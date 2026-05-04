# TFG Workflow Documentation

## Overview

This document describes the end-to-end workflow for the Trabajo de Graduation (TFG/Thesis) process in the SGPFL system.

---

## Workflow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           TFG WORKFLOW                                      │
└─────────────────────────────────────────────────────────────────────────────┘

    ┌──────────┐     ┌─────────────┐     ┌──────────────┐     ┌─────────────┐
    │ ESTUDIANTE│     │ GESTOR      │     │ COMISIÓN     │     │ ASESOR      │
    │ (Rol 4)   │     │ ACADÉMICO   │     │ CTFG (Rol 3) │     │ (Rol 5)     │
    │           │     │ (Rol 2)     │     │              │     │             │
    └─────┬─────┘     └──────┬──────┘     └──────┬───────┘     └──────┬──────┘
          │                  │                   │                   │
          │                  │                   │                   │
          ▼                  ▼                   ▼                   ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 1: PROPOSAL SUBMISSION                                           │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. Student submits TFG proposal (title, disciplines, PDF document)    │
    │  2. Status: "Pendiente de Revisión"                                    │
    │  3. Notification sent to Gestor Académico                              │
    └──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 2: GESTOR REVIEW                                                 │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. Gestor reviews proposal                                            │
    │  2. Gestor can:                                                        │
    │     - Request corrections (status: "No cumple requisitos")             │
    │     - Approve and assign to Committee (status: "Cumple requisitos")    │
    │  3. If approved → Notification to CTFG                                │
    └──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 3: COMMITTEE REVIEW (CTFG)                                       │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. CTFG evaluates proposal                                            │
    │  2. CTFG can:                                                          │
    │     - Approve → Create "registered_projects" record                   │
    │     - Request revisions                                                │
    │     - Reject                                                           │
    │  3. If approved → Assign supervisor (Asesor)                          │
    └──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 4: PROJECT REGISTRATION                                          │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. Project created in "registered_projects"                          │
    │  2. Status: "Registrado" → "En Desarrollo"                             │
    │  3. Supervisor assigned                                                │
    │  4. Project timeline created with milestones                           │
    │  5. Project members added                                             │
    └──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 5: PROJECT DEVELOPMENT                                          │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. Student uploads deliverables (proposals, documents, reports)       │
    │  2. Files stored in: /files/tfg/[project_id]/[version]/               │
    │  3. Asesor can add notes/comments                                       │
    │  4. Progress tracked via milestones                                   │
    │  5. Possible events:                                                    │
    │     - Extension request (prórroga)                                    │
    │     - Cancellation request (cancelación)                               │
    │     - Advisor change                                                   │
    └──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 6: FINAL DOCUMENT SUBMISSION                                    │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. Student submits final thesis document                              │
    │  2. Document stored in "tfg_final_documents"                           │
    │  3. Status: "pending" → CTFG reviews                                   │
    │  4. Reviews stored in "tfg_document_reviews"                          │
    │  5. Decision: "approved" / "needs_revision" / "rejected"              │
    │  6. If rejected → Return to Stage 5                                    │
    └──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 7: DEFENSE SCHEDULING                                           │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. If approved → Create "acuerdo_defensa_publica"                    │
    │  2. Set defense date and approval date                                  │
    │  3. Generate agreement document                                        │
    │  4. Email sent to student                                              │
    │  5. Calendar event created (Google Calendar integration)              │
    └──────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
    ┌──────────────────────────────────────────────────────────────────────────┐
    │  STAGE 8: PROJECT COMPLETION                                           │
    │  ──────────────────────────────────────────────────────────────────────  │
    │  1. Final grade assigned                                              │
    │  2. Status: "Aprobado"                                                 │
    │  3. Project archived (if retention period expires)                    │
    │  4. Audit trail maintained for compliance                             │
    └──────────────────────────────────────────────────────────────────────────┘
```

---

## User Roles & Permissions

| Role | ID | Permissions |
|------|----|-------------|
| Administrador | 1 | Full system access, user management, reports |
| Gestor Académico | 2 | Proposal review, committee assignment, reporting |
| Comisión CTFG | 3 | Committee review, final document approval, scheduling |
| Estudiante | 4 | Submit proposals, upload documents, view status |
| Asesor | 5 | Review student work, add notes, monitor progress |

---

## Stage Details

### Stage 1: Proposal Submission

**Actor:** Estudiante (Student)

**Steps:**
1. Student logs in to the system
2. Navigate to "Panel de Estudiante" → "Subir Propuesta TFG"
3. Fill in proposal form:
   - **Title**: Project title (max 255 chars)
   - **Disciplines**: Select relevant categories
   - **Description**: Project description
   - **Document**: Upload PDF (max size: 25MB)
4. Submit proposal
5. System creates record in `tfg_proposals` with status "Pendiente de Revisión"
6. Notification sent to Gestor Académico

**Database Tables:**
- `tfg_proposals` - Stores proposal data
- `tfg_notifications` - Sends notification
- `tfg_project_timeline` - Creates initial milestone

**Filesystem:**
- Documents stored in `/files/tfg/proposals/[proposal_id]/`

---

### Stage 2: Gestor Review

**Actor:** Gestor Académico

**Steps:**
1. Gestor views pending proposals in panel
2. Reviews proposal details and document
3. Decision options:
   - **Approve**: Status → "Cumple requisitos", assigns to Committee
   - **Request Corrections**: Status → "No cumple requisitos", comments added
   - **Reject**: Status → "Rechazado"
4. Add admin comments if needed
5. Record reviewer ID and timestamp in `tfg_proposals.reviewed_by` and `reviewed_at`
6. If approved → Notification to CTFG

**Files:** `panel_gestor.php`, `PanelGestorLogic.php`

---

### Stage 3: Committee Review (CTFG)

**Actor:** Comisión CTFG

**Steps:**
1. CTFG member reviews approved proposals
2. Evaluates based on committee criteria
3. Decision:
   - **Approve**: Create `registered_projects` entry
   - **Request Revisions**: Status → "No cumple requisitos"
   - **Reject**: Status → "Rechazado"
4. If approved:
   - Assign to project type
   - Set start date
   - Assign supervisor (Asesor)
   - Status → "Registrado"
5. Audit decision in `hu041_committee_audit`

**Database Tables:**
- `registered_projects` - New project record
- `project_members` - Add student as member
- `hu041_committee_audit` - Committee decision audit

---

### Stage 4: Project Registration

**Actor:** System (automated on CTFG approval)

**Steps:**
1. Create `registered_projects` record
2. Generate unique project identifier
3. Create initial project timeline with milestones:
   - Proposal defense
   - Progress report 1
   - Progress report 2
   - Final document submission
   - Defense date
4. Assign supervisor from available Asesores
5. Notification sent to student and advisor

**Timeline Milestones:** `tfg_project_timeline`
- milestone_name, due_date, status, completed_date

---

### Stage 5: Project Development

**Actors:** Estudiante, Asesor

**Student Actions:**
- Upload intermediate documents
- Submit progress reports
- Request extension (prórroga)
- Request cancellation (with valid reason)
- Chat with advisor and committee

**Advisor Actions:**
- View uploaded documents
- Add notes/comments (`proyecto_notas`)
- Monitor timeline progress
- Request revisions

**Extension Request (Prórroga):**
- `tfg_extension_requests` table
- Extension 1: +1 year
- Extension 2: +6 months
- Requires Gestor approval
- Audited in `auditoria_cambios_fecha`

**Cancellation:**
- `acuerdo_cancelacion` table
- Requires valid reason
- Uses last progress date for final record

---

### Stage 6: Final Document Submission

**Actor:** Estudiante (final stage)

**Steps:**
1. Student uploads final thesis document
2. Document stored in `tfg_final_documents`
3. CTFG reviews document
4. Review recorded in `tfg_document_reviews`
5. Decision:
   - **approved**: Proceed to defense scheduling
   - **needs_revision**: Return to student
   - **rejected**: Project fails

**Database Tables:**
- `tfg_final_documents` - Stores final thesis
- `tfg_document_reviews` - Review records

**File Storage:**
- `/files/tfg/final/[project_id]/[timestamp]_filename.pdf`

---

### Stage 7: Defense Scheduling

**Actor:** CTFG

**Steps:**
1. CTFG creates `acuerdo_defensa_publica`
2. Set defense date
3. Set final document approval date
4. Generate agreement document
5. Send email notification to student
6. Create Google Calendar event (if connected)

**Database Table:**
- `acuerdo_defensa_publica` - Defense agreement
- `google_calendar_sync_log` - Calendar sync record

---

### Stage 8: Project Completion

**Actors:** CTFG, System

**Steps:**
1. Defense completed successfully
2. CTFG assigns final grade
3. Project status → "Aprobado"
4. Final grade stored in `registered_projects.final_grade`
5. Generate final project document (`proyecto_aprobado`)
6. Archive project after retention period (5 years)

**Archive Tables:**
- `tfg_proposals_archive`
- `registered_projects_archive`
- `project_members_archive`
- `tfg_files_archive`
- `archive_audit_log`

---

## API Endpoints & Key Files

### Student Panel
- `panel_estudiante.php` - Main student dashboard
- `PanelEstudianteLogic.php` - Student data logic
- `panel_subir_propuesta_tfg.php` - Proposal submission
- `mod/admin/users/tfg_upload.php` - Upload form handler
- `panel_student_summary.php` - Project summary

### Gestor Panel
- `panel_gestor.php` - Main gestor dashboard
- `PanelGestorLogic.php` - Gestor data logic
- `panel_revision_tfg.php` - Proposal review interface

### CTFG Panel
- `panel_ctfg.php` - Main committee dashboard
- `PanelCTFGLogic.php` - CTFG data logic
- `panel_ctfg_review_final_documents.php` - Final doc review

### Advisor Panel
- `panel_asesor.php` - Advisor dashboard
- `PanelAsesorLogic.php` - Advisor data logic

### Common Files
- `cron_check_deadlines.php` - Deadline monitoring
- `descargar_archivo.php` - File download
- `historial_documentos.php` - Document history
- `chat.php` - Messaging system

---

## Notifications

**Notification Types:**
| Type | Trigger | Recipient |
|------|---------|-----------|
| proposal_submitted | Student submits proposal | Gestor |
| proposal_approved | Gestor approves | CTFG |
| project_registered | CTFG approves project | Student, Asesor |
| document_submitted | Student uploads document | Asesor, CTFG |
| document_approved | CTFG approves final doc | Student |
| defense_scheduled | Defense date set | Student |
| deadline_reminder | 30/15/7 days before deadline | Student, Asesor |
| extension_requested | Student requests extensión | Gestor |
| extension_approved | Gestor approves extensión | Student |

**Table:** `tfg_notifications`

---

## File Storage Structure

```
/files/
├── tfg/
│   ├── proposals/
│   │   └── [proposal_id]/
│   │       └── proposal_[timestamp].pdf
│   ├── projects/
│   │   └── [project_id]/
│   │       ├── v1/
│   │       │   ├── document.pdf
│   │       │   └── report.pdf
│   │       └── v2/
│   │           └── ...
│   ├── final/
│   │   └── [project_id]/
│   │       └── [timestamp]_final_thesis.pdf
│   └── defenses/
│       └── [project_id]/
│           └── defense_agreement.pdf
```

---

## Error Handling

| Scenario | Response |
|----------|----------|
| Invalid file type | Show error, reject upload |
| File too large | Show size limit error |
| Proposal already exists | Prevent duplicate submission |
| Unauthorized access | Redirect to login |
| Database connection fail | Show error page |
| Session expired | Redirect to login |

---

## Timeline Example

```
Project: "Sistema de Gestión de Proyectos"

┌────────────────────────────────────────────────────────────┐
│ TIMELINE                                                    │
├─────────────────┬──────────────────────────┬───────────────┤
│ Milestone       │ Due Date    │ Status     │ Completed    │
├─────────────────┼──────────────────────────┼───────────────┤
│ Propuesta       │ 2024-03-15  │ Completed   │ 2024-03-10   │
│ Avance 1        │ 2024-06-15  │ Completed   │ 2024-06-12   │
│ Avance 2        │ 2024-09-15  │ Completed   │ 2024-09-14   │
│ Entrega Final   │ 2024-12-15  │ Pending     │ -            │
│ Defensa         │ 2025-01-15  │ Scheduled   │ -            │
└─────────────────┴──────────────────────────┴───────────────┘
```

---

## Extension (Prórroga) Flow

```
Student Request
       │
       ▼
┌─────────────────┐
│ tfg_extension   │
│ _requests       │
│ (pendiente)     │
└────────┬────────┘
         │
         ▼
   Gestor Review
         │
    ┌────┴────┐
    ▼         ▼
Aprueba    Rechaza
    │         │
    ▼         ▼
Update     Update
fecha_     status:
actualizada  rechazado
    │         
    ▼         
Audited in
auditoria_
cambios_fecha
```

---

## Cancellation Flow

```
Student Request
       │
       ▼
┌─────────────────┐
│ Cancelar        │
│ Proyecto        │
└────────┬────────┘
         │
         ▼
   Gestor Review
         │
    ┌────┴────┐
    ▼         ▼
Aprueba    Rechaza
    │         │
    ▼         ▼
Create      Reject
acuerdo_
cancelacion
    │
    ▼
Update
proyecto_
aprobado
estado =
CANCELADO
```

---

## Audit Trail

All date changes are tracked in `auditoria_cambios_fecha`:

**Triggers:**
- `trg_audit_registered_projects_dates` - Monitors `registered_projects.start_date` and `end_date`
- `trg_audit_extension_requests_dates` - Monitors `tfg_extension_requests.request_date` and `response_date`

**Logged Fields:**
- tabla_origen, id_registro, campo_modificado
- valor_anterior, valor_nuevo
- modificado_por, fecha_modificacion, descripcion

---

## Reports Available

| Report | Access | Description |
|--------|--------|--------------|
| Pending Proposals | Gestor, Admin | All proposals awaiting review |
| Project Status | All | Current status of all projects |
| Extensions | Gestor, Admin | All extension requests |
| Final Documents | CTFG, Admin | Final submissions pending review |
| Defense Schedule | CTFG, Admin | Upcoming defenses |
| Deadlines | Gestor, Asesor | Project deadline tracking |
| Calendar Sync | Admin | Google Calendar sync status |