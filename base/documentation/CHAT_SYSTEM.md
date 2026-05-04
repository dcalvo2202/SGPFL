# Chat System Documentation

## Overview

The SGPFL includes an internal messaging system (HU-029) that allows communication between students, advisors, and committee members. The chat works like Microsoft Teams - users can create conversations, send messages, and share files.

---

## User Stories & Features

| Feature | Description |
|---------|-------------|
| HU-029.1 | Send text messages to other users |
| HU-029.2 | Send file attachments (PDF, images, documents) |
| HU-029.3 | View conversation history |
| HU-029.4 | Create group chats for project teams |
| HU-029.5 | Real-time message updates |
| HU-029.6 | Search users to start new conversations |
| HU-029.7 | Mark messages as read/unread |
| HU-029.8 | Delete own messages |

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     CHAT SYSTEM ARCHITECTURE                   │
└─────────────────────────────────────────────────────────────────┘

    ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
    │   Browser   │     │   Apache    │     │  MariaDB    │
    │  (Frontend) │────▶│   (PHP)     │────▶│  (Storage)  │
    └──────┬──────┘     └─────────────┘     └─────────────┘
           │                   │
           │            ┌──────┴──────┐
           │            │  AJAX APIs   │
           │            │             │
           │            │ chat_get_    │
           │            │ conversations│
           │            │ chat_send_   │
           │            │ message.php  │
           │            │ chat_get_    │
           │            │ messages.php │
           │            └─────────────┘
           │
           ▼
    ┌─────────────────────────────────────────────────────────────┐
    │  JavaScript Client (chat.js, chat.css)                     │
    │  - Polling every 5 seconds for new messages               │
    │  - WebSocket could be added for real-time (future)        │
    └─────────────────────────────────────────────────────────────┘
```

---

## Database Tables

### chat_conversations

Stores conversation threads.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Auto-increment |
| project_id | int(11) FK → registered_projects | Project ID (optional) |
| created_by | varchar(50) FK → sis_user | Creator user ID |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update |
| title | varchar(200) | Conversation title |
| is_group | tinyint(1) | Group chat flag (1=group, 0=direct) |
| conversation_type | varchar(50) | 'direct' or 'group' |

### chat_participants

Users in each conversation.

| Column | Type | Description |
|--------|------|-------------|
| conversation_id | int(11) FK → chat_conversations | Conversation ID |
| user_id | varchar(50) FK → sis_user | User ID |
| joined_at | datetime | Join timestamp |
| role | varchar(50) | 'admin' or 'member' |

**PK:** Composite (`conversation_id`, `user_id`)

### chat_messages

Actual messages.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Message ID |
| conversation_id | int(11) FK → chat_conversations | Conversation ID |
| sender_id | varchar(50) FK → sis_user | Sender user ID |
| message | text | Message content |
| message_type | varchar(20) | 'text', 'file', 'image' |
| file_path | varchar(500) | File path (if attachment) |
| sent_at | datetime | Sent timestamp |
| read_by | text | JSON array of user IDs who read |

**Indexes:** conversation_id, sender_id, sent_at

---

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `chat_get_conversations.php` | GET | List user's conversations |
| `chat_get_messages.php` | GET | Get messages in conversation |
| `chat_send_message.php` | POST | Send new message |
| `chat_mark_read.php` | POST | Mark message as read |
| `chat_search_users.php` | GET | Search users to start chat |
| `chat.php` | GET | Main chat UI |

---

## File Structure

```
base/
├── chat.php                      # Main chat interface
├── inc/
│   ├── chat_functions.php        # Core chat logic
│   └── css/
│       └── chat.css              # Chat styles
├── chat_get_conversations.php    # API: list conversations
├── chat_get_messages.php         # API: get messages
├── chat_send_message.php         # API: send message
├── chat_mark_read.php            # API: mark as read
├── chat_search_users.php         # API: search users
└── files/
    └── chat/                     # Uploaded chat files
        └── [conversation_id]/
            └── [timestamp]_filename.pdf
```

---

## User Flow

### Starting a Conversation

1. User clicks "New Chat" in chat sidebar
2. Search for user by name, email, or ID
3. Select user → creates direct conversation
4. Send initial message

### Group Chat (Project-based)

1. When user is added to a project, system automatically creates group chat
2. Group includes: student + committee members + advisors
3. Chat name = project name
4. Sync happens via `syncUserProjectChats()` in `chat_get_conversations.php`

### Sending Messages

1. User types message in input box
2. Optionally attach file
3. Click send → POST to `chat_send_message.php`
4. Message stored in `chat_messages`
5. Other participants see it in their conversation list

### File Attachments

```
Files uploaded → stored in /files/chat/[conversation_id]/
File path stored in chat_messages.file_path
Supported: PDF, images, documents (no executables)
Max size: configured in PHP (default: 40MB)
```

---

## Real-time Updates

Currently uses **polling** (not WebSocket):

```javascript
// In chat.js - poll every 5 seconds
setInterval(function() {
    loadConversations();
    loadMessages(currentConversationId);
}, 5000);
```

**Future enhancement:** Could implement WebSocket for instant messaging.

---

## Roles & Permissions

| Role | Can Chat With |
|------|----------------|
| Estudiante | Committee, Advisors, other Students |
| Asesor | Students assigned to them, Committee |
| CTFG (Comisión) | Students, Advisors |
| Gestor Académico | All users |
| Administrador | All users |

---

## Security

### Input Validation

- All inputs sanitized with `htmlspecialchars()`
- File uploads validated for type
- SQL queries use prepared statements (prevents injection)

### File Upload Restrictions

```php
// Allowed MIME types
$allowed_types = [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
```

### Message Deletion

- Users can delete their own messages only
- Deletion is soft (message content cleared, record remains)

---

## Testing

### Manual Test: Send Message

1. Login as student (e.g., 206580363)
2. Navigate to Chat (header icon)
3. Search for another user
4. Send message "Test message"
5. Login as recipient → verify message appears

### API Test

```bash
# Get conversations
curl "http://localhost/base/chat_get_conversations.php"

# Send message (POST)
curl -X POST "http://localhost/base/chat_send_message.php" \
  -d "conversation_id=1&message=Hello"
```

---

## Maintenance

### Clean Old Messages

```sql
-- Delete messages older than 1 year (keep conversation structure)
DELETE FROM chat_messages
WHERE sent_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### View Statistics

```sql
-- Total conversations
SELECT COUNT(*) FROM chat_conversations;

-- Total messages
SELECT COUNT(*) FROM chat_messages;

-- Most active conversations
SELECT cc.title, COUNT(cm.id) as message_count
FROM chat_conversations cc
JOIN chat_messages cm ON cm.conversation_id = cc.id
GROUP BY cc.id
ORDER BY message_count DESC
LIMIT 10;
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Messages not loading | Check database connection; check `chat_messages` table |
| Cannot send file | Check PHP `upload_max_filesize`; check file type allowed |
| Search not finding users | Verify LDAP connection; check `sis_user` table |
| Group chat not created | Check `syncUserProjectChats()` function; verify project membership |

---

## Related Documentation

- [TFG_WORKFLOW.md](TFG_WORKFLOW.md) - Chat integration in project workflow
- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - Table definitions
- [DEPLOYMENT.md](DEPLOYMENT.md) - File storage configuration