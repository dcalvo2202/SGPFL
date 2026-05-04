# Cron Jobs Documentation

## Overview

The SGPFL system uses scheduled tasks (cron jobs) to automate critical background operations like deadline monitoring, cleanup, and synchronization.

---

## Scheduled Tasks

### 1. Deadline Check (Primary)

**Script:** `cron_check_deadlines.php`

**Purpose:** Monitor project deadlines and send automatic reminders to students.

**Schedule:** Daily at 9:00 AM (recommended)

**Configuration:**
| Constant | Default | Description |
|----------|---------|-------------|
| DEADLINE_THRESHOLDS | [30, 15, 7, 3, 1] | Days before deadline to send alerts |
| DEADLINE_EMAIL_ENABLED | true | Enable/disable email notifications |
| DEADLINE_FROM_EMAIL | noreply@una.cr | Sender email address |
| DEADLINE_FROM_NAME | SGPFL - Escuela de Informática | Sender display name |

**Location:** `config.inc` lines 101-111

---

## How It Works

### Deadline Detection Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                   DEADLINE CHECK FLOW                           │
└─────────────────────────────────────────────────────────────────┘

    ┌──────────────────┐
    │ Cron Job         │
    │ Runs Daily      │
    │ 9:00 AM         │
    └────────┬─────────┘
             │
             ▼
    ┌─────────────────────────┐
    │ getProjectsNearDeadline │
    │ Query: proyecto_aprobado │
    │ with fecha_finalizacion  │
    └────────┬────────────────┘
             │
             ▼
    ┌─────────────────────────────────┐
    │ Calculate Real Deadline         │
    │ Consider:                       │
    │ - Base fecha_finalizacion        │
    │ - Approved extensions           │
    │ (tfg_extension_requests)         │
    └────────┬────────────────────────┘
             │
             ▼
    ┌───────────────────────────────┐
    │ Check: hasAlertBeenSent()      │
    │ Table: deadline_alerts_sent    │
    │ Prevent duplicate alerts       │
    └────────┬───────────────────────┘
             │
        ┌────┴────┐
        ▼         ▼
   Already    Continue
   Sent       Process
        │         │
        ▼         ▼
   Skip     Register internal alert
        │     (tfg_notifications)
        │         │
        │         ▼
        │    Send email notification
        │    (PHP mail())
        │         │
        │         ▼
        │    Mark alert as sent
        │    (deadline_alerts_sent)
        │         │
        │         ▼
        │    Sync Google Calendar
        │    (if connected)
        │         │
        ▼         ▼
   Done      Done
```

---

## Implementation Details

### Query Logic

```sql
SELECT
    p.id_aprobado AS project_id,
    pae.estudiante_id AS user_id,
    p.fecha_finalizacion AS fecha_base,
    -- Get all approved extensions
    (SELECT GROUP_CONCAT(DATE(pr.response_date))
     FROM tfg_extension_requests pr
     WHERE pr.proposal_id = p.proposal_id
       AND pr.status = 'aprobada') AS prorrogas,
    -- Calculate days remaining
    DATEDIFF(
        COALESCE(
            (SELECT MAX(pr.response_date)
             FROM tfg_extension_requests pr
             WHERE pr.proposal_id = p.proposal_id
               AND pr.status = 'aprobada'),
            p.fecha_finalizacion
        ),
        CURDATE()
    ) AS dias_restantes
FROM proyecto_aprobado p
INNER JOIN proyecto_aprobado_estudiantes pae
    ON pae.id_aprobado = p.id_aprobado
WHERE p.fecha_finalizacion IS NOT NULL
HAVING dias_restantes IN (30, 15, 7, 3, 1)
```

### Threshold Alerts

| Days Remaining | Alert Type | Description |
|---------------|------------|-------------|
| 30 | First reminder | One month warning |
| 15 | Second reminder | Two weeks warning |
| 7 | Third reminder | One week warning |
| 3 | Final reminder | Three days warning |
| 1 | Final reminder | One day warning |

---

## Database Tables

### deadline_alerts_sent

Prevents duplicate alert emails.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Auto-increment ID |
| user_id | varchar(50) | Student ID |
| project_id | int(11) | Project ID |
| days_threshold | int(11) | Alert threshold (30/15/7/3/1) |
| sent_at | datetime | Timestamp |

**Indexes:** user_id, project_id, days_threshold

---

### tfg_notifications

Internal system notifications.

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) PK | Notification ID |
| user_id | varchar(50) FK | Recipient user |
| title | varchar(200) | Notification title |
| message | text | Message content |
| type | varchar(50) | Type (info/warning/error) |
| link | varchar(500) | Target URL |
| read_status | tinyint(1) | Read flag |
| created_at | datetime | Creation timestamp |

---

## Email Template

The system sends HTML emails with:

**Subject:** `Recordatorio de vencimiento del plazo de entrega - SGPFL`

**Content includes:**
- Student name
- Project title
- Deadline date (considering extensions)
- Days remaining
- Contact information (Escuela de Informática)
- Automatic disclaimer

**From:** `SGPFL - Escuela de Informática <noreply@una.cr>`

---

## Google Calendar Integration

If the student has connected their Google account, the cron job also:

1. Gets all project members (proposal owner + registered project members)
2. Creates or updates calendar events
3. Logs sync operations in `google_calendar_sync_log`

**Event details:**
- Title: "Vencimiento TFG: [Project Name]"
- Date: Deadline date
- Description: Days remaining and project details

---

## Cron Configuration

### Linux (crontab)

```bash
# Edit crontab
crontab -e

# Add this line for daily at 9:00 AM
0 9 * * * /usr/bin/php /var/www/html/base/cron_check_deadlines.php >> /var/log/sgpfl_deadlines.log 2>&1
```

### Windows (Task Scheduler)

```powershell
# Create scheduled task (run as Administrator)
schtasks /create /tn "SGPFL Deadline Check" /tr "php C:\xampp\htdocs\base\cron_check_deadlines.php" /sc daily /st 09:00 /ru System
```

### Docker

```yaml
# docker-compose.yml
services:
  cron:
    build: ./php
    volumes:
      - .:/var/www/html
    command: >
      sh -c "while true; do
        php /var/www/html/cron_check_deadlines.php;
        sleep 86400;
      done"
    # Or use external cron service
```

**Note:** For Docker, the cron service must be set up separately. The PHP container needs cron installed.

---

## Testing & Debugging

### Manual Execution

```bash
# Run manually
php cron_check_deadlines.php

# With debug output
php cron_check_deadlines.php --debug-load

# Verbose mode (add to script)
error_log("Processing project: " . $project_id);
```

### Debug Flags

The script supports `--debug-load` flag to:
- Show PHP version
- Verify file paths
- Check function definitions
- Display opcache status

### Logs

Check PHP error log:
```bash
# Linux
tail -f /var/log/php_errors.log

# Windows (XAMPP)
type C:\xampp\php\logs\php_error_log
```

---

## Email Delivery Issues

### Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| Emails not sent | PHP mail() disabled | Check php.ini `sendmail_path` |
| | SMTP not configured | Configure msmtp or sendmail |
| | Firewall blocking port 25 | Contact IT admin |
| | Invalid sender address | Check DEADLINE_FROM_EMAIL |
| Duplicate alerts | Database insert failed | Check deadline_alerts_sent table |
| Missing alerts | Threshold not matched | Verify DEADLINE_THRESHOLDS |

### Email Configuration (Linux)

```bash
# Install msmtp
apt-get install msmtp-mta

# Configure /etc/msmtprc
account default
host smtp.una.cr
port 587
from noreply@una.cr
auth on
user noreply@una.cr
password YOUR_PASSWORD
tls on
tls_certcheck off
```

### Email Configuration (php.ini)

```ini
[mail function]
sendmail_path = "C:\xampp\msmtp\msmtp.exe -t"
SMTP = smtp.una.cr
smtp_port = 587
```

---

## Performance Considerations

- **Execution time:** ~1-2 seconds for 100 projects
- **Memory:** < 64MB typical
- **Database queries:** 1 main query + N queries per project
- **Email queue:** Synchronous (blocks until sent)

**Optimization tips:**
- Add LIMIT to queries for large datasets
- Consider async email queue for high volume
- Index `fecha_finalizacion` column

---

## Alerts Summary

| Threshold | Email Sent | Calendar Sync | Notification Created |
|-----------|------------|---------------|----------------------|
| 30 days | Yes | If connected | Yes |
| 15 days | Yes | If connected | Yes |
| 7 days | Yes | If connected | Yes |
| 3 days | Yes | If connected | Yes |
| 1 day | Yes | If connected | Yes |

---

## Related Files

| File | Description |
|------|-------------|
| `cron_check_deadlines.php` | Main cron script |
| `inc/deadline_functions.php` | Core deadline functions |
| `inc/alert_functions.php` | Alert management functions |
| `inc/db/bdcommon.inc` | Database connection |
| `config.inc` | Configuration constants |
| `service/GoogleCalendarService.php` | Calendar integration |

---

## Monitoring

### Success Metrics

- Email delivery rate: >95%
- No duplicate alerts
- Calendar sync success rate

### Alert Monitoring

```sql
-- Check alerts sent today
SELECT * FROM deadline_alerts_sent
WHERE DATE(sent_at) = CURDATE();

-- Check pending projects near deadline
SELECT * FROM proyecto_aprobado
WHERE DATEDIFF(fecha_finalizacion, CURDATE()) <= 30
AND estado = 'ACTIVO';
```

---

## Security Considerations

- **Path access:** Ensure cron script is not web-accessible
- **Move to outside webroot:**
  ```php
  // Instead of: /var/www/html/base/cron_check_deadlines.php
  // Use: /opt/sgpfl/cron/cron_check_deadlines.php
  ```
- **File permissions:** 640 for cron scripts (owner read/write only)
- **Database credentials:** Use dedicated cron user (read-only on most tables)
- **Log rotation:** Implement log rotation to prevent disk full