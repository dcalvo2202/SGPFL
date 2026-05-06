# Mapa inicial del repositorio SGPFL

**Sistema:** SGPFL — Sistema Gestor de Proyectos Finales de Licenciatura  
**Etapa del manual:** Prompt 1 de 5 — Exploración de estructura real del repositorio  
**Fecha de levantamiento:** 2026-05-05  
**Fuente:** inspección directa del repositorio local `/workspace/SGPFL`

---

## 1. Diagnóstico ejecutivo

El repositorio corresponde a una aplicación web PHP tradicional, organizada principalmente bajo la carpeta `base/`. La implementación combina páginas PHP de nivel superior, módulos administrativos bajo `base/mod/`, funciones compartidas bajo `base/inc/`, servicios con autoload PSR-4 bajo `base/service/`, esquema SQL MySQL, pruebas PHPUnit/Codeception, documentación operativa existente y archivos Docker para entorno local.

La estructura evidencia un sistema monolítico con separación parcial por responsabilidades. Existen intentos de modularización moderna en `service/` y en funciones auxiliares de `inc/`, pero también hay lógica de presentación, consulta SQL, sesión y reglas de negocio mezcladas en páginas PHP. Este punto debe tratarse con cuidado en el manual: no conviene describirlo como Clean Architecture ni como MVC estricto; lo más preciso, por ahora, es documentarlo como **monolito PHP modular por páginas y módulos funcionales**, con algunos servicios desacoplados.

---

## 2. Alcance de esta exploración

Esta primera etapa cubre:

- Carpetas principales.
- Tecnologías utilizadas.
- Backend y frontend detectados.
- Base de datos.
- Configuración.
- Rutas/puntos de entrada.
- Controladores/páginas de proceso.
- Servicios.
- Modelos/entidades inferidas desde SQL y clases.
- Seguridad/autenticación.
- Pruebas.
- Scripts de despliegue.
- Dependencias principales.
- Primer mapa de módulos.
- Riesgos y huecos de documentación.
- Propuesta de índice maestro para el manual técnico.

---

## 3. Mapa inicial de carpetas y archivos principales

| Ruta | Tipo | Descripción inicial | Evidencia observada |
|---|---|---|---|
| `README.md` | Documentación raíz | Guía general de configuración, despliegue, LDAP, base de datos y correo. | Documento raíz existente. |
| `PLAN_DEPLOYMENT.md` | Documentación de despliegue | Plan para servidor Linux, requisitos, Apache, PHP, MySQL, LDAP y SMTP. | Documento raíz existente. |
| `base/` | Aplicación principal | Contiene la aplicación PHP, SQL, recursos, pruebas, documentación, vendor y configuración. | Carpeta dominante del sistema. |
| `base/index.php` | Entrada mínima | Redirige hacia `login.php`. | Punto inicial HTTP. |
| `base/login.php` | Entrada de autenticación | Vista/página de inicio de sesión. | Página superior. |
| `base/main.php` | Contenedor principal | Carga sesión, menú, recursos JS/CSS y contenedor dinámico. | Página principal posterior al login. |
| `base/config.inc` | Configuración de aplicación | Dominio/rutas dinámicas, LDAP, módulos/acciones, Google Calendar, correo y alertas de vencimiento. | Archivo de configuración central. |
| `base/inc/db/bdcommon.inc` | Configuración de BD | Lee variables de entorno `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` y define `base_url`. | Configuración MySQL. |
| `base/inc/db/db.php` | Acceso a datos procedural | Funciones de consulta/transacción, incluyendo variantes con prepared statements. | Capa de acceso a datos compartida. |
| `base/inc/` | Funciones compartidas | Alertas, chat, archivo histórico, deadlines, correo, validaciones HU041, plantillas, estudiantes, TFG y uploads. | Librería interna procedural. |
| `base/mod/` | Módulos internos | Login, administración, permisos, roles, usuarios, auditoría, proyectos y procesos de TFG. | Módulos por carpeta. |
| `base/service/` | Servicios PHP PSR-4 | Servicio de Google Calendar y servicio/reglas/repositorio/validador de cancelaciones. | Namespace `Service\` definido en Composer. |
| `base/auth/` | Integración OAuth Google | Inicio, callback y desconexión de Google Calendar. | Usa `GoogleCalendarService`. |
| `base/sql/` | SQL complementario | Script adicional de mensajería HU029. | SQL modular. |
| `base/base.sql` | Esquema principal | Esquema y datos base de MySQL. | Contiene tablas de seguridad, proyectos, TFG, chat, plantillas y Google Calendar. |
| `base/base_add_password_recovery_table.sql` | SQL incremental | Tabla para recuperación de contraseña. | Script incremental. |
| `base/docker/` | Docker aplicación/BD | Compose de app PHP/Apache y MySQL, Dockerfile PHP, php.ini, msmtp y my.cnf. | Entorno containerizado. |
| `base/ldap-docker/` | Docker LDAP | Compose, Dockerfile y LDIF bootstrap para LDAP de desarrollo. | Entorno LDAP local. |
| `base/tests/` | Pruebas | Pruebas unitarias, integración y funcionales Codeception. | Cobertura por historias de usuario y módulos. |
| `base/documentation/` | Documentación técnica puntual | Chat, cron jobs, esquema de BD, Docker, asesores externos, Google Calendar, workflow TFG, despliegue. | Documentación existente. |
| `base/manuales/` | Manuales existentes | Manual técnico, admin, Docker, LDAP, testing, recuperación de contraseña y guías rápidas. | Documentación formal y operativa previa. |
| `base/uploads/` | Archivos cargados | Propuestas TFG y prórrogas. | Directorio de escritura en runtime. |
| `base/templates/` | Plantillas | Plantillas y temporales para DOCX. | Soporte para documentos. |
| `base/fpdf186/` | Librería embebida | Generación PDF con FPDF. | Dependencia local vendorizada. |
| `base/lib/` | Librerías frontend/PHP legacy | Bootstrap, DataTables, Font Awesome, SweetAlert2, AuthLdap, dompdf, calendar, mysession, etc. | Dependencias incluidas en el repo. |
| `base/vendor/` | Dependencias Composer | Dependencias instaladas por Composer. | Carpeta vendor incluida. |

---

## 4. Tecnologías detectadas

### 4.1 Backend

| Tecnología | Uso detectado | Evidencia |
|---|---|---|
| PHP | Lenguaje principal de la aplicación. | Aproximadamente 280 archivos PHP fuera de `vendor` y `lib`. |
| PHP procedural | Patrón dominante en páginas, includes y funciones globales. | `functions.php`, `inc/db/db.php`, páginas `panel_*.php`, scripts AJAX/proceso. |
| PHP con clases puntuales | Lógica encapsulada en algunos paneles y servicios. | `PanelCTFGLogic.php`, `PanelGestorLogic.php`, `GoogleCalendarService.php`, servicios de cancelación. |
| Composer | Gestión de dependencias y autoload PSR-4 para `Service\`. | `base/composer.json`. |
| MySQL/MariaDB | Base de datos relacional principal. | `base/base.sql`, `bdcommon.inc`, `docker-compose.yml`. |
| LDAP | Autenticación/sincronización institucional o local. | `config.inc`, `lib/AuthLdap`, `ldap-docker`. |
| SMTP/msmtp/PHPMailer | Envío de correos y notificaciones. | `config.inc`, `inc/email_helper.php`, `PHPMailer`, `docker/msmtp`. |
| Google Calendar API | Integración OAuth y sincronización de calendario. | `auth/`, `service/GoogleCalendarService.php`, tablas `google_calendar_*`. |

### 4.2 Frontend

| Tecnología | Uso detectado | Observación |
|---|---|---|
| HTML renderizado por PHP | Vistas generadas desde páginas PHP. | No se detecta SPA separada. |
| JavaScript tradicional | Interactividad y llamadas dinámicas. | `inc/js/*.js`, scripts de chat y módulos. |
| jQuery | DOM/AJAX. | Incluido desde `base/includes.php`. |
| Bootstrap | Estilos y componentes UI. | Incluido desde `base/includes.php`. |
| DataTables | Listados tabulares. | Incluido desde `base/includes.php`. |
| SweetAlert2 / jquery-alerts | Alertas UI. | Incluido desde `base/includes.php`. |
| CSS propio | Paneles, chat, formularios, TFG. | `inc/css/*.css`. |

### 4.3 Pruebas y calidad

| Herramienta | Uso detectado |
|---|---|
| PHPUnit 11 | Pruebas unitarias e integración. |
| Codeception 5.3 | Pruebas funcionales/browser. |
| php-mock-phpunit | Mocking de funciones PHP. |
| Selenium/WebDriver | Dependencia de automatización browser vía `php-webdriver/webdriver`. |

### 4.4 Infraestructura

| Tecnología | Uso detectado |
|---|---|
| Apache HTTPD | Servidor web esperado; `.htaccess` configura límites y HTTPS. |
| Docker Compose | Entorno local para app y MySQL. |
| MySQL 8.0 en Docker | Servicio `db` del compose. |
| OpenLDAP en Docker | Entorno LDAP local separado. |
| msmtp | Envío de correo desde contenedor. |

---

## 5. Backend: estructura funcional inicial

El backend no está organizado como una API REST pura. La aplicación expone páginas PHP y scripts AJAX/proceso que operan como controladores. La navegación principal parece pasar por `login.php`, `main.php`, `menu.php` y páginas/paneles específicos.

### 5.1 Puntos de entrada y navegación

| Archivo | Responsabilidad probable |
|---|---|
| `base/index.php` | Redirección inicial hacia login. |
| `base/login.php` | Pantalla de autenticación. |
| `base/mod/login/ajax_login.php` | Proceso de autenticación. |
| `base/mod/login/check.php` | Control de sesión/autorización base para páginas protegidas. |
| `base/main.php` | Layout principal autenticado. |
| `base/menu.php` | Menú y control visible según permisos/rol. |
| `base/dashboard.php` | Dashboard o página de resumen. |
| `base/logout.php` y `base/mod/login/logout.php` | Cierre de sesión. |

### 5.2 Controladores/páginas de proceso detectados

El patrón recurrente del sistema es usar archivos PHP como controladores directos. Ejemplos:

- `plantilla_upload_process.php`, `plantilla_delete_process.php`, `plantilla_toggle_activo.php` para plantillas oficiales.
- `procesar_acta.php` y `generar_acta.php` para actas/documentos.
- `procesar_decision_asesor.php` para decisiones de asesores.
- `registro_process.php` para registro.
- `chat_send_message.php`, `chat_get_messages.php`, `chat_mark_read.php` y relacionados para mensajería.
- `mod/admin/users/tfg_upload_process.php`, `tfg_upload_final_process.php`, `process_final_document_review.php` y derivados para documentos TFG.
- `mod/admin/users/procesar_prorroga.php` y `responder_prorroga.php` para prórrogas.

---

## 6. Frontend: estructura inicial

No se detectó un frontend independiente tipo React/Vue/Angular. El frontend está integrado en PHP mediante HTML server-side, includes compartidos y JavaScript/CSS locales.

| Carpeta/archivo | Rol |
|---|---|
| `base/includes.php` | Carga global de jQuery, SweetAlert2, DataTables, Prototype, Bootstrap, Font Awesome, calendar y CSS propio. |
| `base/inc/js/` | JavaScript propio para login, validaciones, panel estudiante, permisos, roles, usuarios, chat y TFG. |
| `base/inc/css/` | CSS propio para estilos generales, paneles, chat, formularios, comité y TFG. |
| `base/lib/` | Librerías frontend y legacy incluidas localmente. |
| `base/head.php`, `base/header.php`, `base/footer.php` | Componentes de layout reutilizados por páginas. |

---

## 7. Base de datos

### 7.1 Archivos SQL principales

| Archivo | Propósito |
|---|---|
| `base/base.sql` | Esquema principal y datos base. |
| `base/base_add_password_recovery_table.sql` | Tabla incremental de recuperación de contraseña. |
| `base/sql/hu029_messaging_system.sql` | Script específico del sistema de mensajería/chat. |
| `base/ldap-docker/bootstrap/*.ldif` | Datos iniciales para LDAP local. |
| `base/pruebasLDAP/*.ldif` | Datos de prueba LDAP. |

### 7.2 Entidades/tablas detectadas en `base/base.sql`

Se detectaron 50 sentencias `CREATE TABLE` en el esquema principal. Hay una duplicación nominal de `user_alerts` en el archivo, por lo que el número de tablas únicas puede ser menor si se normaliza por nombre.

| Grupo | Tablas principales |
|---|---|
| Seguridad, sesiones y permisos | `sis_login`, `sis_user`, `sis_rolls`, `sis_mod`, `sis_mod_actions`, `sis_permits`, `sis_sessions`, `sis_sessions_vars`, `sis_log`. |
| Parámetros/catálogos | `sis_parametros_varios`, `sis_tipo_tel`, `categorias`, `comite`, `project_types`. |
| Propuestas/proyectos TFG | `tfg_proposals`, `registered_projects`, `project_members`, `project_history`, `proyecto_aprobado`, `proyecto_aprobado_estudiantes`. |
| Documentos TFG | `tfg_files`, `tfg_final_documents`, `tfg_document_reviews`, `tfg_project_timeline`, `tfg_proposal_history`, `proyecto_notas`. |
| Prórrogas/fechas | `tfg_extension_requests`, `deadline_alerts_sent`, `auditoria_cambios_fecha`. |
| Cancelación y defensa | `acuerdo_cancelacion`, `acuerdo_defensa_publica`. |
| Notificaciones | `user_alerts`, `tfg_notifications`. |
| Archivo histórico | `tfg_proposals_archive`, `registered_projects_archive`, `project_members_archive`, `tfg_files_archive`, `archive_audit_log`. |
| Asesores externos | `external_advisor_profile_requests`, `external_advisor_linked_students`. |
| Chat | `chat_conversations`, `chat_participants`, `chat_messages`. |
| Plantillas y actas | `plantillas_oficiales`, `project_minutes`, `project_minute_attendees`. |
| Google Calendar | `google_calendar_tokens`, `google_calendar_sync_log`. |
| Recuperación de contraseña | `password_recovery_tokens` en script incremental separado. |

---

## 8. Configuración

| Archivo | Configuración relevante |
|---|---|
| `base/config.inc` | Calcula dominio/ruta base; configura LDAP por variables de entorno; define constantes de módulos/acciones; define Google Calendar; define correo; define umbrales de alertas de vencimiento. |
| `base/inc/db/bdcommon.inc` | Configura conexión MySQL desde variables de entorno con valores por defecto. |
| `base/.htaccess` | Límites de subida/memoria/tiempo y redirección HTTPS excepto localhost/loopback. |
| `base/docker/php/php.ini` | Configuración PHP para entorno Docker. |
| `base/docker/mysql/my.cnf` | Configuración MySQL para entorno Docker. |
| `base/docker/msmtp/msmtprc` | Configuración de correo en contenedor. |
| `base/phpunit*.xml` | Configuración de suites PHPUnit. |
| `base/codeception.yml` y `base/tests/functional.suite.yml` | Configuración de pruebas funcionales. |

---

## 9. Seguridad y autenticación

### 9.1 Mecanismos detectados

| Área | Evidencia inicial |
|---|---|
| Autenticación | `login.php`, `mod/login/ajax_login.php`, `mod/login/check.php`. |
| LDAP | Variables `LDAP_ENABLED`, `LDAP_HOST`, `LDAP_DN`, `LDAP_USER`, `LDAP_PASS`; librería `AuthLdap`; entorno `ldap-docker`. |
| Roles/permisos | Constantes de módulos/acciones en `config.inc`; función `check_permiso`; tablas `sis_rolls`, `sis_permits`, `sis_mod`, `sis_mod_actions`. |
| Sesiones | Librería `lib/mysession`; tablas `sis_sessions` y `sis_sessions_vars`. |
| Auditoría de acceso | Tabla `sis_log`; funciones de auditoría en `db.php`; pantalla `admin_auditoria.php` y módulo `mod/admin/audit`. |
| HTTPS | Reglas `.htaccess` fuerzan HTTPS fuera de entornos locales. |
| Prepared statements | Existen funciones `seleccion_segura`, `ejecutar_query` y transacciones con parámetros en `inc/db/db.php`. |
| Alertas de seguridad | Detección/notificación de múltiples intentos fallidos de login en funciones de auditoría. |
| Recuperación de contraseña | Scripts `send_password_recovery_email.php`, `verify_password_recovery.php`, `reset_password_advisor.php` y SQL incremental. |

### 9.2 Observaciones críticas preliminares

- Existen funciones seguras con prepared statements, pero también funciones antiguas que reciben SQL completo (`seleccion`, `transaccion`). El manual debe distinguir entre patrón vigente recomendado y legado.
- Hay credenciales/datos de desarrollo en archivos Docker y SQL de seed. Deben documentarse como valores de desarrollo, nunca de producción.
- El archivo `base/base.sql` contiene datos personales de ejemplo/seed. Esto debe tratarse como riesgo de privacidad si el repositorio es público o compartido fuera del entorno autorizado.
- La aplicación usa `.htaccess` para forzar HTTPS; esto depende de Apache y de que `AllowOverride` esté habilitado.

---

## 10. Servicios detectados

| Servicio/clase | Ruta | Responsabilidad inicial |
|---|---|---|
| `Service\GoogleCalendarService` | `base/service/GoogleCalendarService.php` | Integración OAuth, tokens y sincronización con Google Calendar. |
| `CancelacionProyectoService` | `base/service/cancelaciones/CancelacionProyectoService.php` | Caso de uso de cancelación de proyecto. |
| `CancelacionProyectoRules` | `base/service/cancelaciones/CancelacionProyectoRules.php` | Reglas de negocio de cancelación. |
| `CancelacionProyectoValidator` | `base/service/cancelaciones/CancelacionProyectoValidator.php` | Validaciones de entrada/proceso de cancelación. |
| `CancelacionProyectoRepositoryInterface` | `base/service/cancelaciones/CancelacionProyectoRepositoryInterface.php` | Contrato de persistencia para cancelaciones. |

---

## 11. Pruebas detectadas

| Tipo | Ruta/configuración | Alcance observado |
|---|---|---|
| Unitarias | `base/tests/unit/`, `base/phpunit.unit.xml` | Alertas, archivo histórico, chat, asesores externos, revisión documental, HU033, HU039, HU041, plantillas, prórrogas, cancelaciones, login, TFG. |
| Integración | `base/tests/integration/`, `base/phpunit.integration.xml` | Login, alertas, chat y flujo completo TFG. |
| Funcionales | `base/tests/functional/`, `base/codeception.yml`, `base/tests/functional.suite.yml` | Auditoría, minutas, asesores externos, notificaciones, carga de archivos, cancelación, login, plantillas, correo TFG. |
| Datos de prueba | `base/tests/_data/` | PDFs, imágenes y archivos inválidos para validación de uploads. |
| Evidencias de fallos | `base/tests/_output/` | HTML/JSON/JUnit de ejecuciones previas fallidas. |

Scripts Composer detectados:

```bash
composer test
composer test:unit
composer test:integration
composer test:functional
composer test:functional:debug
```

---

## 12. Scripts de despliegue e infraestructura

| Recurso | Descripción |
|---|---|
| `PLAN_DEPLOYMENT.md` | Guía de despliegue Linux. |
| `base/documentation/DOCKER_DEPLOYMENT.md` | Despliegue con Docker. |
| `base/documentation/MANUAL_DEPLOYMENT.md` | Manual de despliegue adicional. |
| `base/docker/docker-compose.yml` | Orquesta app PHP/Apache y MySQL. |
| `base/docker/php/Dockerfile` | Imagen PHP/Apache. |
| `base/docker/php/php.ini` | Configuración PHP. |
| `base/docker/mysql/my.cnf` | Configuración MySQL. |
| `base/docker/msmtp/msmtprc` | Correo desde contenedor. |
| `base/ldap-docker/docker-compose.yml` | Entorno LDAP local. |
| `base/cron_check_deadlines.php` | Job de revisión de vencimientos. |
| `base/cron_check_deadlines.log` | Log del cron en repositorio. |

---

## 13. Primera tabla de módulos funcionales

| Módulo | Archivos/carpetas principales | Descripción preliminar | Estado de validación |
|---|---|---|---|
| Acceso, login y sesión | `login.php`, `mod/login/`, `lib/mysession/`, `config.inc` | Autenticación local/LDAP, recuperación de contraseña, sesión y cierre. | Confirmado por estructura. |
| Administración de usuarios | `admin_usuarios.php`, `mod/admin/users/` | Gestión de usuarios, perfiles, búsqueda y edición. | Confirmado por estructura. |
| Roles y permisos | `admin_roles.php`, `admin_modulos.php`, `mod/admin/rolls/`, `mod/admin/permits/`, `functions.php` | Administración de roles, módulos, acciones y permisos. | Confirmado por estructura. |
| Auditoría | `admin_auditoria.php`, `mod/admin/audit/`, `sis_log` | Consulta de logs de acceso y eventos. | Confirmado por estructura. |
| Gestión de propuestas TFG | `panel_subir_propuesta_tfg.php`, `inc/tfg_proposal_functions.php`, `tfg_proposals` | Registro/carga de propuestas y flujo inicial de TFG. | Confirmado por estructura. |
| Revisión y documentos TFG | `panel_revision_tfg.php`, `panel_ctfg_review_final_documents.php`, `mod/admin/users/tfg_*`, `inc/tfg_final_functions.php` | Carga, revisión, correcciones, versiones y documentos finales. | Confirmado por estructura. |
| Proyectos registrados/aprobados | `ProyectosRegistrados.php`, `proyecto_aprobado.php`, `registered_projects`, `project_members` | Administración y consulta de proyectos registrados/aprobados. | Confirmado por estructura. |
| Panel estudiante | `panel_estudiante.php`, `PanelEstudianteLogic.php`, `inc/js/panel_estudiante.js` | Vista y acciones asociadas al rol estudiante. | Confirmado por estructura. |
| Panel asesor | `panel_asesor.php`, `PanelAsesorLogic.php`, `panel_revisar_asesor_externo.php` | Revisión y seguimiento desde rol asesor/interno/externo. | Confirmado por estructura. |
| Panel gestor | `panel_gestor.php`, `PanelGestorLogic.php` | Gestión operativa de proyectos/solicitudes. | Confirmado por estructura. |
| Panel CTFG/comité | `panel_ctfg.php`, `PanelCTFGLogic.php`, `panel_comites_asesores.php`, `panel_committee_minutes.php` | Gestión de comité, minutas y revisión final. | Confirmado por estructura. |
| Prórrogas | `panel_solicitudProrroga.php`, `panel_aprobarProrroga.php`, `panel_reporte_prorrogas.php`, `mod/admin/users/*prorroga*` | Solicitud, aprobación, detalle y reporte de prórrogas. | Confirmado por estructura. |
| Notificaciones y alertas | `historial_alertas.php`, `inc/alert_functions.php`, `inc/deadline_functions.php`, `cron_check_deadlines.php` | Alertas internas y de vencimiento. | Confirmado por estructura. |
| Chat/mensajería | `chat.php`, `chat_*.php`, `inc/chat_functions.php`, `inc/js/chat.js`, `chat_*` tables | Conversaciones, participantes, mensajes y lecturas. | Confirmado por estructura. |
| Plantillas oficiales | `panel_plantillas.php`, `plantilla_*`, `inc/plantillas_functions.php`, `plantillas_oficiales` | Carga, descarga, activación/desactivación y eliminación de plantillas. | Confirmado por estructura. |
| Actas/minutas/acuerdos | `generar_acta.php`, `procesar_acta.php`, `PanelRegistroAcuerdo.php`, `project_minutes`, `project_minute_attendees` | Generación/carga/gestión de actas y acuerdos. | Confirmado por estructura. |
| Archivo histórico | `panel_archivo_historico.php`, `inc/archive_functions.php`, tablas `*_archive` | Archivado y consulta histórica de proyectos/documentos. | Confirmado por estructura. |
| Asesores externos | `panel_revisar_asesor_externo.php`, `external_advisor_*`, documentación `EXTERNAL_ADVISORS.md` | Solicitudes/perfiles/vinculación de asesores externos. | Confirmado por estructura. |
| Google Calendar | `auth/`, `service/GoogleCalendarService.php`, `google_calendar_*` | Conexión OAuth y sincronización de eventos. | Confirmado por estructura. |
| Correo | `inc/email_helper.php`, `inc/email_template_helper.php`, PHPMailer, `test_mail*.php` | Envío de correos transaccionales y pruebas de correo. | Confirmado por estructura. |
| Reportes/resúmenes | `panel_student_summary.php`, `mod/admin/users/student_summary_*`, `prorroga_report_*` | Reportes PDF/resúmenes de estudiante/prórrogas. | Confirmado por estructura. |
| Cancelación de proyectos | `cancelar_proyecto_aprobado.php`, `service/cancelaciones/`, `acuerdo_cancelacion` | Cancelación y reglas asociadas. | Confirmado por estructura. |

---

## 14. Riesgos y huecos de documentación detectados

| Riesgo/hueco | Impacto real | Recomendación para el manual |
|---|---|---|
| Arquitectura híbrida/legacy | Si se documenta como MVC o Clean Architecture estricta, el manual quedaría técnicamente falso. | Describir como monolito PHP modular por páginas, con servicios puntuales. |
| Lógica mezclada en vistas/controladores | Dificulta mantenimiento, pruebas y onboarding. | Documentar flujo real por archivo y sugerir refactor gradual, no reescritura. |
| Doble estilo de acceso a BD | Riesgo de SQL injection si se usan funciones legacy con entrada no validada. | Identificar funciones seguras vs. legacy y exigir prepared statements en cambios nuevos. |
| Datos personales/seed en SQL | Riesgo de privacidad y cumplimiento si se expone el repo. | Clasificar datos como desarrollo; recomendar anonimización o seeds sintéticos. |
| `vendor/`, librerías y binarios incluidos | Repositorio pesado y posible dificultad de auditoría de dependencias. | Documentar dependencias gestionadas por Composer vs. librerías embebidas; sugerir política de dependencias. |
| Documentación fragmentada | Hay muchos manuales/documentos separados que pueden contradecirse. | Crear índice maestro y usar documentos existentes como anexos/fuentes. |
| Configuración productiva sensible | Variables y ejemplos pueden confundirse con valores productivos. | Separar claramente desarrollo, pruebas y producción. |
| Logs/evidencias de prueba en repo | Puede exponer datos o ruido operacional. | Documentar política de exclusión/limpieza para logs y `_output`. |
| Dependencia de `.htaccess` | Seguridad HTTPS depende de Apache y `AllowOverride`. | Incluir verificación de configuración del virtual host en despliegue. |
| Pruebas funcionales con outputs fallidos | Puede indicar flujos frágiles o dependencias no levantadas. | En Prompt 5 validar estado real de suites y ambiente requerido. |

---

## 15. Propuesta de índice maestro del manual técnico

> Esta propuesta está pensada para un manual formal extenso. Debe completarse y validarse en los siguientes prompts.

1. **Portada**
   - Nombre del sistema.
   - Institución/proyecto.
   - Versión del documento.
   - Fecha.
   - Autores/responsables.

2. **Control de cambios del documento**
   - Versión.
   - Fecha.
   - Autor.
   - Descripción del cambio.

3. **Resumen ejecutivo técnico**
   - Propósito del sistema.
   - Alcance funcional.
   - Arquitectura resumida.
   - Tecnologías principales.

4. **Introducción**
   - Contexto del SGPFL.
   - Objetivos del manual.
   - Audiencia objetivo.
   - Convenciones del documento.

5. **Descripción general del sistema**
   - Problema que resuelve.
   - Usuarios/roles principales.
   - Capacidades funcionales.
   - Límites del sistema.

6. **Arquitectura del sistema**
   - Estilo arquitectónico real.
   - Diagrama de contexto.
   - Diagrama de contenedores/componentes.
   - Flujo HTTP general.
   - Capas reales: presentación, scripts/controladores, funciones, servicios, datos.
   - Dependencias internas y externas.

7. **Estructura del repositorio**
   - Mapa de carpetas.
   - Archivos principales.
   - Convenciones de nombres.
   - Ubicación de documentación y manuales.

8. **Requisitos técnicos**
   - Sistema operativo.
   - Servidor web.
   - PHP y extensiones.
   - MySQL/MariaDB.
   - LDAP.
   - SMTP.
   - Docker.
   - Composer.

9. **Instalación y configuración**
   - Instalación manual.
   - Instalación con Docker.
   - Configuración de Apache/.htaccess.
   - Configuración PHP.
   - Configuración MySQL.
   - Configuración LDAP.
   - Configuración correo.
   - Variables de entorno.
   - Permisos de carpetas.

10. **Base de datos**
    - Modelo conceptual.
    - Modelo lógico por grupos de tablas.
    - Diccionario de datos.
    - Scripts SQL.
    - Migraciones/incrementales.
    - Datos iniciales.
    - Respaldo y restauración.

11. **Seguridad**
    - Autenticación local/LDAP.
    - Sesiones.
    - Roles y permisos.
    - Auditoría.
    - HTTPS.
    - Manejo de contraseñas y recuperación.
    - Validación de entradas.
    - Carga segura de archivos.
    - Riesgos OWASP relevantes.
    - Recomendaciones de hardening.

12. **Módulos funcionales**
    - Acceso/login.
    - Administración de usuarios.
    - Roles/permisos.
    - Auditoría.
    - Propuestas TFG.
    - Revisión documental.
    - Proyectos registrados/aprobados.
    - Panel estudiante.
    - Panel asesor.
    - Panel gestor.
    - Panel CTFG/comité.
    - Prórrogas.
    - Notificaciones.
    - Chat.
    - Plantillas.
    - Actas/minutas/acuerdos.
    - Archivo histórico.
    - Asesores externos.
    - Google Calendar.
    - Correo.
    - Reportes.
    - Cancelación de proyectos.

13. **Flujos principales del sistema**
    - Login y autorización.
    - Registro/carga de propuesta.
    - Aprobación/rechazo de propuesta.
    - Carga de documentos TFG.
    - Revisión final por asesor/CTFG.
    - Solicitud/aprobación de prórroga.
    - Generación de actas.
    - Notificaciones y vencimientos.
    - Chat entre usuarios.
    - Integración Google Calendar.

14. **Servicios e integraciones externas**
    - LDAP.
    - SMTP/PHPMailer/msmtp.
    - Google Calendar.
    - Docker/MySQL.

15. **Pruebas**
    - Estrategia de pruebas.
    - PHPUnit unitario.
    - PHPUnit integración.
    - Codeception funcional.
    - Datos de prueba.
    - Cómo ejecutar pruebas.
    - Interpretación de fallos comunes.

16. **Despliegue**
    - Despliegue manual Linux.
    - Despliegue Docker.
    - Configuración productiva.
    - Checklist preproducción.
    - Checklist postdespliegue.
    - Rollback.

17. **Operación y mantenimiento**
    - Backups.
    - Logs.
    - Limpieza de uploads temporales.
    - Rotación de credenciales.
    - Actualización de dependencias.
    - Monitoreo recomendado.

18. **Observabilidad**
    - Logs de aplicación.
    - Auditoría en BD.
    - Logs de cron.
    - Métricas recomendadas.
    - Alertas operativas.

19. **Riesgos técnicos y deuda técnica**
    - Hallazgos.
    - Impacto.
    - Priorización.
    - Plan de mejora gradual.

20. **Glosario**
    - TFG.
    - CTFG.
    - LDAP.
    - Roles.
    - Propuesta.
    - Prórroga.
    - Acta/minuta.

21. **Anexos**
    - Variables de entorno.
    - Comandos útiles.
    - Scripts SQL.
    - Diagramas.
    - Manuales existentes relacionados.
    - Evidencia por archivo.

---

## 16. Diagramas sugeridos para próximas etapas

| Diagrama | Objetivo | Fuente de datos |
|---|---|---|
| Diagrama de contexto | Mostrar usuarios, SGPFL, LDAP, SMTP, MySQL y Google Calendar. | `config.inc`, `docker-compose.yml`, `auth/`, `service/`. |
| Diagrama de contenedores | Separar navegador, Apache/PHP, MySQL, LDAP, SMTP. | Docker y despliegue. |
| Diagrama de componentes PHP | Mostrar páginas, `mod/`, `inc/`, `service/`, `inc/db`. | Estructura real. |
| DER por dominios | Agrupar tablas por seguridad, TFG, chat, notificaciones, archivos, Google Calendar. | `base/base.sql`. |
| Flujo de login | Usuario → login → ajax_login → LDAP/BD → sesión → main. | `login.php`, `mod/login/`, `lib/mysession`. |
| Flujo de propuesta TFG | Estudiante → carga → revisión → registro/proyecto. | Paneles TFG y tablas. |
| Flujo de documentos finales | Carga → revisión asesor/CTFG → corrección/aprobación. | `tfg_*`, `tfg_document_reviews`. |
| Flujo de prórroga | Solicitud → revisión/aprobación → notificación. | `panel_solicitudProrroga.php`, `panel_aprobarProrroga.php`. |
| Flujo de notificaciones | Evento → `alert_functions`/cron → `user_alerts`. | `inc/alert_functions.php`, `cron_check_deadlines.php`. |

---

## 17. Próximo paso recomendado

El siguiente prompt debería enfocarse en **identificar y documentar la arquitectura real del sistema**. La hipótesis inicial a validar es:

> SGPFL es un monolito PHP server-rendered, con estructura modular por páginas/controladores y carpetas funcionales, persistencia MySQL procedural, autenticación local/LDAP, permisos por roles y servicios puntuales desacoplados mediante Composer PSR-4.

En el Prompt 2 se debe validar esta hipótesis revisando flujos concretos: login, permisos, carga de propuesta/documentos, notificaciones y al menos un servicio (`GoogleCalendarService` o cancelaciones).
