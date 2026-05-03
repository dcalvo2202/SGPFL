# Introducción

Este documento describe la configuración técnica y los parámetros esenciales del sistema **SGPFL (Sistema Gestor de Proyectos Finales de Licenciatura)**. Su objetivo es servir como guía de referencia para nuevos administradores o personal de soporte que requieran comprender, mantener o desplegar el sistema en diferentes entornos.

El contenido abarca los archivos de configuración principales, la estructura de base de datos, la integración con el servicio de autenticación LDAP y las configuraciones necesarias en PHP para garantizar el funcionamiento correcto del sistema.

A lo largo del documento se detallan los siguientes aspectos:

* Archivos de configuración del sistema (`config.inc`, `dbcommon.inc`).
* Integración con el servidor LDAP y sincronización con la base de datos.
* Configuración de versiones y dependencias (LDAPv3, PHP, MySQL).
* Parámetros de envío de correos electrónicos (archivos `php.ini` y `sendmail.ini`).
* Consideraciones de seguridad y despliegue en entornos productivos.

Esta documentación debe mantenerse actualizada conforme se realicen cambios en el entorno del servidor o en la estructura interna del sistema, con el fin de asegurar su estabilidad, compatibilidad y seguridad operativa.

## Instalación y despliegue

Este apartado describe los pasos necesarios para instalar y poner en funcionamiento el sistema SGPFL en un entorno local o productivo.

### 1.1 Requisitos previos

* Servidor web: Apache o Nginx
* PHP >= 7.4 con extensiones: `ldap`, `mysqli`, `zip`, `mbstring`, `fileinfo` (Recomendado PHP 8.4)
* MySQL o MariaDB >= 5.7
* Acceso a un servidor LDAP v3 (puede ser OpenLDAP o Active Directory)
* Acceso a un servidor SMTP para el envío de correos institucionales

### 1.2 Opción A — Instalación con XAMPP

1. Copiar los archivos del proyecto en la ruta deseada del servidor web.
2. Configurar las variables de entorno en:

   * `config.inc` → dominio, ubicación del sistema, LDAP, módulos y acciones
   * `inc/db/bdcommon.inc` → conexión a la base de datos
3. Crear la base de datos e importar el esquema inicial:

   ```bash
   mysql -u usuario -p base_db < base.sql
   ```
4. Configurar LDAP y verificar la conexión según lo definido en `config.inc`.
5. Configurar envío de correos electrónicos mediante `php.ini` y `sendmail.ini`.
6. Reiniciar el servidor web para aplicar cambios.
7. Acceder al sistema desde un navegador para verificar que todas las funcionalidades estén operativas.

### 1.3 Opción B — Instalación con Docker (sin XAMPP)

El sistema incluye un entorno Docker completo. Ver manuales detallados en `/manuales/`:

| Manual | Descripción |
|--------|-------------|
| `LDAP_DOCKER_GUIDE.md` | Servidor OpenLDAP + phpLDAPadmin |
| `DOCKER_SISTEMA_GUIDE.md` | Apache + PHP + MySQL |

```bash
# 1. Crear red compartida (solo una vez)
docker network create sgpfl-shared

# 2. Levantar LDAP
cd ldap-docker && docker-compose up -d --build

# 3. Levantar sistema
cd ../docker && docker-compose up -d --build
```

El sistema queda disponible en **http://localhost:8080/base/**

---

## 2. Estructura del proyecto

Se recomienda que los administradores se familiaricen con la estructura de carpetas y archivos principales:

```
/inc             → Archivos de configuración y librerías generales
/inc/db          → Archivos de conexión a la base de datos
/mod             → Módulos funcionales del sistema
/uploads         → Archivos cargados por los usuarios
/img             → Recursos gráficos como favicon, logos, íconos
/docker          → Entorno Docker: Apache + PHP + MySQL (sin XAMPP)
/ldap-docker     → Entorno Docker: servidor OpenLDAP + phpLDAPadmin
/manuales        → Documentación técnica del sistema
```

* Las modificaciones en módulos o rutas deben reflejarse en los archivos de configuración y, si corresponde, en la base de datos.
* Mantener una copia de seguridad de los archivos de configuración críticos (`config.inc`, `dbcommon.inc`, `sendmail.ini`) antes de cualquier cambio.

---

## 3. Mantenimiento y respaldo

### 3.1 Copia de seguridad de la base de datos

Se recomienda realizar respaldos periódicos de la base de datos. Por ejemplo:

```bash
mysqldump -u usuario -p base_db > respaldo_base_db.sql
```

### 3.2 Copia de seguridad de archivos críticos

* `config.inc`
* `dbcommon.inc`
* `sendmail.ini`
* `/uploads` (archivos subidos por usuarios)

### 3.3 Actualizaciones y cambios

* Antes de actualizar el sistema, respaldar tanto la base de datos como los archivos de configuración.
* Verificar compatibilidad de módulos y permisos si se agregan nuevas funcionalidades.
* Confirmar que los servicios LDAP y de correo continúan funcionando tras cualquier actualización.

---

## 4. Solución de problemas comunes

| Problema                         | Posible causa                                          | Solución sugerida                                                 |
| -------------------------------- | ------------------------------------------------------ | ----------------------------------------------------------------- |
| Error de conexión LDAP           | Versión o puerto incorrecto                            | Verificar configuración LDAPv3 y puerto en `config.inc`           |
| No se envían correos             | Configuración de `sendmail.ini` o `php.ini` incorrecta | Revisar credenciales y remitente en `sendmail.ini`                |
| Sendmail se queda colgado        | Puerto 587 con TLS no compatible                       | Cambiar a puerto **465 con SSL** en `sendmail.ini`                |
| Error de permisos en logs        | Carpeta sin permisos de escritura                      | Mover logs a `C:\xampp\tmp\` en `sendmail.ini`                    |
| mail() retorna FALSE             | Apache no reiniciado tras cambios                      | Reiniciar Apache desde XAMPP Control Panel                        |
| No carga la base de datos        | Credenciales incorrectas o base no creada              | Revisar `dbcommon.inc` y existencia de la base          |
| Error "MySQL server has gone away" al subir archivos | `max_allowed_packet` bajo o timeout en MySQL/XAMPP | Aumentar parámetros en `my.ini` y reiniciar MySQL/Apache |
| Listados incompletos o sin datos | `$page_cant` demasiado bajo                            | Ajustar valor en `config.inc`                           |
| Accesos denegados en módulos     | Usuario no asignado al rol correcto                    | Revisar LDAP y tabla `sis_rolls`                        |

# Configuración del sistema

## Configuración requerida en `php.ini`

Para que el sistema funcione correctamente, es necesario habilitar y configurar ciertos módulos en el archivo de configuración de PHP (`php.ini`). Estos componentes permiten la conexión con LDAP, la base de datos MySQL y el envío de correos electrónicos institucionales.

---

### 1. Extensiones obligatorias

Asegúrese de que las siguientes extensiones estén habilitadas en `php.ini`:

```ini
extension=ldap
extension=mysqli
extension=zip
extension=mbstring
extension=fileinfo
```

* **ldap:** Permite la autenticación y comunicación con el servidor LDAP.
* **mysqli:** Gestiona la conexión con la base de datos MySQL utilizada por el sistema.
* **zip:** Manejo de archivos comprimidos en uploads.
* **mbstring:** Manejo de strings multibyte (requerido por PHP moderno).
* **fileinfo:** Validación de tipos de archivos subidos por usuarios.

> Si las líneas anteriores están comentadas (precedidas por `;`), deben descomentarse eliminando el punto y coma.

---

### 2. Límites de subida de archivos (IMPORTANTE)

Para permitir que los estudiantes suban propuestas TFG, debe configurar los siguientes valores en `php.ini`:

```ini
upload_max_filesize = 40M
post_max_size = 40M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

**Ubicación del archivo `php.ini`:**
- **XAMPP Windows:** `C:\xampp\php\php.ini`
- **XAMPP Linux/Mac:** `/opt/lampp/etc/php.ini`
- **Ubuntu/Debian:** `/etc/php/8.x/apache2/php.ini`

**Pasos para modificar:**
1. Abrir `php.ini` con un editor de texto
2. Buscar cada parámetro (Ctrl+F) y cambiar el valor
3. Guardar el archivo
4. **Reiniciar Apache** desde el panel de XAMPP

> **Nota:** El archivo `.htaccess` del proyecto también define estos valores como respaldo, pero algunos servidores pueden ignorarlos dependiendo de la configuración de `AllowOverride`.

---

### 2.1 Ajustes de MySQL para archivos grandes (XAMPP)

Si durante la subida de documentos aparece el error:

```text
MySQL server has gone away
```

normalmente la causa es un límite bajo en MySQL al guardar BLOBs.

Edite el archivo `my.ini` de XAMPP y ajuste en la sección `[mysqld]`:

```ini
max_allowed_packet       = 64M
innodb_log_file_size     = 128M
innodb_buffer_pool_size  = 16M
innodb_log_buffer_size   = 8M
innodb_flush_log_at_trx_commit = 1
innodb_lock_wait_timeout = 50
```

**Ubicación habitual (Windows XAMPP):**
- `C:\xampp\mysql\bin\my.ini`

**Pasos recomendados:**
1. Detener MySQL y Apache desde XAMPP Control Panel.
2. Guardar los cambios en `my.ini`.
3. Iniciar MySQL y Apache nuevamente.

> Si el error persiste, revisar también `wait_timeout`/`interactive_timeout` en MySQL y confirmar los límites de PHP (`upload_max_filesize`, `post_max_size`).

---

### 3. Configuración de envío de correos

#### En XAMPP (Windows)

Debe configurarse la sección `[mail function]` en `php.ini`:

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 465
sendmail_from = correo-del-emisor
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t -i"
```

#### En Docker (Linux)

En el contenedor se usa `msmtp` como relay SMTP (equivalente a `sendmail.exe`). La configuración está en `docker/msmtp/msmtprc` y el `php.ini` del contenedor apunta a él:

```ini
sendmail_path = /usr/bin/msmtp -t
sendmail_from = correo-del-emisor
```

Ver la correspondencia completa de parámetros en `manuales/DOCKER_SISTEMA_GUIDE.md`.

* **SMTP:** Dirección del servidor SMTP que procesará los correos.
  * Gmail: `smtp.gmail.com`
  * Outlook: `smtp-mail.outlook.com`

* **smtp_port:** Puerto de conexión SMTP.
  * Recomendado: **465** (SSL)
  * Alternativa: 587 (TLS, puede tener problemas con sendmail.exe)

* **sendmail_from:** Dirección de correo que se utilizará como remitente predeterminado.
  * Debe pertenecer al dominio institucional o autorizado por el servidor SMTP.
  * Ejemplo institucional: `no-reply@institucional.edu`

* **sendmail_path:** Ruta completa al ejecutable de sendmail.
  * Windows XAMPP: `"\"C:\xampp\sendmail\sendmail.exe\" -t -i"`
  * Docker/Linux: `/usr/bin/msmtp -t`

> **Nota:** Esta configuración debe sincronizarse con `sendmail.ini` en XAMPP o con `msmtprc` en Docker.

---

### 4. Consideraciones adicionales

* Después de realizar cambios en `php.ini`, **reinicie el servidor web** (por ejemplo, Apache o Nginx) para aplicar la configuración.
* Verifique que las extensiones estén correctamente cargadas ejecutando el siguiente comando:

```bash
php -m
```

Debe listar entre los módulos activos:

```
ldap
mysqli
```

* En entornos productivos, se recomienda utilizar un servidor SMTP autenticado y con TLS habilitado para garantizar la seguridad en el envío de correos.

## Configuración del servicio de correo: `sendmail.ini`

El archivo `sendmail.ini` define los parámetros necesarios para el envío de correos electrónicos desde el sistema. Esta configuración permite autenticar y utilizar una cuenta institucional o de servicio como remitente predeterminado.

**Ubicación del archivo:**
- **XAMPP Windows:** `C:\xampp\sendmail\sendmail.ini`
- **XAMPP Linux/Mac:** `/opt/lampp/sendmail/sendmail.ini`

---

### 1. Configuración básica

Ejemplo de configuración funcional para Gmail:

```ini
[sendmail]

; Servidor SMTP
smtp_server=smtp.gmail.com
smtp_port=465

; Protocolo de seguridad (SSL para puerto 465)
smtp_ssl=ssl

; Logs (con permisos de escritura)
error_logfile=C:\xampp\tmp\sendmail_error.log
debug_logfile=C:\xampp\tmp\sendmail_debug.log

; Autenticación
auth_username=correo-del-emisor
auth_password=(contraseña de app de Gmail)

; Remitente forzado
force_sender=correo-del-emisor
```

#### Parámetros importantes:

* **smtp_server:** Dirección del servidor SMTP.
  * Gmail: `smtp.gmail.com`
  * Outlook: `smtp-mail.outlook.com`

* **smtp_port:** Puerto de conexión SMTP.
  * **465** (SSL) → Recomendado para sendmail.exe
  * 587 (TLS) → Puede causar problemas con versiones antiguas de sendmail

* **smtp_ssl:** Protocolo de seguridad.
  * **ssl** → Para puerto 465 (más compatible)
  * tls → Para puerto 587
  * auto → Selección automática (no recomendado)

* **error_logfile / debug_logfile:** Rutas de logs.
  * Deben apuntar a una carpeta con **permisos de escritura**
  * Recomendado: `C:\xampp\tmp\` en Windows

* **auth_username:** Usuario para autenticación SMTP.
  * Para Gmail, debe tener **verificación en 2 pasos** habilitada.

* **auth_password:** Contraseña de aplicación.
  * **Gmail:** Generar en https://myaccount.google.com/apppasswords
  * **NO usar la contraseña normal de la cuenta**

* **force_sender:** Dirección del remitente.
  * Debe coincidir con `auth_username` o ser autorizada por el servicio SMTP.
  * En entornos institucionales: `no-reply@institucional.edu`

---

### 2. Solución de problemas comunes

| Problema | Causa | Solución |
|----------|-------|----------|
| Sendmail se queda colgado | Puerto 587 con TLS no compatible | Cambiar a puerto **465 con SSL** |
| Error de permisos en logs | Carpeta sin permisos de escritura | Mover logs a `C:\xampp\tmp\` |
| No autentica con Gmail | Contraseña incorrecta | Generar contraseña de aplicación |
| Correos no salen | Apache no reiniciado | Reiniciar Apache después de cambios |

---

### 3. Consideraciones de seguridad

* Nunca almacene contraseñas reales en texto plano dentro de entornos compartidos o repositorios.
* Si es posible, limite los permisos de lectura del archivo `sendmail.ini` solo al usuario que ejecuta el servidor web.
* Para entornos productivos, se recomienda configurar un **correo institucional dedicado** y no una cuenta personal.
* Use siempre **contraseñas de aplicación**, nunca contraseñas de cuenta principales.

---

### 4. Configuración en `php.ini`

Debe sincronizar la configuración de sendmail en `php.ini`:

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 465
sendmail_from = correo-del-emisor
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t -i"
```

**Pasos importantes:**
1. Editar `C:\xampp\php\php.ini`
2. Buscar la sección `[mail function]`
3. Configurar los valores como se muestra arriba
4. **Reiniciar Apache** desde XAMPP Control Panel

---

### 5. Verificación de funcionamiento

Una vez configurado, puede probar el envío de correos ejecutando un script PHP sencillo:

```php
<?php
$to = "destinatario@institucional.edu";
$subject = "Prueba de correo desde el sistema";
$message = "<b>Este es un correo de prueba</b> para verificar la configuración de sendmail.";
$headers = "From: correo-del-emisor\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";

if(mail($to, $subject, $message, $headers)) {
    echo "✓ Correo enviado correctamente.";
} else {
    echo "✗ Error al enviar el correo.";
}
?>
```

Si el mensaje se envía correctamente, la configuración del archivo `sendmail.ini` es funcional.

**Verificar logs:**
- `C:\xampp\tmp\sendmail_debug.log` → Historial de conexiones SMTP
- `C:\xampp\tmp\sendmail_error.log` → Errores específicos

# Configuración general

## Archivo de configuración: `config.inc`

El archivo `config.inc` contiene las variables principales de configuración del sistema. Define parámetros relacionados con el dominio, la conexión al servidor LDAP, las preferencias del sistema y los identificadores de módulos y acciones.

---

### 1. Configuración del servidor

```php
$cds_domain = "http://localhost/";
$cds_locate = "base/";
```

* **$cds_domain:** Define el dominio donde se aloja el sistema.

  * Valor por defecto: `http://localhost/`
  * Debe reemplazarse por el dominio real al desplegar el sistema en un entorno productivo, por ejemplo:

    ```php
    $cds_domain = "https://www.midominio.com/";
    ```

* **$cds_locate:** Indica la ruta del sistema dentro del dominio, en caso de estar en un subdirectorio.

  * Ejemplo: `"/sistema/"`, `"/lib/sistema/"`.

---

### 2. Configuración del servidor LDAP

El archivo usa variable de entorno con fallback, compatible con XAMPP y Docker:

```php
$ldap_status = 1;
$ldap_server[0] = getenv('LDAP_HOST') ?: "ldap://localhost:389";
$ldap_dn = "dc=una,dc=ac,dc=cr";
$ldap_user = "cn=admin,dc=una,dc=ac,dc=cr";
$ldap_pass = "admin";
```

* **$ldap_status:** Activa o desactiva la autenticación LDAP.

  * `1`: LDAP habilitado
  * `0`: LDAP deshabilitado

* **$ldap_server[0]:** Dirección del servidor LDAP.

  * Valor por defecto: `ldap://localhost:389`
  * Reemplace `localhost` por la IP o dominio del servidor LDAP en el entorno de producción.

* **$ldap_dn:** Base del directorio LDAP (Distinguished Name).

* **$ldap_user / $ldap_pass:** Credenciales del usuario administrador para conexión LDAP.

---

### 3. Preferencias generales

```php
$favicon_url = "./img/logo.webp";
$page_cant = 10;
$page_title = "SGPFL";
$footer_title = "Sistema Gestor de Proyectos Finales de Licenciatura<br/>Escuela de Informática, Universidad Nacional";
```

* **$favicon_url:** Ruta del ícono mostrado en el navegador.
* **$page_cant:** Número de elementos mostrados por página en listados.
* **$page_title:** Título principal del sistema.
* **$footer_title:** Texto mostrado en el pie de página.

---

### 4. Identificadores de acciones y módulos

Las variables de acción y módulo permiten gestionar permisos y accesos internos dentro del sistema.

#### Acciones:

```php
$act1 = 1; // Ver
$act2 = 2; // Listar
$act3 = 3; // Agregar
$act4 = 4; // Editar
$act5 = 5; // Eliminar
$act6 = 6; // Imprimir
```

#### Módulos:

```php
$mod1 = 1; // Acceso
$mod2 = 2; // Búsqueda
$mod3 = 3; // Historial y auditoría
$mod4 = 4; // Notificaciones
$mod5 = 5; // Documentación y versionado
$mod6 = 6; // Gestión de proyectos
$mod7 = 7; // Reportes y paneles
$mod8 = 8; // Gestión académica
```

> **Nota:**
> Es posible agregar nuevos módulos, pero debe actualizarse también la estructura correspondiente en la base de datos para mantener la coherencia del sistema. Se recomienda realizar un respaldo antes de cualquier modificación.

---

### 5. Consideraciones de despliegue

* Todos los valores que incluyan `localhost` deben ser reemplazados según el dominio o entorno real del servidor.
* Asegúrese de mantener las credenciales LDAP seguras y fuera del control de versiones.
* Cualquier cambio en las rutas o nombres de módulos requiere ajustes paralelos tanto en el código fuente como en la base de datos.

## Archivo de configuración: `/inc/db/dbcommon.inc`

El archivo `dbcommon.inc` contiene los parámetros de conexión a la base de datos y la ruta base utilizada en redirecciones internas del sistema. Es un componente esencial para la comunicación entre el sistema y el motor de base de datos.

---

### 1. Configuración de conexión a la base de datos

El archivo usa variables de entorno con fallback a `localhost`, compatible con XAMPP y Docker sin cambios:

```php
$db_host = getenv('DB_HOST') ?: 'localhost';
$usuario = getenv('DB_USER') ?: 'root';
$clave   = getenv('DB_PASS') ?: '';
$db      = getenv('DB_NAME') ?: 'base_db';
$DBMS = 'mysql';
```

* **$db_host:** Dirección del servidor de base de datos.

  * En XAMPP: usa `localhost` por defecto.
  * En Docker: se inyecta `DB_HOST=db` como variable de entorno.

* **$usuario:** Nombre de usuario con permisos de acceso a la base de datos.

* **$clave:** Contraseña asociada al usuario de base de datos.

  * Por motivos de seguridad, se recomienda **no dejar este valor vacío** en entornos de producción.

* **$db:** Nombre de la base de datos utilizada por el sistema.

  * Debe coincidir con la base de datos creada en el servidor.

* **$DBMS:** Define el tipo de sistema de gestión de base de datos.

  * Valor por defecto: `'mysql'`.
  * Solo modificar si se cambia el motor de base de datos.

---

### 2. Configuración de ruta base

```php
$base_url = '/base/';
```

* Define la ruta base utilizada para las redirecciones internas del sistema.
* Ajuste este valor si la estructura de carpetas difiere de la instalación por defecto.

---

### 3. Consideraciones de despliegue

* En **XAMPP**: los valores de `$db_host`, `$usuario`, `$clave` y `$db` se toman del fallback (`localhost`, `root`, vacío, `base_db`).
* En **Docker**: se inyectan las variables de entorno `DB_HOST=db`, `DB_USER=sgpfl`, `DB_PASS=sgpfl1234`, `DB_NAME=base_db` desde `docker-compose.yml`.
* Se recomienda mantener este archivo fuera del control de versiones o utilizar variables de entorno para proteger las credenciales.
* Antes de realizar cambios en las credenciales o el host, verifique la conexión con el nuevo servidor y respalde la base de datos.

## Integración con LDAP y estructura de roles

El sistema utiliza un servicio **LDAP** para la autenticación y organización de los usuarios. Este servicio gestiona tanto los grupos como las cuentas individuales, lo que permite controlar el acceso según el rol asignado.

---

### 1. Estructura LDAP

La jerarquía base del directorio LDAP sigue la siguiente estructura:

```
dc=una,dc=ac,dc=cr
├── ou=Groups
│   ├── cn=Administrador
│   ├── cn=Asesor Externo
│   ├── cn=CTFG
│   ├── cn=Estudiante
│   └── cn=Gestor Academico
└── ou=People
    ├── uid=105710421
    ├── uid=110600492
    ├── uid=111710169
    ├── uid=118440202
    └── uid=205610158
```

* **ou=Groups:** Contiene los diferentes grupos de usuarios definidos en el sistema.
* **ou=People:** Contiene las cuentas individuales de los usuarios, identificadas por su atributo `uid`.

> Cada usuario (`uid`) debe estar asociado a uno de los grupos definidos en `ou=Groups` para que el sistema determine sus permisos y accesos.

---

### 2. Relación con la base de datos

La tabla `sis_rolls` en la base de datos refleja la misma estructura de roles definida en el directorio LDAP. Esto permite sincronizar los permisos del sistema con las pertenencias de grupo de LDAP.

#### Estructura de la tabla `sis_rolls`

```sql
CREATE TABLE `sis_rolls` (
  `id_roll` int(11) NOT NULL AUTO_INCREMENT,
  `roll_name` varchar(100) DEFAULT NULL,
  `roll_desc` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_roll`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### Registros de ejemplo

| id_roll | roll_name        | roll_desc                                                                                              |
| ------- | ---------------- | ------------------------------------------------------------------------------------------------------ |
| 1       | Administrador    | Permisos totales sobre todos los módulos; sin restricciones.                                           |
| 2       | Gestor Académico | Permisos amplios excepto sobre la gestión de módulos y permisos de usuarios.                           |
| 3       | CTFG             | Comisión de Trabajos Finales; puede aprobar o rechazar trabajos y consultar documentos de estudiantes. |
| 4       | Estudiante       | Puede crear, editar y enviar documentos propios; acceso limitado a otros módulos.                      |
| 5       | Asesor Externo   | Permisos de lectura sobre los módulos del estudiante asignado.                                         |

---

### 3. Consideraciones de configuración

* Los nombres de los grupos definidos en LDAP deben coincidir con los valores de `roll_name` en la tabla `sis_rolls` para garantizar la correcta asignación de roles.
* Cualquier grupo nuevo creado en LDAP debe tener un registro correspondiente en `sis_rolls`.
* Antes de agregar o eliminar grupos en LDAP, se recomienda:

  1. Actualizar la tabla `sis_rolls`.
  2. Verificar que las reglas de autenticación en el sistema reconozcan el nuevo grupo.
  3. Respaldar el archivo `config.inc` y la base de datos.

---

### 4. Flujo de autenticación

1. El usuario inicia sesión con sus credenciales LDAP.
2. El sistema valida las credenciales contra el servidor LDAP configurado en `config.inc`.
3. Se obtiene el grupo (`cn`) del usuario.
4. El sistema consulta la tabla `sis_rolls` para asignar los permisos correspondientes.
5. Se establece la sesión con el rol y los privilegios asociados.

---

Este modelo permite una administración centralizada de usuarios mediante LDAP, manteniendo coherencia con la gestión de roles dentro de la base de datos.

## Compatibilidad y versión de LDAP

El sistema utiliza **LDAP versión 3 (LDAPv3)** como protocolo de comunicación con el servidor de directorio. Esta versión es obligatoria para el correcto funcionamiento del inicio de sesión y la gestión de usuarios.

---

### 1. Requisitos de versión

* **Protocolo:** LDAPv3
* **Puerto predeterminado:** 389 (para conexiones sin cifrar)
* **Puerto alternativo:** 636 (para conexiones seguras mediante LDAPS)

> LDAPv2 no es compatible con este sistema, ya que no soporta las extensiones y controles necesarios para la autenticación y búsqueda de usuarios implementada.

---

### 2. Configuración en el servidor

Asegúrese de que el servicio LDAP esté configurado para operar bajo la versión 3.
En la mayoría de los entornos, esto se define en el archivo de configuración del servidor (`slapd.conf` o `cn=config`).

Ejemplo de directiva en `slapd.conf`:

```
allow bind_v2
```

Debe estar **comentada o eliminada** para forzar el uso de LDAPv3 únicamente.

---

### 3. Compatibilidad en el sistema

* El cliente LDAP del sistema, configurado en `config.inc`, está preparado para comunicarse únicamente con servidores LDAPv3.
* Los comandos de conexión y las operaciones de búsqueda (`ldap_bind`, `ldap_search`, etc.) se ejecutan bajo esta versión.
* En caso de utilizar un servidor LDAP diferente (por ejemplo, Active Directory o OpenLDAP), se debe verificar que el protocolo LDAPv3 esté habilitado.

---

### 4. Recomendaciones

* Utilizar **LDAPS (puerto 636)** en entornos productivos para cifrar las credenciales y datos transmitidos.
* Mantener sincronizados los certificados SSL si se utiliza autenticación segura.
* Comprobar la versión del servidor LDAP mediante el comando:

```bash
ldapsearch -x -H ldap://localhost -b "" -s base "(objectclass=*)" supportedLDAPVersion
```

El resultado debe incluir:

```
supportedLDAPVersion: 3
```

## Integración con Google Calendar API

El sistema incluye sincronización opcional de eventos con **Google Calendar** usando OAuth 2.0. Permite que cada usuario conecte su cuenta de Google para que eventos como prórrogas, actas, fechas límite y avances del proyecto aparezcan automáticamente en su calendario personal.

---

### 1. Arquitectura general

| Componente | Descripción |
|---|---|
| `config.inc` | Almacena credenciales OAuth y clave de cifrado |
| `service/GoogleCalendarService.php` | Clase principal con toda la lógica de Google Calendar |
| `auth/google_auth.php` | Inicia el flujo OAuth 2.0, redirige al consentimiento de Google |
| `auth/google_callback.php` | Recibe el código de autorización y guarda el token |
| `auth/disconnect_google.php` | Desvincula la cuenta de Google del usuario |
| `vendor/google/apiclient` | Biblioteca oficial instalada vía Composer |
| `google_calendar_tokens` | Tabla en BD que guarda los tokens cifrados por usuario |
| `google_calendar_sync_log` | Tabla en BD que mapea eventos locales a eventos de Google |

---

### 2. Configuración en Google Cloud Console

#### 2.1 Crear proyecto

1. Ingresar a [https://console.cloud.google.com](https://console.cloud.google.com).
2. Crear un nuevo proyecto (ejemplo: `SGPFL Calendar Sync`).
3. Esperar a que se complete la creación del proyecto.

#### 2.2 Habilitar Google Calendar API

1. En el menú lateral ir a **APIs y servicios → Biblioteca**.
2. Buscar `Google Calendar API`.
3. Hacer clic en **Habilitar**.

#### 2.3 Configurar pantalla de consentimiento OAuth

1. Ir a **APIs y servicios → Pantalla de consentimiento OAuth**.
2. Seleccionar **Usuario externo**.
3. Completar los campos obligatorios:
   - **Nombre de la app:** SGPFL Calendar Sync
   - **Correo de soporte del usuario:** `tu@dominio.edu`
   - **Correo del desarrollador:** `tu@dominio.edu`
4. En la sección **Permisos (Scopes)**, agregar:
   - `https://www.googleapis.com/auth/calendar`
   - `https://www.googleapis.com/auth/userinfo.email`
   - `https://www.googleapis.com/auth/userinfo.profile`
5. Si la app aún está en fase de pruebas, agregar los correos de los usuarios de prueba en la sección **Usuarios de prueba**.

> **Nota:** En producción, se debe solicitar la verificación de la app para que usuarios externos puedan conectarse sin restricción.

#### 2.4 Crear credenciales OAuth 2.0

1. Ir a **APIs y servicios → Credenciales**.
2. Hacer clic en **Crear credenciales → ID de cliente OAuth**.
3. Seleccionar tipo: **Aplicación web**.
4. Agregar los URIs de redireccionamiento autorizados:
   ```
   http://localhost/base/auth/google_callback.php
   https://tudominio.com/base/auth/google_callback.php
   ```
5. Guardar y copiar el **Client ID** y el **Client Secret**.

---

### 3. Instalación de la biblioteca

La biblioteca oficial de Google para PHP se instala con Composer:

```bash
composer require google/apiclient
```

Si el archivo `vendor/autoload.php` no existe, ejecutar primero:

```bash
composer install
```

---

### 4. Configuración en `config.inc`

Agregar el siguiente bloque al archivo `config.inc`:

```php
$google_calendar_config = [
  'client_id'        => getenv('GOOGLE_CLIENT_ID')        ?: '',
  'client_secret'    => getenv('GOOGLE_CLIENT_SECRET')    ?: '',
    'redirect_uri'     => getenv('GOOGLE_REDIRECT_URI')     ?: 'http://localhost/base/auth/google_callback.php',
    'scopes'           => ['https://www.googleapis.com/auth/calendar'],
    'token_cipher'     => 'aes-256-gcm',
    'token_cipher_key' => getenv('GOOGLE_TOKEN_CIPHER_KEY') ?: 'CAMBIAR_ESTA_CLAVE_EN_PRODUCCION',
];
```

**Parámetros:**

| Parámetro | Descripción |
|---|---|
| `client_id` | ID de cliente OAuth de Google Cloud Console |
| `client_secret` | Secreto de cliente OAuth |
| `redirect_uri` | URI exacta registrada en Google Cloud Console |
| `scopes` | Permisos requeridos (solo calendario) |
| `token_cipher` | Algoritmo de cifrado para guardar tokens en BD (AES-256-GCM) |
| `token_cipher_key` | Clave de cifrado; se normaliza internamente con SHA-256 a 32 bytes |

> **Seguridad (implementado):** `client_id` y `client_secret` quedan vacíos si no existen variables de entorno. Esto evita credenciales hardcodeadas en repositorio. En producción es obligatorio definir `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` y `GOOGLE_TOKEN_CIPHER_KEY`.

---

### 5. Estructura de base de datos

Ejecutar las siguientes sentencias SQL para crear las tablas necesarias:

```sql
-- Almacena el token OAuth de cada usuario (cifrado)
CREATE TABLE IF NOT EXISTS `google_calendar_tokens` (
  `id`               int(11)      NOT NULL AUTO_INCREMENT,
  `id_user`          varchar(50)  NOT NULL,
  `access_token`     longtext     NOT NULL,
  `refresh_token`    longtext,
  `token_expires_at` longtext     NOT NULL,
  `calendar_id`      varchar(255),
  `sync_enabled`     tinyint(1)   DEFAULT 1,
  `created_at`       datetime     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       datetime     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_token` (`id_user`),
  FOREIGN KEY (`id_user`) REFERENCES `sis_login`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Mapeo entre eventos del sistema y eventos de Google Calendar
CREATE TABLE IF NOT EXISTS `google_calendar_sync_log` (
  `id`              int(11)                                   NOT NULL AUTO_INCREMENT,
  `id_user`         varchar(50)                               NOT NULL,
  `event_type`      varchar(50)                               NOT NULL COMMENT 'prorroga, acta, deadline, timeline',
  `event_id`        int(11)                                   NOT NULL,
  `google_event_id` varchar(255),
  `sync_status`     enum('pending','synced','failed')         DEFAULT 'pending',
  `last_sync_at`    datetime,
  `error_message`   text,
  `created_at`      datetime                                  DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      datetime                                  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_mapping` (`id_user`, `event_type`, `event_id`),
  KEY `idx_sync_status` (`sync_status`),
  FOREIGN KEY (`id_user`) REFERENCES `sis_login`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

### 6. Flujo de autenticación (OAuth 2.0)

```
Usuario hace clic en "Conectar Google Calendar"
        │
        ▼
auth/google_auth.php
  → Genera URL de autorización con GoogleCalendarService::getAuthUrl()
  → Redirige al usuario a Google
        │
        ▼
Google solicita consentimiento al usuario
        │
        ▼
auth/google_callback.php
  → Recibe parámetro ?code=...
  → Llama a GoogleCalendarService::handleAuthCallback()
  → Intercambia code por access_token + refresh_token
  → Cifra los tokens con AES-256-GCM
  → Los guarda en la tabla google_calendar_tokens
  → Redirige a perfil.php con mensaje de éxito
```

Para **desconectar** la cuenta:

```
auth/disconnect_google.php
  → Llama a GoogleCalendarService::disconnectUser()
  → Marca sync_enabled = 0 en google_calendar_tokens
  → Las sincronizaciones posteriores ignoran al usuario (WHERE sync_enabled = 1)
  → Redirige a perfil.php con mensaje de éxito
```

---

### 7. Uso de `GoogleCalendarService`

La clase `service/GoogleCalendarService.php` centraliza toda la lógica. Métodos públicos disponibles:

- `syncExtensionRequest($userId, $eventId, $projectData)`
- `syncProjectMinutes($userId, $eventId, $minutesData)`
- `syncDeadline($userId, $eventId, $deadlineData)`
- `syncProjectTimeline($userId, $eventId, $timelineData)`
- `deleteEvent($userId, $eventType, $eventId)`
- `isSyncEnabled($userId)`
- `disconnectUser($userId)`

Ejemplo real de sincronización de prórroga:

```php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/inc/db/bdcommon.inc';

use Service\GoogleCalendarService;

$conn = new mysqli($db_host, $usuario, $clave, $db);
$conn->set_charset('utf8');

$gcService = new GoogleCalendarService($conn);

if ($gcService->isSyncEnabled($userId)) {
  $gcService->syncExtensionRequest($userId, $requestId, [
    'project_name' => 'Proyecto Final',
    'student_names' => 'Estudiante 1, Estudiante 2',
    'status' => 'aprobada',
    'request_date' => date('Y-m-d H:i:s'),
    'extension_number' => 1,
    'extension_duration_label' => '1 año',
    'base_deadline' => '2029-04-04 00:00:00',
    'new_deadline' => '2030-04-04 00:00:00',
    'calendar_event_date' => '2030-04-04 00:00:00',
  ]);
}
```

---

### 8. Seguridad de los tokens

Los tokens de acceso y actualización se cifran con **AES-256-GCM** antes de guardarse en la base de datos:

- La clave de cifrado definida en `token_cipher_key` se normaliza internamente con `hash('sha256', $clave, true)` para obtener 32 bytes binarios.
- Cada token cifrado se almacena como un JSON con tres campos: `iv` (vector de inicialización), `tag` (etiqueta GCM) y `data` (texto cifrado), todos en Base64.
- Nunca se guarda el token en texto plano.
- La sincronización solo utiliza tokens con `sync_enabled = 1`.

Consulta rápida de verificación en BD:

```sql
SELECT
  id_user,
  JSON_VALID(access_token) AS access_json_ok,
  JSON_EXTRACT(access_token, '$.iv') IS NOT NULL AS has_iv,
  JSON_EXTRACT(access_token, '$.tag') IS NOT NULL AS has_tag,
  JSON_EXTRACT(access_token, '$.data') IS NOT NULL AS has_data,
  sync_enabled
FROM google_calendar_tokens;
```

---

### 9. Variables de entorno recomendadas para producción

Definir en el servidor web (Apache `SetEnv`, `.env`, o variables del sistema):

```
GOOGLE_CLIENT_ID=TU_CLIENT_ID.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=TU_CLIENT_SECRET
GOOGLE_REDIRECT_URI=https://tudominio.com/base/auth/google_callback.php
GOOGLE_TOKEN_CIPHER_KEY=una_clave_aleatoria_larga_y_segura
```

En XAMPP (Windows), se pueden agregar en `httpd.conf` o en el `VirtualHost`:

```apache
SetEnv GOOGLE_CLIENT_ID        "TU_CLIENT_ID.apps.googleusercontent.com"
SetEnv GOOGLE_CLIENT_SECRET    "TU_CLIENT_SECRET"
SetEnv GOOGLE_REDIRECT_URI     "http://localhost/base/auth/google_callback.php"
SetEnv GOOGLE_TOKEN_CIPHER_KEY "una_clave_aleatoria_larga_y_segura"
```

Checklist antes de migrar a producción:

1. Registrar en Google Cloud el `GOOGLE_REDIRECT_URI` exacto del servidor productivo (https).
2. Definir las 4 variables de entorno en Apache o en variables del sistema del servidor.
3. Verificar que `openssl` esté habilitado en `php.ini`.
4. Re-conectar Google Calendar para usuarios que tuvieran tokens de un ambiente anterior.
5. Confirmar en `google_calendar_sync_log` que aparezcan estados `synced` y revisar `failed`/`skipped`.

---

### 10. Solución de problemas comunes

| Problema | Causa probable | Solución |
|---|---|---|
| `redirect_uri_mismatch` | URI de callback no registrada en Google Cloud | Verificar que el URI en `config.inc` coincida exactamente con el registrado en Google Cloud Console |
| `invalid_grant` al usar el refresh_token | Token revocado o expirado | El usuario debe volver a conectar su cuenta desde `perfil.php` |
| Error `No se encontró la configuración de Google Calendar` | `$google_calendar_config` no definido en `config.inc` | Agregar el bloque de configuración indicado en el paso 4 |
| `Error al sincronizar con Google Calendar` en log | Token antiguo o credenciales/redirect no consistentes con el ambiente actual | Revisar `apache/logs/error.log`, validar variables de entorno y forzar reconexión OAuth del usuario |
| Estado `skipped` en `google_calendar_sync_log` | Usuario sin conexión activa (`sync_enabled = 0` o sin token) | Conectar Google Calendar desde `perfil.php` |
| Eventos no se crean en Google | Usuario no conectado o token sin permisos de escritura | Verificar que el scope `calendar` esté incluido y reconectar la cuenta |
| Error de cifrado al guardar token | `token_cipher_key` vacía o `openssl` no habilitado | Definir la clave y habilitar la extensión `openssl` en `php.ini` |
| La pantalla de consentimiento bloquea usuarios externos | App en modo prueba sin usuarios autorizados | Agregar el correo del usuario en "Usuarios de prueba" en Google Cloud Console |

---

## Anexo

* En la siguiente imagen se muestra la estructura que se utiliza para manejar el LDAP en el sistema. Se utilizan grupos los cuales son el nombre de los roles que están en la base de datos.

(Se llama Estructura_LDAP.png)

