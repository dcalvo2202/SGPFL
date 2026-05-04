# External Advisors System Documentation

## Overview

The SGPFL supports external advisors (asesores externos) - professionals from outside the university who can advise students on their TFG projects. This system allows external professionals to register, get approved, and be assigned to student projects.

---

## Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│             EXTERNAL ADVISOR WORKFLOW                           │
└─────────────────────────────────────────────────────────────────┘

    ┌─────────────┐
    │  External   │
    │  Professional│
    └──────┬──────┘
           │
           ▼
    ┌─────────────────────┐
    │  1. Registration    │
    │  (registro.php)     │
    │  - Full name        │
    │  - Email            │
    │  - Institution      │
    │  - Academic title   │
    │  - Expertise area   │
    │  - Experience       │
    └──────┬──────────────┘
           │
           ▼
    ┌─────────────────────┐
    │  2. Pending Review  │
    │  Status: pendiente   │
    │  (admin reviews)    │
    └──────┬──────────────┘
           │
      ┌────┴────┐
      ▼         ▼
   Aprueba   Rechaza
      │         │
      ▼         ▼
    ┌─────┐  ┌──────────┐
    │     │  │ Status:  │
    │     │  │ Rechazado│
    │     │  └──────────┘
    ▼     │
┌─────────┴────────┐
│ 3. Approved      │
│ Status: Aprobado │
│ Create system     │
│ login account    │
└────────┬─────────┘
         │
         ▼
    ┌─────────────────────┐
    │ 4. Link to Student │
    │ (Advisory role)     │
    │ - Select student    │
    │ - Select project    │
    │ - Assign as advisor │
    └────────┬────────────┘
             │
             ▼
    ┌─────────────────────┐
    │ 5. Active Advisor   │
    │ - View project      │
    │ - Add notes         │
    │ - Review documents  │
    │ - Chat with student │
    └─────────────────────┘
```

---

## Database Tables

### external_advisor_profile_requests

Stores registration requests from external professionals.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Auto-increment |
| nombre | varchar(150) | Full name |
| email | varchar(100) | Email address |
| telefono | varchar(15) | Phone number |
| institution | varchar(200) | Institution/company |
| titulo | varchar(100) | Academic title (Dr., MSc., Lic.) |
| area_expertise | varchar(255) | Area of expertise |
| experiencia | text | Professional experience |
| status | varchar(50) | pendiente/Aprobado/Rechazado/En Revision |
| created_at | datetime | Submission timestamp |
| reviewed_by | varchar(50) | Admin who reviewed |
| reviewed_at | datetime | Review timestamp |
| linked_comite_id | int(11) | Linked committee ID |
| linked_at | datetime | Link timestamp |
| applicant_id | varchar(50) | Generated user ID |

### external_advisor_linked_students

Links external advisors to students/projects.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Auto-increment |
| advisor_request_id | int(11) FK → external_advisor_profile_requests | Advisor request ID |
| student_id | varchar(50) FK → sis_user | Student ID |
| project_id | int(11) FK → registered_projects | Project ID |
| linked_at | datetime | Assignment timestamp |
| status | varchar(50) | active/inactive |

---

## User Registration Flow

### Step 1: External Advisor Registration Form

**File:** `registro.php`

User selects "Asesor Externo" and fills:
- Full name (nombre)
- Email (email) - must be unique
- Phone (telefono)
- Institution (institution)
- Academic title (titulo) - Dr., MSc., Lic., etc.
- Area of expertise (area_expertise)
- Professional experience (experiencia)

### Step 2: Admin Review

**File:** `panel_revisar_asesor_externo.php`

Admin (Gestor/CTFG) reviews pending requests:
- View all details
- Approve → Creates login account
- Reject → Notifies applicant

### Step 3: Account Creation

When approved:
1. Generate `applicant_id` (format: EXT + timestamp)
2. Create login in `sis_login` (role = 5)
3. Create user in `sis_user`
4. Generate temporary password
5. Send email with login credentials

---

## Key Files

| File | Purpose |
|------|---------|
| `registro.php` | External advisor registration form |
| `panel_revisar_asesor_externo.php` | Admin review panel |
| `procesar_decision_asesor.php` | Handle approve/reject decisions |
| `panel_comites_asesores.php` | Manage advisor-committee links |
| `mod/login/reset_password_advisor.php` | Password reset for external advisors |
| `mod/login/send_password_recovery_email.php` | Send recovery email |

---

## Roles & Permissions

External advisors have **Role ID = 5** (`sis_rolls`):

| Permission | Description |
|------------|-------------|
| View projects | See assigned projects |
| Add notes | Add notes to projects |
| Review documents | View uploaded documents |
| Chat | Message students and committee |
| Profile management | Update own profile |

---

## Linking to Students

### Automatic Assignment

When an external advisor is approved, they can be linked to:

1. **Direct by Admin:**
   - Admin selects advisor from list
   - Selects student and project
   - Creates link in `external_advisor_linked_students`

2. **Project-based:**
   - When project is registered, committee members may include external advisors
   - System syncs via `linkExternalAdvisorToProject()`

### View Assigned Projects

External advisor sees only their linked projects:

```sql
SELECT pa.nombre, pa.fecha_finalizacion
FROM proyecto_aprobado pa
INNER JOIN external_advisor_linked_students eals ON eals.project_id = pa.id_aprobado
WHERE eals.advisor_request_id = ?
AND eals.status = 'active';
```

---

## Password Recovery

External advisors can reset their password:

1. Go to login page
2. Click "Olvidé mi contraseña"
3. Select "Asesor Externo"
4. Enter email
5. System sends reset link (token-based)
6. Advisor creates new password

**Files:**
- `mod/login/send_password_recovery_email.php`
- `mod/login/reset_password_advisor.php`

---

## Approval Workflow Details

### Decision Options

| Decision | Status Update | Action |
|----------|--------------|--------|
| Aprobado | status = 'Aprobado' | Create system account, send credentials |
| Rechazado | status = 'Rechazado' | Notify via email |
| En Revision | status = 'En Revision' | Request more documents |

### Email Notifications

- **Approved:** Send login credentials
- **Rejected:** Notify reason
- **Revision:** Request additional information

---

## Testing

### Test Registration

1. Go to `registro.php`
2. Select "Asesor Externo"
3. Fill form with test data
4. Submit → Check status = "pendiente"

### Test Approval

1. Login as Admin/Gestor
2. Go to `panel_revisar_asesor_externo.php`
3. Find pending request
4. Click "Aprobado"
5. Verify account created in `sis_login`

---

## Maintenance

### View All External Advisors

```sql
SELECT id, nombre, email, institution, status, created_at
FROM external_advisor_profile_requests
ORDER BY created_at DESC;
```

### View Active Assignments

```sql
SELECT
    ear.nombre AS advisor,
    su.nombre AS student,
    pa.nombre AS project,
    eals.linked_at
FROM external_advisor_linked_students eals
INNER JOIN external_advisor_profile_requests ear ON ear.id = eals.advisor_request_id
INNER JOIN sis_user su ON su.id = eals.student_id
INNER JOIN proyecto_aprobado pa ON pa.id_aprobado = eals.project_id
WHERE eals.status = 'active';
```

### Deactivate Assignment

```sql
UPDATE external_advisor_linked_students
SET status = 'inactive'
WHERE id = ?;
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Cannot register | Check email not already registered |
| Cannot approve | Verify all required fields filled |
| Advisor cannot login | Check role = 5 in sis_login |
| Cannot link to project | Verify project exists and is active |
| Password reset fails | Check email matches registered email |

---

## Security Considerations

1. **Email uniqueness:** System prevents duplicate emails
2. **Password hashing:** Passwords stored as MD5 hash (legacy) or bcrypt
3. **Role validation:** Only role 5 can access advisor functions
4. **Data visibility:** Advisors only see their linked projects
5. **Audit trail:** All status changes logged

---

## Related Documentation

- [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) - Table definitions
- [TFG_WORKFLOW.md](TFG_WORKFLOW.md) - Advisor role in project workflow
- [CHAT_SYSTEM.md](CHAT_SYSTEM.md) - Communication with advisors