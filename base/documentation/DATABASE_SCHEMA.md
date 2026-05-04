# Database Schema Documentation

## Overview

The SGPFL system uses **54 tables** organized into functional areas:

1. Authentication & Authorization (7 tables)
2. Sessions & State (2 tables)
3. Logging & Audit (2 tables)
4. System Parameters (2 tables)
5. TFG Proposals (4 tables)
6. Projects (3 tables)
7. Project Members (2 tables)
8. Project Documents (5 tables)
9. Notifications & Alerts (3 tables)
10. External Advisors (2 tables)
11. Committees & Categories (2 tables)
12. Agreements (2 tables)
13. Chat System (3 tables)
14. Templates & Minutes (3 tables)
15. Google Calendar (2 tables)
16. Archives (5 tables)
17. Password Recovery (1 table)

---

## 1. Authentication & Authorization

### `sis_login`

User authentication credentials.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | varchar(50) | PK | User ID (cedula) |
| pass | varchar(255) | NOT NULL | Password hash (MD5) |
| id_roll | int(11) | FK → sis_rolls | Role ID |

**Indexes:** Primary key on `id`, foreign key on `id_roll`

---

### `sis_user`

User profile information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | varchar(50) | PK, FK → sis_login | User ID |
| nombre | varchar(150) | NULL | Full name |
| email | varchar(100) | NULL | Email address |
| telefono | varchar(15) | NULL | Phone number |
| id_tipo_tel | varchar(1) | NULL | Phone type (T/M) |

**Relationships:** One-to-one with `sis_login` (CASCADE delete)

---

### `sis_rolls`

System roles.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_roll | int(11) | PK, AUTO_INCREMENT | Role ID |
| roll_name | varchar(100) | NULL | Role name |
| roll_desc | varchar(500) | NULL | Role description |

**Default Roles:**
- 1: Administrador
- 2: Gestor Academico
- 3: Comision (CTFG)
- 4: Estudiante
- 5: Asesor

---

### `sis_mod`

System modules.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_mod | int(11) | PK, AUTO_INCREMENT | Module ID |
| mod_name | varchar(100) | NULL | Module name |
| mod_desc | varchar(500) | NULL | Module description |
| active | varchar(1) | DEFAULT '1' | Active status |

**Modules:**
1. Acceso (Authentication)
2. Busqueda (Project Search)
3. Historial y Auditoria (History & Audit)
4. Notificaciones (Notifications)
5. Documentacion y versionado (Document Versioning)
6. Gestion de proyectos (Project Management)
7. Reportes y paneles (Reports & Dashboards)
8. Gestion academica (Academic Management)

---

### `sis_mod_actions`

Module actions/permissions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_action | int(11) | PK, AUTO_INCREMENT | Action ID |
| action_name | varchar(100) | NULL | Action name |
| action_desc | varchar(500) | NULL | Action description |

**Actions:**
1. Ver (View)
2. Listar (List)
3. Añadir (Add)
4. Editar (Edit)
5. Eliminar (Delete)
6. Imprimir (Print)

---

### `sis_permits`

Role-based access control (RBAC) matrix.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_mod | int(11) | FK → sis_mod | Module ID |
| id_action | int(11) | FK → sis_mod_actions | Action ID |
| id_roll | int(11) | FK → sis_rolls | Role ID |

**PK:** Composite (`id_mod`, `id_action`, `id_roll`)

---

### `sis_tipo_tel`

Phone type catalog.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Type ID |
| tipo | varchar(50) | NULL | Type name (T=Tower, M=Mobile) |

---

## 2. Sessions & State

### `sis_sessions`

Active user sessions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| sid | varchar(100) | PK | Session ID |
| expires | int(11) | NOT NULL | Expiration timestamp |
| forced_expires | int(11) | NOT NULL | Forced expiration |
| ua | varchar(40) | NOT NULL | User-Agent hash |

---

### `sis_sessions_vars`

Session variables.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| name | text | NOT NULL | Variable name |
| value | text | NOT NULL | Variable value |
| sid | varchar(100) | FK → sis_sessions | Session ID |

**FK:** CASCADE delete on `sid`

---

## 3. Logging & Audit

### `sis_log`

System audit log (immutable).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_bi | int(11) | PK, AUTO_INCREMENT | Log ID |
| id_user | varchar(50) | NULL | User ID |
| date_bi | datetime | NOT NULL | Timestamp |
| action_type | varchar(50) | NOT NULL DEFAULT 'GENERAL' | Action category |
| action_result | varchar(20) | NOT NULL DEFAULT 'SUCCESS' | Result (SUCCESS/FAIL) |
| ip_address | varchar(45) | NULL | Client IP (IPv4/IPv6) |
| device_info | varchar(255) | NULL | User-Agent |
| detail | text | NULL | Log details |

**Indexes:**
- `idx_sis_log_user_date` (id_user, date_bi)
- `idx_sis_log_action_result_date` (action_type, action_result, date_bi)
- `idx_sis_log_date` (date_bi)

**Triggers:**
- `trg_sis_log_no_update`: Prevents UPDATE
- `trg_sis_log_no_delete`: Prevents DELETE (except with @sis_log_allow_purge)

---

### `auditoria_cambios_fecha`

Date change audit trail for projects and extensions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Audit ID |
| tabla_origen | varchar(100) | NOT NULL | Source table name |
| id_registro | int(11) | NOT NULL | Record ID |
| campo_modificado | varchar(100) | NOT NULL | Field name |
| valor_anterior | datetime | NULL | Previous value |
| valor_nuevo | datetime | NULL | New value |
| modificado_por | varchar(50) | NOT NULL | User who changed |
| fecha_modificacion | datetime | NOT NULL | Change timestamp |
| descripcion | text | NULL | Additional context |

**Indexes:** idx_tabla_origen, idx_id_registro, idx_modificado_por, idx_fecha_modificacion, idx_campo_modificado

---

## 4. System Parameters

### `sis_parametros_varios`

System configuration parameters.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_pv | int(16) | PK, AUTO_INCREMENT | Parameter ID |
| parametro | varchar(50) | NULL | Parameter name |
| valor | varchar(100) | NULL | Parameter value |
| descripcion | varchar(300) | NULL | Description |

---

## 5. TFG Proposals

### `project_types`

TFG project type definitions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Type ID |
| type_name | varchar(100) | NOT NULL | Type name |
| max_members | int(11) | NOT NULL DEFAULT 1 | Max team members |
| description | text | NULL | Description |
| active | boolean | DEFAULT TRUE | Active status |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

---

### `tfg_proposals`

TFG proposal submissions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Proposal ID |
| user_id | varchar(50) | FK → sis_user | Submitter ID |
| title | varchar(255) | NOT NULL | Project title |
| disciplines | varchar(255) | NOT NULL | Selected disciplines |
| project_description | text | NULL | Project description |
| document | longblob | NULL | PDF document |
| file_name | varchar(255) | NOT NULL DEFAULT '' | Original filename |
| mime_type | varchar(100) | NOT NULL DEFAULT '' | MIME type |
| file_size | int(11) | NOT NULL DEFAULT 0 | File size (bytes) |
| status | enum | DEFAULT 'Pendiente de Revision' | Proposal status |
| admin_comments | text | NULL | Review comments |
| reviewed_by | varchar(50) | FK → sis_user | Reviewer ID |
| reviewed_at | datetime | NULL | Review timestamp |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Submission timestamp |
| updated_at | datetime | AUTO UPDATE | Last modification |

**Status Values:** 'Pendiente de Revision', 'Cumple requisitos', 'No cumple requisitos', 'Aprobado', 'Rechazado'

**Indexes:** idx_user_id, idx_status, idx_title, idx_created

---

### `tfg_proposal_history`

Proposal version history.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | History ID |
| proposal_id | int(11) | FK → tfg_proposals | Proposal ID |
| document | longblob | NOT NULL | Document version |
| file_name | varchar(255) | NOT NULL | Filename |
| mime_type | varchar(100) | NOT NULL | MIME type |
| file_size | int(11) | UNSIGNED NOT NULL | Size |
| status | varchar(50) | NOT NULL | Status at version |
| reviewed_by | varchar(50) | FK → sis_user | Reviewer |
| comments | text | NULL | Comments |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Timestamp |

---

### `tfg_extension_requests`

Extension/Prórroga requests.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Request ID |
| proposal_id | int(11) | NOT NULL | TFG Proposal ID |
| user_id | varchar(50) | NOT NULL | Requester ID |
| extension_number | tinyint(4) | NOT NULL | Extension number (1=1 year, 2=6 months) |
| reason | text | NOT NULL | Request reason |
| status | enum | DEFAULT 'pendiente' | Request status |
| request_date | datetime | DEFAULT CURRENT_TIMESTAMP | Request date |
| response_date | datetime | NULL | Response date |
| fecha_actualizada | datetime | NULL | New project date |
| responded_by | varchar(50) | NULL | Responder ID |
| response_comment | text | NULL | Response comments |
| documento_path | text | NULL | Supporting documents (JSON array) |

**Triggers:** `trg_audit_extension_requests_dates` - Audits date changes

---

## 6. Projects

### `registered_projects`

Active TFG project registrations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Project ID |
| tfg_proposal_id | int(11) | FK → tfg_proposals | Proposal ID |
| project_type_id | int(11) | FK → project_types | Project type |
| status | enum | DEFAULT 'Registrado' | Project status |
| start_date | date | NULL | Start date |
| end_date | date | NULL | End date (deadline) |
| final_grade | decimal(3,1) | NULL | Final grade |
| supervisor_id | varchar(50) | FK → sis_user | Supervisor/Asesor |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| updated_at | datetime | AUTO UPDATE | Last modification |

**Status Values:** 'Registrado', 'En Desarrollo', 'En Revision', 'Finalizado', 'Aprobado', 'Rechazado'

**Indexes:** idx_tfg_proposal, idx_project_type, idx_status, idx_supervisor

**Constraints:** UNIQUE KEY on tfg_proposal_id

**Triggers:** `trg_audit_registered_projects_dates` - Audits start_date and end_date changes

---

### `proyecto_aprobado`

Approved project documents.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_aprobado | int(11) | PK, AUTO_INCREMENT | Approved ID |
| nombre | varchar(150) | NOT NULL | Project name |
| proposal_id | int(11) | FK → tfg_proposals | Proposal reference |
| comite_id | int(11) | FK → comite | Committee ID |
| documento | longblob | NOT NULL | PDF document |
| aprobado | tinyint(1) | DEFAULT 1 | Approval flag |
| identificador | varchar(50) | UNIQUE | Unique identifier |
| fecha_creacion | datetime | NOT NULL | Creation date |
| fecha_finalizacion | datetime | NOT NULL | Completion deadline |
| estado | varchar(20) | DEFAULT 'ACTIVO' | Status |
| fecha_ultimo_avance | datetime | NULL | Last progress update |

**Indexes:** uq_identificador, idx_comite_id, idx_proposal_id

---

### `proyecto_notas`

Project notes/comments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_nota | int(11) | PK | Note ID |
| proyecto_id | int(11) | FK → proyecto_aprobado | Project ID |
| titulo | varchar(200) | NOT NULL | Note title |
| notas | text | NOT NULL | Note content |
| creado_por | varchar(50) | NULL | Creator ID |
| creado_en | timestamp | DEFAULT CURRENT_TIMESTAMP | Creation time |
| etapa_proyecto | varchar(100) | NULL | Project stage |

**FK:** ON DELETE CASCADE

---

## 7. Project Members

### `project_members`

Team members for registered projects.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Member ID |
| project_id | int(11) | FK → registered_projects | Project ID |
| user_id | varchar(50) | FK → sis_user | Member ID |
| role | varchar(50) | DEFAULT 'member' | Role (leader/member) |
| joined_at | datetime | DEFAULT CURRENT_TIMESTAMP | Join date |

**Indexes:** idx_project_id, idx_user_id

---

### `proyecto_aprobado_estudiantes`

Students in approved projects.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_aprobado | int(11) | FK → proyecto_aprobado | Project ID |
| estudiante_id | varchar(50) | FK → sis_user | Student ID |

**PK:** Composite (`id_aprobado`, `estudiante_id`)

---

## 8. Project Documents

### `tfg_files`

Uploaded project files.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | File ID |
| project_id | int(11) | FK → registered_projects | Project ID |
| proposal_id | int(11) | FK → tfg_proposals | Proposal ID |
| file_name | varchar(255) | NOT NULL | Original filename |
| file_path | varchar(500) | NOT NULL | Stored path |
| mime_type | varchar(100) | NOT NULL | MIME type |
| file_size | bigint | NOT NULL | Size in bytes |
| uploaded_by | varchar(50) | FK → sis_user | Uploader ID |
| uploaded_at | datetime | DEFAULT CURRENT_TIMESTAMP | Upload timestamp |
| file_category | varchar(50) | NULL | Category (proposal/document/report) |
| version | int(11) | DEFAULT 1 | Version number |
| is_latest | tinyint(1) | DEFAULT 1 | Latest version flag |

**Indexes:** idx_project_id, idx_proposal_id, idx_uploaded_by

---

### `tfg_final_documents`

Final project documents (thesis).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Document ID |
| project_id | int(11) | FK → registered_projects | Project ID |
| file_name | varchar(255) | NOT NULL | Filename |
| file_path | varchar(500) | NOT NULL | Storage path |
| mime_type | varchar(100) | NOT NULL | MIME type |
| file_size | bigint | NOT NULL | File size |
| submitted_by | varchar(50) | FK → sis_user | Submitter ID |
| submitted_at | datetime | DEFAULT CURRENT_TIMESTAMP | Submission date |
| status | varchar(50) | DEFAULT 'pending' | Review status |

---

### `tfg_document_reviews`

Document review records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Review ID |
| document_id | int(11) | FK → tfg_final_documents | Document ID |
| reviewer_id | varchar(50) | FK → sis_user | Reviewer ID |
| review_date | datetime | DEFAULT CURRENT_TIMESTAMP | Review date |
| decision | varchar(50) | NOT NULL | Decision (approved/needs_revision/rejected) |
| comments | text | NULL | Review comments |

---

### `tfg_project_timeline`

Project milestone timeline.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Timeline ID |
| project_id | int(11) | FK → registered_projects | Project ID |
| milestone_name | varchar(100) | NOT NULL | Milestone name |
| due_date | date | NOT NULL | Due date |
| completed_date | date | NULL | Completion date |
| status | varchar(50) | DEFAULT 'pending' | Status (pending/in_progress/completed) |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

---

## 9. Notifications & Alerts

### `tfg_notifications`

In-app notifications.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Notification ID |
| user_id | varchar(50) | FK → sis_user | Recipient ID |
| title | varchar(200) | NOT NULL | Notification title |
| message | text | NOT NULL | Message content |
| type | varchar(50) | DEFAULT 'info' | Type (info/warning/error/success) |
| link | varchar(500) | NULL | Target URL |
| read_status | tinyint(1) | DEFAULT 0 | Read flag |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

**Indexes:** idx_user_id, idx_read_status, idx_created_at

---

### `user_alerts`

User-configurable alerts.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Alert ID |
| user_id | varchar(50) | NOT NULL | User ID |
| alert_type | varchar(50) | NOT NULL | Alert type |
| enabled | tinyint(1) | DEFAULT 1 | Enabled flag |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

---

### `deadline_alerts_sent`

Deadline alert tracking (prevent duplicate notifications).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | ID |
| project_id | int(11) | NOT NULL | Project ID |
| alert_type | varchar(50) | NOT NULL | Alert type (deadline/reminder) |
| sent_at | datetime | NOT NULL | Sent timestamp |

**Indexes:** idx_project_id, idx_alert_type

---

## 10. External Advisors

### `external_advisor_profile_requests`

External advisor registration requests.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Request ID |
| nombre | varchar(150) | NOT NULL | Full name |
| email | varchar(100) | NOT NULL | Email |
| telefono | varchar(15) | NULL | Phone |
| institution | varchar(200) | NOT NULL | Institution |
| titulo | varchar(100) | NOT NULL | Academic title |
| area_expertise | varchar(255) | NOT NULL | Expertise area |
| experiencia | text | NULL | Experience details |
| status | varchar(50) | DEFAULT 'pendiente' | Request status |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Submission date |
| reviewed_by | varchar(50) | NULL | Reviewer ID |
| reviewed_at | datetime | NULL | Review date |

---

### `external_advisor_linked_students`

External advisor-student assignments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Link ID |
| advisor_request_id | int(11) | FK → external_advisor_profile_requests | Advisor request |
| student_id | varchar(50) | FK → sis_user | Student ID |
| project_id | int(11) | FK → registered_projects | Project ID |
| linked_at | datetime | DEFAULT CURRENT_TIMESTAMP | Link date |
| status | varchar(50) | DEFAULT 'active' | Status |

---

## 11. Committees & Categories

### `categorias`

Project categories/disciplines.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id_categoria | int(11) | PK, AUTO_INCREMENT | Category ID |
| nombre | varchar(100) | NOT NULL | Category name |
| descripcion | varchar(300) | NULL | Description |

---

### `comite`

Evaluation committees.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| Id | int(11) | PK, AUTO_INCREMENT | Committee ID |
| Nombre | varchar(150) | NOT NULL | Committee name |
| descripcion | varchar(500) | NULL | Description |
| miembros | text | NULL | Member IDs (JSON) |
| estado | varchar(20) | DEFAULT 'ACTIVO' | Status |

---

## 12. Agreements

### `acuerdo_cancelacion`

Project cancellation agreements.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Agreement ID |
| proyecto_id | int(11) | FK → proyecto_aprobado | Project ID |
| usuario_id | varchar(50) | NOT NULL | User ID |
| motivo | varchar(500) | NOT NULL | Cancellation reason |
| observaciones | text | NULL | Observations |
| fecha_cancelacion | datetime | NOT NULL | Cancellation date |
| fecha_ultimo_avance_usada | datetime | NULL | Last progress date used |

**Constraint:** UNIQUE on proyecto_id

---

### `acuerdo_defensa_publica`

Public defense scheduling agreements.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Agreement ID |
| proyecto_id | int(11) | FK → proyecto_aprobado | Project ID |
| proposal_id | int(11) | NOT NULL | Proposal ID |
| codigo_acuerdo | varchar(30) | NOT NULL | Agreement code |
| fecha_aprobacion_documento_final | date | NOT NULL | Final document approval date |
| fecha_defensa | date | NOT NULL | Defense date |
| archivo_nombre | varchar(255) | NOT NULL | Document filename |
| archivo_ruta | varchar(500) | NOT NULL | Document path |
| mime_type | varchar(100) | NOT NULL DEFAULT 'application/pdf' | MIME type |
| file_size | bigint | NOT NULL DEFAULT 0 | File size |
| enviado_correo | tinyint(1) | NOT NULL DEFAULT 0 | Email sent flag |
| correo_destino | varchar(255) | NULL | Destination email |

---

## 13. Chat System

### `chat_conversations`

Chat conversation threads.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Conversation ID |
| project_id | int(11) | FK → registered_projects | Project ID (optional) |
| created_by | varchar(50) | FK → sis_user | Creator ID |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| updated_at | datetime | DEFAULT CURRENT_TIMESTAMP | Last update |
| title | varchar(200) | NULL | Conversation title |
| is_group | tinyint(1) | DEFAULT 0 | Group chat flag |

---

### `chat_participants`

Chat participants.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| conversation_id | int(11) | FK → chat_conversations | Conversation ID |
| user_id | varchar(50) | FK → sis_user | User ID |
| joined_at | datetime | DEFAULT CURRENT_TIMESTAMP | Join timestamp |
| role | varchar(50) | DEFAULT 'member' | Role (admin/member) |

**PK:** Composite (conversation_id, user_id)

---

### `chat_messages`

Chat messages.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Message ID |
| conversation_id | int(11) | FK → chat_conversations | Conversation ID |
| sender_id | varchar(50) | FK → sis_user | Sender ID |
| message | text | NOT NULL | Message content |
| message_type | varchar(20) | DEFAULT 'text' | Type (text/file/image) |
| file_path | varchar(500) | NULL | Attached file path |
| sent_at | datetime | DEFAULT CURRENT_TIMESTAMP | Sent timestamp |
| read_by | text | NULL | Read user IDs (JSON) |

**Indexes:** idx_conversation_id, idx_sender_id, idx_sent_at

---

## 14. Templates & Minutes

### `plantillas_oficiales`

Official document templates.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Template ID |
| nombre | varchar(200) | NOT NULL | Template name |
| descripcion | varchar(500) | NULL | Description |
| tipo | varchar(50) | NOT NULL | Template type |
| contenido | text | NULL | Template content |
| archivo_ruta | varchar(500) | NULL | File path |
| activo | tinyint(1) | DEFAULT 1 | Active flag |
| creado_por | varchar(50) | FK → sis_user | Creator ID |
| creado_en | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| actualizado_por | varchar(50) | NULL | Updater ID |
| actualizado_en | datetime | NULL | Update timestamp |

---

### `project_minutes`

Meeting minutes.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Minutes ID |
| project_id | int(11) | FK → registered_projects | Project ID |
| titulo | varchar(200) | NOT NULL | Meeting title |
| fecha_reunion | datetime | NOT NULL | Meeting date |
| lugar | varchar(200) | NULL | Location |
| contenido | text | NOT NULL | Meeting notes |
| acuerdos | text | NULL | Agreements/action items |
| creado_por | varchar(50) | FK → sis_user | Creator ID |
| creado_en | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

---

### `project_minute_attendees`

Meeting attendees.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| minute_id | int(11) | FK → project_minutes | Minutes ID |
| usuario_id | varchar(50) | FK → sis_user | User ID |
| asistencia | tinyint(1) | DEFAULT 1 | Attendance flag |
| rol | varchar(50) | NULL | Role in meeting |

**PK:** Composite (minute_id, usuario_id)

---

## 15. Google Calendar

### `google_calendar_tokens`

OAuth tokens for Google Calendar API.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Token ID |
| user_id | varchar(50) | FK → sis_user | User ID |
| access_token | text | NOT NULL | OAuth access token |
| refresh_token | text | NULL | OAuth refresh token |
| expires_at | datetime | NOT NULL | Expiration timestamp |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |
| updated_at | datetime | DEFAULT CURRENT_TIMESTAMP | Last update |

**Constraint:** UNIQUE on user_id

---

### `google_calendar_sync_log`

Calendar sync operation logs.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Log ID |
| user_id | varchar(50) | NOT NULL | User ID |
| operation | varchar(50) | NOT NULL | Operation type (create/update/delete) |
| event_id | varchar(100) | NULL | Google Calendar event ID |
| project_id | int(11) | NULL | Related project ID |
| status | varchar(50) | NOT NULL | Operation status |
| error_message | text | NULL | Error details |
| executed_at | datetime | DEFAULT CURRENT_TIMESTAMP | Execution timestamp |

**Indexes:** idx_user_id, idx_project_id, idx_executed_at

---

## 16. Archives

### `tfg_proposals_archive`

Archived TFG proposals.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Archive ID |
| original_id | int(11) | NOT NULL | Original proposal ID |
| user_id | varchar(50) | NOT NULL | User ID |
| title | varchar(255) | NOT NULL | Project title |
| disciplines | varchar(255) | NOT NULL | Disciplines |
| project_description | text | NULL | Description |
| file_name | varchar(255) | NOT NULL | Filename |
| mime_type | varchar(100) | NOT NULL | MIME type |
| file_size | int(11) | NOT NULL | Size |
| status | varchar(50) | NOT NULL | Status |
| archived_at | datetime | NOT NULL | Archive timestamp |
| archived_by | varchar(50) | NOT NULL | Archived by |
| archive_reason | text | NULL | Archive reason |

---

### `registered_projects_archive`

Archived project registrations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Archive ID |
| original_id | int(11) | NOT NULL | Original project ID |
| tfg_proposal_id | int(11) | NOT NULL | Proposal ID |
| project_type_id | int(11) | NOT NULL | Type ID |
| status | varchar(50) | NOT NULL | Status |
| start_date | date | NULL | Start date |
| end_date | date | NULL | End date |
| final_grade | decimal(3,1) | NULL | Grade |
| supervisor_id | varchar(50) | NULL | Supervisor ID |
| archived_at | datetime | NOT NULL | Archive timestamp |

---

### `project_members_archive`

Archived project members.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Archive ID |
| original_id | int(11) | NOT NULL | Original member ID |
| project_id | int(11) | NOT NULL | Project ID |
| user_id | varchar(50) | NOT NULL | User ID |
| role | varchar(50) | NOT NULL | Role |
| joined_at | datetime | NOT NULL | Join date |
| archived_at | datetime | NOT NULL | Archive timestamp |

---

### `tfg_files_archive`

Archived project files.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Archive ID |
| original_id | int(11) | NOT NULL | Original file ID |
| project_id | int(11) | NOT NULL | Project ID |
| file_name | varchar(255) | NOT NULL | Filename |
| file_path | varchar(500) | NOT NULL | Storage path |
| mime_type | varchar(100) | NOT NULL | MIME type |
| file_size | bigint | NOT NULL | Size |
| uploaded_by | varchar(50) | NOT NULL | Uploader ID |
| uploaded_at | datetime | NOT NULL | Upload date |
| archived_at | datetime | NOT NULL | Archive timestamp |

---

### `archive_audit_log`

Archive operation audit trail.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Log ID |
| table_name | varchar(100) | NOT NULL | Archived table |
| record_id | int(11) | NOT NULL | Record ID |
| operation | varchar(50) | NOT NULL | Operation (archive/restore/delete) |
| user_id | varchar(50) | NOT NULL | Operator ID |
| timestamp | datetime | NOT NULL | Operation timestamp |
| details | text | NULL | Additional details |

---

### `hu041_committee_audit`

Committee decision audit (HU041).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Audit ID |
| proposal_id | int(11) | NOT NULL | Proposal ID |
| comite_id | int(11) | NOT NULL | Committee ID |
| decision | varchar(50) | NOT NULL | Decision |
| decision_by | varchar(50) | NOT NULL | Decider ID |
| decision_date | datetime | NOT NULL | Decision date |
| comments | text | NULL | Comments |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

---

## 17. Password Recovery

### `password_recovery_tokens`

Password reset tokens.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | int(11) | PK, AUTO_INCREMENT | Token ID |
| user_id | varchar(50) | FK → sis_user | User ID |
| token | varchar(255) | NOT NULL | Reset token (hashed) |
| expires_at | datetime | NOT NULL | Expiration timestamp |
| used_at | datetime | NULL | Used timestamp |
| ip_address | varchar(45) | NULL | Requester IP |
| created_at | datetime | DEFAULT CURRENT_TIMESTAMP | Creation timestamp |

**Indexes:** idx_user_id, idx_token

---

## Entity Relationship Summary

```
sis_login ←──── sis_user
    ↓               ↓
sis_rolls ───→ sis_permits ───→ sis_mod ←── sis_mod_actions
                                    ↓
                              sis_sessions ←── sis_sessions_vars

tfg_proposals ←─ project_types
       ↓                    ↓
registered_projects ← project_members
       ↓                    ↓
proyecto_aprobado ←── proyecto_aprobado_estudiantes
       ↓
proyecto_notas / acuerdo_cancelacion / acuerdo_defensa_publica

tfg_files ← tfg_final_documents ← tfg_document_reviews
                              ↓
                        tfg_project_timeline

tfg_notifications ← user_alerts / deadline_alerts_sent
                         ↓
                   external_advisor_profile_requests ← external_advisor_linked_students

chat_conversations ← chat_participants ← chat_messages

registered_projects ← project_minutes ← project_minute_attendees
                          ↓
                    google_calendar_tokens ← google_calendar_sync_log
```

---

## Indexes Overview

| Table | Indexes |
|-------|---------|
| sis_log | user_date, action_result_date, date |
| sis_sessions | sid (PK) |
| tfg_proposals | user_id, status, title, created |
| registered_projects | tfg_proposal (UK), project_type, status, supervisor |
| tfg_files | project_id, proposal_id, uploaded_by |
| chat_messages | conversation_id, sender_id, sent_at |
| google_calendar_sync_log | user_id, project_id, executed_at |

---

## Triggers

1. **sis_log**: Prevent UPDATE/DELETE (immutable)
2. **registered_projects**: Audit start_date/end_date changes
3. **tfg_extension_requests**: Audit request_date/response_date changes

---

## Data Retention

- **sis_log**: Immutable - requires special permission to delete
- **Archives**: 5-year retention recommended
- **Chat messages**: 1-year retention recommended
- **Sessions**: Auto-expire based on `expires` field
- **Password recovery tokens**: 24-hour expiration