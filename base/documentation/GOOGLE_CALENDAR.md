# Google Calendar Integration

## Overview

The SGPFL system integrates with Google Calendar to allow students and advisors to sync project deadlines and defense dates to their personal Google calendars.

---

## How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│               GOOGLE CALENDAR INTEGRATION FLOW                 │
└─────────────────────────────────────────────────────────────────┘

    ┌──────────────┐     ┌──────────────┐     ┌──────────────┐
    │   Student    │     │    SGPFL     │     │    Google    │
    │  (User)      │     │    System    │     │    Calendar   │
    └──────┬───────┘     └──────┬───────┘     └──────┬───────┘
           │                    │                    │
           │  1. Connect        │                    │
           │───────────────────>│                    │
           │                    │                    │
           │              2. OAuth 2.0             │
           │<─────────────────────────────────────────>│
           │                Authorization            │
           │                    │                    │
           │              3. Store Tokens            │
           │                    │                    │
           │                    ▼                    │
           │           ┌──────────────┐              │
           │           │ google_      │              │
           │           │ calendar_    │              │
           │           │ tokens       │              │
           │           └──────────────┘              │
           │                    │                    │
           │  4. Select Events  │                    │
           │<───────────────────│                    │
           │                    │                    │
           │              5. Create Calendar Events │
           │<─────────────────────────────────────────│
           │                    │                    │
           ▼                    ▼                    ▼
```

---

## Prerequisites

### Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable the **Google Calendar API**
4. Configure OAuth consent screen:
   - User Type: External
   - Add authorized domains
   - Add scopes: `https://www.googleapis.com/auth/calendar.events`
5. Create OAuth 2.0 credentials:
   - Application type: Web application
   - Authorized redirect URIs: `https://your-domain.com/auth/google_callback.php`

### Configuration in config.inc

Add to `config.inc`:

```php
// Google Calendar Configuration
$google_calendar_config = [
    'client_id'        => 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret'    => 'YOUR_CLIENT_SECRET',
    'redirect_uri'     => 'https://your-domain.com/auth/google_callback.php',
    'scopes'           => Google_Service_Calendar::CALENDAR_EVENTS,
    'token_cipher_key' => 'generate-a-secure-32-char-random-key-here',
    'token_cipher'     => 'aes-256-gcm',
];
```

**Generate a secure key:**
```bash
openssl rand -hex 16
```

---

## Database Tables

### google_calendar_tokens

Stores encrypted OAuth tokens for each user.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Auto-increment |
| user_id | varchar(50) FK | User ID (unique) |
| access_token | text | Encrypted access token |
| refresh_token | text | Encrypted refresh token |
| expires_at | datetime | Token expiration |
| created_at | datetime | Creation timestamp |
| updated_at | datetime | Last update |

**Constraint:** UNIQUE on `user_id`

### google_calendar_sync_log

Tracks sync operations for debugging.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Log ID |
| user_id | varchar(50) | User ID |
| operation | varchar(50) | create/update/delete |
| event_id | varchar(100) | Google Calendar event ID |
| project_id | int(11) | Related project |
| status | varchar(50) | success/failed |
| error_message | text | Error details |
| executed_at | datetime | Execution timestamp |

---

## User Flow

### 1. Connect Google Account

**Files:**
- `auth/google_auth.php` - Initiates OAuth flow
- `auth/google_callback.php` - Handles OAuth callback

**Steps:**
1. User clicks "Connect Google Calendar" in profile/settings
2. System redirects to Google OAuth consent screen
3. User grants permission
4. Google redirects to `google_callback.php`
5. System stores encrypted tokens in `google_calendar_tokens`

### 2. Disconnect

**File:** `auth/disconnect_google.php`

Removes stored tokens from the database.

### 3. Automatic Sync

When deadlines approach, the system syncs events:

**Triggers:**
- Cron job: `cron_check_deadlines.php` (line 109-180)
- Final defense scheduled

**Events synced:**
- Deadline reminders (30, 15, 7, 3, 1 days)
- Defense dates
- Project milestones

---

## Key Files

| File | Purpose |
|------|---------|
| `auth/google_auth.php` | Initiate OAuth authorization |
| `auth/google_callback.php` | Handle OAuth callback & token exchange |
| `auth/disconnect_google.php` | Disconnect Google account |
| `service/GoogleCalendarService.php` | Core Google Calendar API integration |
| `cron_check_deadlines.php` | Auto-sync deadline events |
| `panel_estudiante.php` | User interface for connecting |

---

## GoogleCalendarService Methods

```php
class GoogleCalendarService {
    // OAuth & Authentication
    public function getAuthUrl()           // Get OAuth authorization URL
    public function handleCallback($code)  // Exchange code for tokens
    public function refreshToken()         // Refresh expired token
    public function disconnect()           // Remove stored tokens

    // Calendar Operations
    public function createEvent($event)    // Create calendar event
    public function updateEvent($eventId, $event)  // Update event
    public function deleteEvent($eventId)  // Delete event
    public function listEvents()           // List user's events

    // Token Management
    public function hasValidToken()        // Check if user has valid token
    public function getTokenExpiration()   // Get token expiry time
}
```

---

## Security

### Token Encryption

Tokens are encrypted using AES-256-GCM before storage:

```php
// Encryption key derived from config
$key = hash('sha256', $google_calendar_config['token_cipher_key'], true);

// Encrypt
$iv = random_bytes(16);
$ciphertext = openssl_encrypt($token, 'aes-256-gcm', $key, 0, $iv);

// Store: $iv . $ciphertext . $tag
```

### Token Refresh

- Access tokens expire after 1 hour
- Service automatically refreshes using refresh_token
- Refresh tokens don't expire (unless user revokes access)

---

## Troubleshooting

### Issue: "invalid_grant" Error

**Cause:** Authorization code expired or already used.

**Solution:**
1. Clear stored tokens: `DELETE FROM google_calendar_tokens WHERE user_id = ?`
2. User initiates new OAuth flow

### Issue: "Calendar service not enabled"

**Cause:** Google Calendar API not enabled in Cloud Console.

**Solution:**
1. Go to Google Cloud Console → APIs & Services → Library
2. Search "Google Calendar API"
3. Click Enable

### Issue: Tokens not saving

**Cause:** Encryption key mismatch or database permission.

**Solution:**
1. Verify `$google_calendar_config['token_cipher_key']` is consistent
2. Check database user has INSERT/UPDATE permissions on `google_calendar_tokens`

---

## Testing

### Manual Test Script

```php
<?php
require_once __DIR__ . '/service/GoogleCalendarService.php';
require_once __DIR__ . '/inc/db/bdcommon.inc';

$conn = new mysqli($db_host, $usuario, $clave, $db);
$gc = new Service\GoogleCalendarService($conn);

// Check if user is connected
if ($gc->hasValidToken('205610158')) {
    echo "User is connected to Google Calendar";
} else {
    echo "User is not connected. URL: " . $gc->getAuthUrl();
}
```

---

## API Rate Limits

Google Calendar API has quota limits:
- **Queries per day:** 1,000,000
- **Queries per 100 seconds:** 500

The system includes error handling for quota exceeded scenarios.

---

## Maintenance

### Clean Up Old Tokens

```sql
-- Remove tokens for users who haven't logged in for 6 months
DELETE FROM google_calendar_tokens
WHERE updated_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

### View Sync History

```sql
-- Recent sync operations
SELECT * FROM google_calendar_sync_log
ORDER BY executed_at DESC
LIMIT 50;

-- Failed syncs
SELECT * FROM google_calendar_sync_log
WHERE status = 'failed'
ORDER BY executed_at DESC;
```

---

## Disable Google Calendar

To disable Google Calendar integration:

```php
// In config.inc - set to empty array or comment out
// $google_calendar_config = [];
```

Or remove the OAuth credentials from Google Cloud Console.

---

## Related Documentation

- [Google Calendar API Docs](https://developers.google.com/calendar/api)
- [OAuth 2.0 for PHP](https://github.com/google/google-api-php-client)
- [TFG Workflow](TFG_WORKFLOW.md) - Calendar events in workflow
- [Cron Jobs](CRON_JOBS.md) - Automatic sync via cron