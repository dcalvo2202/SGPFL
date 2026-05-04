# Guía de Despliegue Manual en Producción - SGPFL

## Sistema Gestor de Proyectos Finales de Licenciatura

Este documento describe los pasos completos para desplegar el sistema SGPFL en un entorno de **producción** utilizando instalación manual (servidor dedicado con Apache + PHP + MariaDB).

> **Importante:** Esta guía está enfocada en el despliegue **manual** para producción. Para entornos de desarrollo/pruebas con Docker, consulte `DOCKER_DEPLOYMENT.md`.

---

## 1. Prerrequisitos

### 1.1 Requisitos de Hardware

| Recurso | Mínimo | Recomendado |
|---------|--------|-------------|
| CPU | 2 núcleos | 4+ núcleos |
| RAM | 4 GB | 8 GB |
| Almacenamiento | 20 GB | 50+ GB (depende del volumen de archivos subidos) |
| Tipo de disco | HDD | SSD (recomendado para mejor rendimiento) |

### 1.2 Requisitos de Software

- **Sistema Operativo**: Ubuntu 22.04 LTS (recomendado), Ubuntu 20.04 LTS, Debian 11+
- **Servidor Web**: Apache 2.4+ (con módulo SSL)
- **PHP**: PHP 8.4 (con extensiones: mysqli, ldap, zip, mbstring, fileinfo, openssl)
- **Base de Datos**: MariaDB 10.11 LTS

### 1.3 Puertos Requeridos

| Puerto | Servicio | Propósito |
|--------|----------|-----------|
| 80 | HTTP | Acceso web sin cifrar (redirecciona a 443) |
| 443 | HTTPS | Acceso web con SSL/TLS |
| 3306 | MariaDB | Conexión a la base de datos (solo localhost o red interna) |

### 1.4 Dependencias Externas

- **Servidor LDAP**: Servidor LDAPv3 institucional existente (el sistema se conecta a un servidor LDAP externo, no crea uno propio)
- **Servidor SMTP**: Servidor para envío de correos electrónicos institucionales (Gmail, Outlook, SendGrid, o SMTP institucional)

---

## 2. Arquitectura del Sistema

### 2.1 Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENTE (Navegador)                      │
└──────────────────────────────┬──────────────────────────────────┘
                                │ HTTPS (443)
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SERVIDOR WEB (Apache)                        │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   PHP 8.4                                │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────────────────┐   │   │
│  │  │ SGPFL    │  │ LDAPS    │  │ Email (sendmail/     │   │   │
│  │  │ App      │──│ Client   │  │ msmtp)               │   │   │
│  │  └──────────┘  └──────────┘  └──────────────────────┘   │   │
│  └─────────────────────────────────────────────────────────┘   │
└──────────────────────────────┬──────────────────────────────────┘
                                │
               ┌────────────────┼────────────────┐
               ▼                ▼                ▼
        ┌────────────┐   ┌────────────┐   ┌────────────┐
        │ MariaDB    │   │   LDAP     │   │   SMTP     │
        │ 10.11      │   │  Server    │   │  Server    │
        │ (Puerto    │   │ (externo)  │   │ (Puerto    │
        │  3306)     │   │            │   │  25/465/587)│
        └────────────┘   └────────────┘   └────────────┘
```

### 2.2 Flujo de Datos

1. **Usuario** accede al sistema vía HTTPS (navegador → Apache)
2. **PHP** procesa la solicitud, conecta a **LDAP** para autenticación (servidor externo)
3. **PHP** consulta/almacena datos en **MariaDB**
4. **PHP** envía notificaciones via **SMTP** cuando es necesario
5. **Archivos** (propuestas, minutas, actas) se almacenan en la base de datos como BLOBs

### 2.3 Estructura de Archivos del Proyecto

```
/var/www/html/base/          ← Raíz del proyecto (ajustar según instalación)
├── config.inc               ← Configuración principal (dominio, LDAP, módulos)
├── inc/db/bdcommon.inc      ← Conexión a base de datos
├── base.sql                 ← Esquema de la base de datos
├── mod/                     ← Módulos funcionales del sistema
├── uploads/                 ← Archivos subidos por usuarios
├── docker/                  ← Configuración Docker (para pruebas únicamente)
├── documentation/           ← Documentación del sistema
└── manuales/               ← Manuales técnicos adicionales
```

---

## 3. Variables de Entorno

El sistema utiliza variables de entorno para la configuración de conexiones externas. Si no se definen, usa valores por defecto.

### 3.1 Base de Datos

| Variable | Descripción | Valor por Defecto |
|----------|-------------|-------------------|
| `DB_HOST` | Servidor de base de datos | `localhost` |
| `DB_USER` | Usuario de la base de datos | `root` |
| `DB_PASS` | Contraseña de la base de datos | (vacío) |
| `DB_NAME` | Nombre de la base de datos | `base_db` |

### 3.2 LDAP

| Variable | Descripción | Valor por Defecto |
|----------|-------------|-------------------|
| `LDAP_HOST` | Servidor LDAP externo (incluir protocolo y puerto) | `ldap://localhost:389` |

### 3.3 Uso en el Código

```php
// En inc/db/bdcommon.inc
$db_host = getenv('DB_HOST') ?: 'localhost';
$usuario = getenv('DB_USER') ?: 'root';
$clave   = getenv('DB_PASS') ?: '';
$db      = getenv('DB_NAME') ?: 'base_db';

// En config.inc
$ldap_server[0] = getenv('LDAP_HOST') ?: "ldap://localhost:389";
```

---

## 4. Despliegue Manual en Producción

### 4.1 Instalación de Paquetes Base

```bash
# Actualizar repositorios
sudo apt update && sudo apt upgrade -y

# Instalar Apache, PHP y extensiones requeridas
sudo apt install -y apache2 php8.4 php8.4-mysqli php8.4-ldap php8.4-zip php8.4-mbstring php8.4-xml php8.4-curl php8.4-gd

# Instalar MariaDB (versión 10.11 LTS)
sudo apt install -y mariadb-server

# Instalar herramientas adicionales
sudo apt install -y openssl certbot python3-certbot-apache mailutils
```

### 4.2 Configurar Apache - Virtual Host

Crear archivo de configuración del Virtual Host:

```bash
sudo nano /etc/apache2/sites-available/sgpfl.conf
```

```apache
<VirtualHost *:80>
    ServerName sgpfl.tudominio.com
    ServerAlias www.sgpfl.tudominio.com
    DocumentRoot /var/www/html/base

    # Redireccionar HTTP a HTTPS
    Redirect permanent / https://sgpfl.tudominio.com/

    ErrorLog ${APACHE_LOG_DIR}/sgpfl_error.log
    CustomLog ${APACHE_LOG_DIR}/sgpfl_access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName sgpfl.tudominio.com
    ServerAlias www.sgpfl.tudominio.com
    DocumentRoot /var/www/html/base

    # Configuración SSL (ver Sección 5)
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/sgpfl.crt
    SSLCertificateKeyFile /etc/ssl/private/sgpfl.key
    SSLCertificateChainFile /etc/ssl/certs/sgpfl-chain.crt

    # Configuración PHP
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

    DirectoryIndex index.php index.html

    <Directory /var/www/html/base>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Bloquear acceso a archivos sensibles
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/sgpfl_ssl_error.log
    CustomLog ${APACHE_LOG_DIR}/sgpfl_ssl_access.log combined
</VirtualHost>
```

Activar el sitio y reiniciar Apache:

```bash
sudo a2enmod ssl rewrite
sudo a2ensite sgpfl
sudo systemctl reload apache2
```

### 4.3 Configurar PHP (php.ini)

```bash
sudo nano /etc/php/8.4/apache2/php.ini
```

Configuraciones requeridas:

```ini
[PHP]
; Extensiones obligatorias
extension=mysqli
extension=ldap
extension=zip
extension=mbstring

; Límites de subida de archivos
upload_max_filesize = 40M
post_max_size = 40M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M

; Zona horaria
date.timezone = America/Costa_Rica

; Configuración de correo
sendmail_path = /usr/sbin/sendmail
sendmail_from = noreply@tudominio.com

; Errores (Off en producción)
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

### 4.4 Configurar MariaDB (my.cnf)

```bash
sudo nano /etc/mysql/mariadb.conf.d/50-server.cnf
```

```ini
[mysqld]
# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_general_ci

# Optimización para archivos grandes (BLOBs)
max_allowed_packet = 64M
innodb_log_file_size = 128M
innodb_buffer_pool_size = 256M
innodb_log_buffer_size = 8M

# Conexiones
max_connections = 150
wait_timeout = 60

# Innodb settings
innodb_flush_log_at_trx_commit = 1
innodb_lock_wait_timeout = 50

[client]
default-character-set = utf8mb4
```

Reiniciar MariaDB:

```bash
sudo systemctl restart mariadb
```

### 4.5 Importar la base de datos

```bash
# Crear la base de datos y usuario
sudo mysql -u root -p << EOF
CREATE DATABASE base_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'sgpfl'@'localhost' IDENTIFIED BY 'tu_contraseña_segura';
GRANT ALL PRIVILEGES ON base_db.* TO 'sgpfl'@'localhost';
FLUSH PRIVILEGES;
EOF

# Importar el esquema
sudo mysql -u sgpfl -p base_db < /var/www/html/base/base.sql
```

### 4.6 Configurar credenciales en bdcommon.inc

```php
<?php
// inc/db/bdcommon.inc
$db_host = 'localhost';
$usuario = 'sgpfl';
$clave   = 'tu_contraseña_segura';
$db      = 'base_db';
$DBMS = 'mysql';
$base_url = '/base/';
?>
```

---

## 5. Configuración de Certificados SSL/TLS (Sectigo)

Esta sección cubre la configuración de certificados SSL comerciales de Sectigo Limited.

### 5.1 Obtener el certificado de Sectigo

1. **Generar CSR (Certificate Signing Request)**:

```bash
# Generar clave privada y CSR
sudo openssl req -new -newkey rsa:4096 -nodes -keyout sgpfl.key -out sgpfl.csr -days 365

#填入以下信息:
# Country: CR
# State: San Jose
# Locality: Heredia
# Organization: Universidad Nacional
# Organizational Unit: Escuela de Informatica
# Common Name: sgpfl.tudominio.com
# Email: webmaster@tudominio.com
```

2. **Enviar el CSR a Sectigo** a través del portal o proveedor.
3. **Recibir los certificados**:
   - `sgpfl.crt` - Certificado del servidor
   - `sgpfl-chain.crt` - Cadena de certificados (Intermediate + Root)
   - (La clave privada `sgpfl.key` generada en el paso 1)

### 5.2 Instalar certificados en Apache

```bash
# Mover certificados a ubicaciones seguras
sudo cp sgpfl.crt /etc/ssl/certs/sgpfl.crt
sudo cp sgpfl-chain.crt /etc/ssl/certs/sgpfl-chain.crt
sudo cp sgpfl.key /etc/ssl/private/sgpfl.key

# Ajustar permisos
sudo chmod 600 /etc/ssl/private/sgpfl.key
sudo chmod 644 /etc/ssl/certs/sgpfl.crt
sudo chmod 644 /etc/ssl/certs/sgpfl-chain.crt
```

### 5.3 Actualizar configuración de Apache

Agregar las directivas SSL al Virtual Host:

```apache
SSLEngine on
SSLCertificateFile /etc/ssl/certs/sgpfl.crt
SSLCertificateKeyFile /etc/ssl/private/sgpfl.key
SSLCertificateChainFile /etc/ssl/certs/sgpfl-chain.crt

# Opciones de seguridad adicionales
SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
SSLCipherSuite HIGH:!aNULL:!MD5
SSLHonorCipherOrder on
```

```bash
# Verificar configuración
sudo apache2ctl configtest

# Reiniciar Apache
sudo systemctl restart apache2
```

### 5.4 Forzar HTTPS en config.inc

El archivo `config.inc` ya detecta automáticamente HTTPS:

```php
// En config.inc - Esto detecta HTTPS automáticamente
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$cds_domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/";
```

### 5.5 Renovación del certificado

Para renovar el certificado cuando expire:

1. Generar nuevo CSR (o usar el mismo si se renueva con el mismo proveedor)
2. Enviar a Sectigo y recibir el nuevo certificado
3. Reemplazar archivos:
   ```bash
   sudo cp nuevo_sgpfl.crt /etc/ssl/certs/sgpfl.crt
   sudo cp nueva_cadena.crt /etc/ssl/certs/sgpfl-chain.crt
   sudo systemctl reload apache2
   ```

---

## 6. Configuración de LDAP (Servidor Externo)

El sistema SGPFL se conecta a un **servidor LDAP institucional existente** para la autenticación de usuarios. El sistema no crea su propio servidor LDAP, se conecta a uno externo.

### 6.1 Conectar al Servidor LDAP Existente

Editar `config.inc` con los datos del LDAP institucional:

```php
$ldap_status = 1;
$ldap_server[0] = "ldap://ldap.institucional.edu:389";  // IP o hostname del servidor LDAP existente
$ldap_dn = "dc=institucion,dc=ac,dc=cr";
$ldap_user = "cn=admin,dc=institucion,dc=ac,dc=cr";
$ldap_pass = "contraseña_admin_ldap";
```

Ejemplo con IP del servidor:

```php
$ldap_status = 1;
$ldap_server[0] = "ldap://192.168.1.100:389";  // IP del servidor LDAP institucional
$ldap_dn = "dc=una,dc=ac,dc=cr";
$ldap_user = "cn=admin,dc=una,dc=ac,dc=cr";
$ldap_pass = "admin";
```

### 6.2 Verificar Conexión LDAP

Probar la conexión al servidor LDAP externo:

```bash
# Desde el servidor PHP (usando IP del servidor LDAP)
php -r "
\$conn = ldap_connect('ldap://192.168.1.100:389');
ldap_set_option(\$conn, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option(\$conn, LDAP_OPT_REFERRALS, 0);
\$bind = ldap_bind(\$conn, 'cn=admin,dc=una,dc=ac,dc=cr', 'admin');
echo \$bind ? 'LDAP OK' : 'LDAP FAIL';
"

# O con hostname institucional
php -r "
\$conn = ldap_connect('ldap://ldap.una.cr:389');
ldap_set_option(\$conn, LDAP_OPT_PROTOCOL_VERSION, 3);
\$bind = ldap_bind(\$conn, 'cn=admin,dc=una,dc=ac,dc=cr', 'admin');
echo \$bind ? 'LDAP OK' : 'LDAP FAIL';
"
```

### 6.3 Grupos LDAP y Roles en la Base de Datos

El sistema mapea grupos LDAP a roles en la tabla `sis_rolls`:

| Grupo LDAP | Rol en sis_rolls |
|------------|------------------|
| Administrador | Administrador |
| Gestor Academico | Gestor Académico |
| CTFG | CTFG |
| Estudiante | Estudiante |
| Asesor Externo | Asesor Externo |

Asegurarse de que los nombres de grupos en LDAP coincidan exactamente con los valores en `sis_roll.roll_name`.

---

## 7. Configuración de Correo/SMTP

El sistema SGPFL utiliza la función `mail()` de PHP para enviar notificaciones por correo electrónico.

### 7.1 Opciones de Configuración de Correo

| Método | Ventajas | Desventajas |
|--------|----------|-------------|
| **msmtp** (recomendado) | Simple, estable, soporta TLS | Requiere instalación adicional |
| **sendmail** | Nativo en Linux | Limitado, sin autenticación SMTP |
| **PEAR Mail** | Portable, muchas opciones | Requiere paquete adicional |

### 7.2 Método Recomendado: msmtp

**msmtp** es un cliente SMTP liviano que reemplaza sendmail y permite autenticación TLS/SSL.

#### 7.2.1 Instalación

```bash
# Ubuntu/Debian
sudo apt install -y msmtp msmtp-mta

# Verificar instalación
which msmtp
msmtp --version
```

#### 7.2.2 Configuración Global

Crear archivo de configuración global:

```bash
sudo nano /etc/msmtprc
```

Contenido para Gmail:

```ini
account default
host smtp.gmail.com
port 587
tls on
tls_starttls on
from noreply@tu-dominio.com
auth on
user tu-correo@dominio.com
password TU_CONTRASEÑA_APP

logfile /var/log/msmtp.log
timeout 30
```

Para servidor SMTP institucional (ejemplo: UNA):

```ini
account default
host smtp.una.cr
port 587
tls on
tls_starttls on
from noreply@una.cr
auth on
user noreply@una.cr
password CONTRASEÑA_INSTITUCIONAL
```

Para puerto 465 (SSL implícito):

```ini
account default
host smtp.tu-servidor.com
port 465
tls on
tls_starttls off
from noreply@tu-dominio.com
auth on
user usuario_smtp
password CONTRASEÑA
```

#### 7.2.3 Permisos de Seguridad

```bash
sudo chmod 600 /etc/msmtprc
sudo chown root:root /etc/msmtprc
```

#### 7.2.4 Configurar PHP para usar msmtp

Editar `php.ini`:

```bash
sudo nano /etc/php/8.4/apache2/php.ini
```

```ini
[mail function]
; Usar msmtp como sendmail
sendmail_path = "/usr/bin/msmtp -t"

; Remitente por defecto
sendmail_from = noreply@tu-dominio.com
```

Reiniciar Apache:

```bash
sudo systemctl restart apache2
```

### 7.3 Método Alternativo: sendmail (XAMPP/Windows)

Si usa XAMPP en Windows, configure `sendmail.ini`:

```bash
# Editar: C:\xampp\sendmail\sendmail.ini

[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
smtp_ssl=auto
auth_username=tu-correo@gmail.com
auth_password=CONTRASEÑA_APP
force_sender=tu-correo@gmail.com
logfile=C:\xampp\sendmail\sendmail.log
```

Y en `php.ini`:

```ini
[mail function]
sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"
SMTP = localhost
smtp_port = 25
```

### 7.4 Configuración del Sistema (config.inc)

El sistema usa constantes definidas en `config.inc` para los correos del sistema:

```php
// En config.inc
if (!defined('SYSTEM_EMAIL_FROM')) {
    define('SYSTEM_EMAIL_FROM', 'noreply@tu-dominio.com');
}

if (!defined('SYSTEM_EMAIL_FROM_NAME')) {
    define('SYSTEM_EMAIL_FROM_NAME', 'SGPFL - Escuela de Informática');
}

if (!defined('DEADLINE_FROM_EMAIL')) {
    define('DEADLINE_FROM_EMAIL', SYSTEM_EMAIL_FROM);
}

if (!defined('DEADLINE_FROM_NAME')) {
    define('DEADLINE_FROM_NAME', SYSTEM_EMAIL_FROM_NAME);
}
```

### 7.5 Crear Contraseña de Aplicación (Gmail)

Para usar Gmail como servidor SMTP, debe crear una **contraseña de aplicación**:

1. Ir a https://myaccount.google.com/security
2. Habilitar **Verificación en 2 pasos**
3. Ir a "Contraseñas de aplicación" (en sección "Iniciar sesión en Google")
4. Crear nueva contraseña para "Correo"
5. Usar esa contraseña en la configuración de msmtp

### 7.6 Proveedores SMTP Comunes

| Proveedor | Servidor SMTP | Puerto | TLS |
|-----------|--------------|--------|-----|
| Gmail | smtp.gmail.com | 587 | STARTTLS |
| Gmail (SSL) | smtp.gmail.com | 465 | SSL |
| Outlook | smtp.office365.com | 587 | STARTTLS |
| UNA | smtp.una.cr | 587 | STARTTLS |
| SendGrid | smtp.sendgrid.net | 587 | STARTTLS |

> **Importante:** Para Gmail y Outlook, debe usar una **contraseña de aplicación** (App Password), no su contraseña normal.

### 7.7 Probar Envío de Correo

```bash
php -r "
\$to = 'destino@ejemplo.com';
\$subject = 'Prueba SGPFL';
\$message = '<html><body><h1>Prueba</h1><p>Correo de prueba desde el sistema SGPFL</p></body></html>';
\$headers = 'From: noreply@tu-dominio.com' . PHP_EOL .
           'MIME-Version: 1.0' . PHP_EOL .
           'Content-Type: text/html; charset=UTF-8';

if(mail(\$to, \$subject, \$message, \$headers)) {
    echo 'OK: Correo enviado';
} else {
    echo 'FAIL: Error al enviar';
}
"
```

#### 7.7.2 Prueba con msmtp directo

```bash
# Crear archivo de prueba
echo -e "Subject: Prueba SGPFL\n\nCorreo de prueba desde msmtp" | msmtp -a default destino@ejemplo.com
```

#### 7.7.3 Verificar Logs

```bash
# Logs de msmtp
sudo tail -f /var/log/msmtp.log

# Logs de PHP
sudo tail -f /var/log/apache2/error.log

# Logs del sistema (mail)
sudo tail -f /var/log/mail.log
```

### 7.8 Solución de Problemas de Correo

| Problema | Causa | Solución |
|----------|-------|----------|
| `msmtp: could not connect` | Firewall bloqueando puerto | Abrir puerto 587/465 en firewall |
| `msmtp: authentication failed` | Contraseña incorrecta | Usar contraseña de aplicación |
| `SSL/TLS error` | Certificado no válido | Usar `tls_certcheck off` temporalmente |
| Correos no llegan | spam | Verificar bandeja de spam |
| `sendmail_path` no encontrado | PHP no trova sendmail | Verificar ruta en php.ini |

### 7.9 Scripts de Notificación del Sistema

El sistema envía correos en estos eventos:

| Evento | Archivo | Función |
|--------|---------|---------|
| Recordatorio de vencimiento | `inc/deadline_functions.php` | `sendDeadlineEmail()` |
| Nueva propuesta | `inc/tfg_proposal_functions.php` | Notificación a Gestor |
| Cambio de estado | `inc/tfg_final_functions.php` | Notificación a estudiante |
| Recuperación de contraseña | `mod/login/send_password_recovery_email.php` | Envío de token |

---

## 8. Configuración de Tareas Programadas (Cron)

### 8.1 Linux (cron)

```bash
sudo crontab -e
```

Agregar línea para ejecutar diariamente a las 8:00 AM:

```cron
0 8 * * * /usr/bin/php /var/www/html/base/cron_check_deadlines.php >> /var/log/sgpfl_cron.log 2>&1
```

Verificar:

```bash
sudo crontab -l
tail -f /var/log/sgpfl_cron.log
```

### 8.2 Windows (Programador de Tareas)

1. Abrir **Programador de Tareas** (Taskschd.msc)
2. Crear tarea básica:
   - **Nombre**: SGPFL-DeadlineCheck
   - **Desencadenador**: Diariamente a las 8:00 AM
   - **Acción**: Iniciar un programa
   - **Programa**: `C:\xampp\php\php.exe`
   - **Argumentos**: `C:\xampp\htdocs\base\cron_check_deadlines.php`

---

## 9. Lista de Verificación de Seguridad

### 9.1 Permisos de archivos

```bash
# Archivos del proyecto
sudo chown -R www-data:www-data /var/www/html/base
sudo chmod -R 755 /var/www/html/base
sudo chmod -R 775 /var/www/html/base/uploads

# Archivos sensibles - denegar lectura pública
sudo chmod 600 /var/www/html/base/config.inc
sudo chmod 600 /var/www/html/base/inc/db/bdcommon.inc
sudo chmod 600 /etc/ssl/private/sgpfl.key
```

### 9.2 Configuración de firewall (UFW)

```bash
sudo apt install -y ufw

sudo ufw default deny incoming
sudo ufw default allow outgoing

sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS

sudo ufw enable
sudo ufw status verbose
```

### 9.3 Contraseñas seguras

- **MariaDB**: Mínimo 16 caracteres, mezcla de mayúsculas, minúsculas, números y símbolos
- **LDAP Admin**: Cambiar la contraseña por defecto "admin"
- **SMTP**: Usar contraseña de aplicación (no contraseña de cuenta)

### 9.4 Archivos sensibles a excluir

```
config.inc
inc/db/bdcommon.inc
docker/msmtp/msmtprc
.env
*.key
*.pem
```

### 9.5 Headers de seguridad

Agregar a Apache `.htaccess` o Virtual Host:

```apache
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

```bash
sudo a2enmod headers
sudo systemctl reload apache2
```

---

## 10. Verificación Post-Despliegue

### 10.1 Verificar servicios

```bash
sudo systemctl status apache2
sudo systemctl status mariadb
```

### 10.2 Pruebas de funcionalidad

| Prueba | Procedimiento | Esperado |
|--------|---------------|----------|
| **Acceso web** | Navegar a https://sgpfl.tudominio.com/ | Página de login carga |
| **Login LDAP** | Iniciar sesión con credenciales LDAP | Redirecciona al panel |
| **Subir archivo** | Estudiante → Subir propuesta TFG | Archivo se guarda correctamente |
| **Correo** | Recuperar contraseña | Llega correo con link |
| **Descarga** | Descargar archivo subido | Archivo descarga con nombre legible |
| **Cron** | Ejecutar `cron_check_deadlines.php` | Sin errores en log |

---

## 11. Mantenimiento y Respaldo

### 11.1 Respaldo de base de datos

```bash
sudo crontab -e
# Agregar:
0 2 * * * mysqldump -u sgpfl -p'tu_password' base_db | gzip > /backup/base_db_$(date +\%Y\%m\%d).sql.gz
```

### 11.2 Respaldo de archivos

```bash
rsync -av /var/www/html/base/uploads /backup/
rsync -av /var/www/html/base/config.inc /backup/
rsync -av /etc/ssl/ /backup/ssl/
```

---

## 12. Solución de Problemas Comunes

### 12.1 Error: "Connection refused" a MariaDB

```bash
sudo systemctl status mariadb
sudo systemctl restart mariadb
```

### 12.2 Error: "Can't connect to LDAP server"

**Causa**: No se puede comunicar con el servidor LDAP externo.

```bash
# Probar conexión directa
ldapsearch -H ldap://192.168.1.100:389 -D "cn=admin,dc=una,dc=ac,dc=cr" -W -b "dc=una,dc=ac,dc=cr"

# Verificar que el servidor LDAP esté accesible
ping 192.168.1.100
telnet 192.168.1.100 389
```

### 12.3 Error: "MySQL server has gone away"

```ini
# En my.cnf
max_allowed_packet = 64M
```

### 12.4 Error: "mail() returns false"

```bash
tail -f /var/log/mail.log
sudo systemctl restart apache2
```

### 12.5 Error: SSL certificate not trusted

```bash
openssl s_client -connect sgpfl.tudominio.com:443 -showcerts
```

---

*Documento actualizado: Mayo 2026*
*Versión del sistema: SGPFL 1.0*