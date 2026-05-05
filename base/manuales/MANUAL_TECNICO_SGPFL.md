# Manual Técnico del Sistema SGPFL
**Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL)**  
**Repositorio:** `RodrigoUC/SGPFL`  
**Fecha:** 2026-05-05  

---

## Índice
1. [Introducción](#1-introducción)
2. [Alcance y público objetivo](#2-alcance-y-público-objetivo)
3. [Arquitectura general](#3-arquitectura-general)
4. [Requisitos y dependencias](#4-requisitos-y-dependencias)
5. [Estructura del proyecto](#5-estructura-del-proyecto)
6. [Configuración del sistema](#6-configuración-del-sistema)
7. [Subsistemas principales](#7-subsistemas-principales)
8. [Base de datos (resumen)](#8-base-de-datos-resumen)
9. [Operación y mantenimiento](#9-operación-y-mantenimiento)
10. [Pruebas](#10-pruebas)
11. [Despliegue](#11-despliegue)
12. [Diagramas sugeridos y capturas](#12-diagramas-sugeridos-y-capturas)
13. [Referencias](#13-referencias)

---

## 1. Introducción
SGPFL es un sistema web para la gestión del ciclo completo de proyectos finales de licenciatura. Centraliza el registro de propuestas, el flujo de revisión, la gestión de documentos y la comunicación entre estudiantes, asesores y la Comisión de TFG (CTFG). También integra servicios externos como LDAP, correo SMTP y Google Calendar.

Este manual técnico describe la arquitectura, estructura del código, configuración y operación del sistema con base en la implementación real del repositorio.

---

## 2. Alcance y público objetivo
**Público objetivo:**
- Administradores técnicos responsables del despliegue y mantenimiento.
- Personal de soporte que atiende incidentes y operatividad diaria.
- Desarrolladores que requieren entender la estructura y módulos del sistema.

**Alcance:**
- Arquitectura general y componentes.
- Configuración técnica y dependencias.
- Descripción de módulos y flujo de negocio.
- Resumen de base de datos y procesos críticos.

---

## 3. Arquitectura general
### 3.1 Componentes
El sistema sigue una arquitectura web clásica con servidor Apache/PHP, base de datos MariaDB y autenticación LDAP externa.

```
Cliente (Navegador)
        │ HTTPS
        ▼
Servidor Web (Apache + PHP)
        │         │
        │         ├── LDAP (autenticación)
        │         ├── SMTP (notificaciones)
        │         └── Google Calendar (OAuth2)
        ▼
Base de Datos (MariaDB)
```

### 3.2 Flujo de datos principal
1. El usuario accede al sistema y se autentica (LDAP o credenciales locales).
2. PHP procesa la solicitud y consulta la base de datos.
3. Se generan notificaciones internas y correos según eventos del flujo TFG.
4. Documentos y actas se almacenan en el sistema de archivos y/o base de datos.

---

## 4. Requisitos y dependencias
### 4.1 Stack principal
| Componente | Requisito |
|-----------|-----------|
| PHP | 7.4+ (recomendado 8.4) |
| Base de datos | MariaDB/MySQL 5.7+ (recomendado MariaDB 10.11) |
| Servidor web | Apache 2.4+ o Nginx |
| LDAP | LDAPv3 (OpenLDAP/AD externo) |
| SMTP | Servidor de correo institucional |

### 4.2 Dependencias Composer
| Paquete | Uso |
|---------|-----|
| `google/apiclient` | Integración con Google Calendar |
| `phpmailer/phpmailer` | Envío de correos |
| `php-webdriver/webdriver` | Pruebas funcionales |

### 4.3 Herramientas de pruebas
| Herramienta | Uso |
|-------------|-----|
| PHPUnit | Pruebas unitarias e integración |
| Codeception | Pruebas funcionales |

---

## 5. Estructura del proyecto
Ruta base de la aplicación: `/base/`

| Ruta | Propósito |
|------|-----------|
| `config.inc` | Configuración global (LDAP, correo, Google Calendar, módulos) |
| `inc/db/bdcommon.inc` | Parámetros de conexión a la base de datos |
| `inc/` | Lógica compartida, CSS/JS, utilidades |
| `mod/` | Módulos de autenticación y funcionalidades por rol |
| `panel_*.php` | Paneles por rol (estudiante, gestor, CTFG, asesor, etc.) |
| `service/` | Servicios (Google Calendar, integraciones) |
| `documentation/` | Documentación técnica detallada |
| `manuales/` | Manuales de despliegue y soporte |
| `files/` / `uploads/` | Archivos subidos y documentos |

**Puntos de entrada principales:**
- `index.php` redirige a `login.php`.
- `login.php` inicia la autenticación y la sesión de usuario.

---

## 6. Configuración del sistema
### 6.1 `config.inc`
Define parámetros clave:
- Dominio y rutas dinámicas (`$cds_domain`, `$cds_locate`).
- LDAP (`LDAP_HOST`, `LDAP_DN`, credenciales).
- Google Calendar (client_id, client_secret, redirect_uri).
- Correo institucional (remitente y respuesta).
- Umbrales de alertas de vencimiento (`DEADLINE_THRESHOLDS`).

### 6.2 `inc/db/bdcommon.inc`
Usa variables de entorno para conexión a la base de datos:
`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.

### 6.3 Variables de entorno relevantes
| Variable | Uso |
|----------|-----|
| `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` | Conexión a MariaDB/MySQL |
| `LDAP_HOST`, `LDAP_DN`, `LDAP_USER`, `LDAP_PASS` | Conexión a LDAP |
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` | OAuth Google |
| `GOOGLE_TOKEN_CIPHER_KEY` | Cifrado de tokens de Google |

---

## 7. Subsistemas principales
### 7.0 Organización del código y flujo base
- **Entrada:** `index.php` redirige a `login.php`.
- **Configuración:** `config.inc` y `inc/db/bdcommon.inc` cargan parámetros globales.
- **Funciones comunes:** `functions.php` incluye utilidades transversales como validación de permisos.
- **Carga de assets:** `includes.php` centraliza CSS/JS (Bootstrap, DataTables, scripts internos).
- **Layout:** `head.php`, `header.php`, `menu.php`, `footer.php` componen la estructura visual.
- **Paneles por rol:** archivos `panel_*.php` y clases `Panel*Logic.php` concentran lógica de negocio por perfil.

### 7.1 Autenticación y permisos
- LDAP es el mecanismo principal de autenticación.
- Las autorizaciones se basan en roles almacenados en `sis_rolls` y permisos en `sis_permits`.
- Funciones como `check_permiso()` validan acceso a acciones por rol.

### 7.2 Flujo TFG (negocio principal)
El flujo completo está documentado en `documentation/TFG_WORKFLOW.md` y cubre:
1. Envío de propuesta (estudiante).
2. Revisión por Gestor Académico.
3. Revisión y decisión por CTFG.
4. Registro y seguimiento del proyecto.
5. Entregas finales y defensa.

### 7.3 Gestión documental
Los documentos (propuestas, entregables, actas) se almacenan en rutas controladas por el sistema y vinculadas a registros en base de datos.  
Paneles y módulos relevantes: `panel_subir_propuesta_tfg.php`, `panel_revision_tfg.php`, `panel_committee_minutes.php`, `panel_ctfg_review_final_documents.php`.

### 7.4 Sistema de notificaciones y cron
- El cron `cron_check_deadlines.php` envía alertas de vencimiento.
- Las notificaciones internas se registran en `tfg_notifications`.
- La lógica de alertas está descrita en `documentation/CRON_JOBS.md`.

### 7.5 Chat interno
- Implementado en `chat.php` y endpoints `chat_*`.
- Registra conversaciones en `chat_conversations`, `chat_participants`, `chat_messages`.
- Documentado en `documentation/CHAT_SYSTEM.md`.

### 7.6 Asesores externos
- Registro y aprobación de asesores externos mediante formularios y panel administrativo.
- Tablas clave: `external_advisor_profile_requests`, `external_advisor_linked_students`.
- Documentado en `documentation/EXTERNAL_ADVISORS.md`.

### 7.7 Google Calendar
- OAuth2 para usuarios con sincronización de eventos.
- Servicio principal: `service/GoogleCalendarService.php`.
- Documentado en `documentation/GOOGLE_CALENDAR.md`.

---

## 8. Base de datos (resumen)
El esquema contiene **54 tablas** agrupadas por áreas:
- Autenticación y roles
- Propuestas y proyectos
- Documentos y actas
- Notificaciones y alertas
- Chat
- Integraciones externas

La descripción completa se encuentra en `documentation/DATABASE_SCHEMA.md`.

---

## 9. Operación y mantenimiento
### 9.1 Respaldos
Se recomienda respaldar periódicamente:
- Base de datos (`mysqldump`)
- Archivos críticos (`config.inc`, `inc/db/bdcommon.inc`, `uploads/`)

### 9.2 Auditoría
El sistema registra acciones en `sis_log` con trazabilidad de usuario, IP y resultado.

### 9.3 Seguridad
- Accesos restringidos por rol y módulo.
- Uso de variables de entorno para credenciales.
- Control de rutas y extensiones permitidas en archivos.

---

## 10. Pruebas
Scripts disponibles en `composer.json`:
- `composer test` / `composer test:unit`
- `composer test:integration`
- `composer test:functional`

Manual adicional: `manuales/MANUAL_CODECEPTION_FUNCIONAL.md`.

---

## 11. Despliegue
Existen dos modalidades:
1. **Despliegue manual en producción:** `documentation/MANUAL_DEPLOYMENT.md`
2. **Entorno Docker (desarrollo):** `documentation/DOCKER_DEPLOYMENT.md` y `manuales/DOCKER_SISTEMA_GUIDE.md`

---

## 12. Diagramas sugeridos y capturas
**Diagramas recomendados:**
1. Arquitectura general (cliente → servidor → DB/LDAP/SMTP).
2. Flujo TFG por etapas (ver `TFG_WORKFLOW.md`).
3. Flujo de cron de vencimientos (ver `CRON_JOBS.md`).
4. Diagrama de módulos y roles (RBAC).

**Capturas sugeridas (si se aportan):**
- Pantalla de login.
- Panel del estudiante.
- Panel del gestor académico.
- Panel de revisión CTFG.
- Pantalla de chat.

---

## 13. Referencias
- `README.md` (raíz del repositorio)
- `documentation/TFG_WORKFLOW.md`
- `documentation/DATABASE_SCHEMA.md`
- `documentation/CRON_JOBS.md`
- `documentation/CHAT_SYSTEM.md`
- `documentation/EXTERNAL_ADVISORS.md`
- `documentation/GOOGLE_CALENDAR.md`
- `documentation/MANUAL_DEPLOYMENT.md`
- `documentation/DOCKER_DEPLOYMENT.md`
- `manuales/DOCKER_SISTEMA_GUIDE.md`
- `manuales/INSTALL_QUICK_GUIDE.md`
