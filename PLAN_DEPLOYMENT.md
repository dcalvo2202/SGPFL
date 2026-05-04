# Plan de Despliegue - Sistema SGPFL

Este documento define los pasos para implementar el Sistema Gestor de Proyectos Finales de Licenciatura (SGPFL) en un servidor Linux en producción.

---

## 1. Requisitos del Servidor

### 1.1 Software Requerido

| Componente | Versión Mínima | Notas |
|------------|----------------|-------|
| Sistema Operativo | Ubuntu 20.04+ / Debian 11+ | O equivalente Linux |
| PHP | 7.4+ (Recomendado 8.4) | Con extensiones requeridas |
| Apache | 2.4+ | Módulo mod_rewrite habilitado |
| MySQL | 5.7+ o MariaDB 10.3+ | |
| Servidor LDAP | LDAPv3 | OpenLDAP o Active Directory |
| Servidor SMTP | - | Gmail, Outlook o institucional |

### 1.2 Extensiones PHP Requeridas

```bash
php-mysqli    # Conexión a MySQL
php-ldap      # Autenticación LDAP
php-zip       # Manejo de archivos comprimidos
php-mbstring  # Strings multibyte
php-fileinfo  # Validación de tipos de archivo
```

### 1.3 Permisos de Sistema

- Usuario del servidor web (www-data) con acceso de lectura/escritura a:
  - Directorio `/var/www/html/sgpfl/` (o ruta de instalación)
  - Directorio `/var/www/html/sgpfl/uploads/`
  - Directorio de logs

---

## 2. Estructura del Proyecto

```
/var/www/html/sgpfl/
├── inc/                    # Archivos de configuración y librerías
│   ├── db/
│   │   └── bdcommon.inc    # Conexión a la base de datos
│   └── ...
├── mod/                    # Módulos funcionales
├── uploads/                # Archivos subidos por usuarios
├── img/                    # Recursos gráficos
├── .htaccess               # Configuración Apache
└── config.inc              # Configuración principal del sistema
```

---

## 3. Pasos de Instalación

### Paso 1: Preparar el Servidor

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache, PHP y MySQL
sudo apt install -y apache2 php8.4 php8.4-mysqli php8.4-ldap php8.4-zip php8.4-mbstring php8.4-fileinfo mysql-server

# Habilitar mod_rewrite
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2
```

### Paso 2: Copiar Archivos del Proyecto

```bash
# Copiar archivos a la ruta del servidor
sudo cp -r /ruta/local/base/* /var/www/html/sgpfl/

# Ajustar permisos
sudo chown -R www-data:www-data /var/www/html/sgpfl/
sudo chmod -R 755 /var/www/html/sgpfl/
sudo chmod -R 775 /var/www/html/sgpfl/uploads/
```

### Paso 3: Configurar php.ini

**Ubicación típica:** `/etc/php/8.4/apache2/php.ini`

#### 3.1 Habilitar Extensiones

```ini
extension=mysqli
extension=ldap
extension=zip
extension=mbstring
extension=fileinfo
```

#### 3.2 Configurar Límites de Subida de Archivos

```ini
upload_max_filesize = 40M
post_max_size = 40M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

#### 3.3 Configurar Correo Electrónico

```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 465
sendmail_from = noreply@una.cr
sendmail_path = "/usr/sbin/sendmail -t -i"
```

> **Nota:** En Linux, sendmail puede ser postfix/sendmail. Ajuste según el agente de correo instalado.

### Paso 4: Configurar sendmail.ini / msmtp

Si usa sendmail.exe (XAMPP en Windows), en Linux configure:

**Archivo:** `/etc/msmtprc` (para msmtp) o configure el agente de correo del sistema

#### Configuración con msmtp (recomendado para Gmail):

```ini
account default
host smtp.gmail.com
port 465
tls on
tls_starttls off
from noreply@una.cr
auth on
user noreply@una.cr
password (contraseña de aplicación)
```

**Instalar msmtp:**
```bash
sudo apt install -y msmtp
sudo chmod 600 /etc/msmtprc
```

#### Alternativa: Configurar Postfix para Gmail

```bash
sudo apt install -y postfix
```

Editar `/etc/postfix/main.cf`:

```ini
relayhost = [smtp.gmail.com]:587
smtp_sasl_auth_enable = yes
smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd
smtp_tls_security_level = encrypt
```

### Paso 5: Configurar my.ini (MySQL)

**Ubicación típica:** `/etc/mysql/mysql.conf.d/mysqld.cnf` (Ubuntu/Debian)

```ini
[mysqld]
max_allowed_packet = 64M
innodb_log_file_size = 128M
innodb_buffer_pool_size = 256M
innodb_log_buffer_size = 8M
innodb_flush_log_at_trx_commit = 1
innodb_lock_wait_timeout = 50
character-set-server = utf8mb4
collation-server = utf8mb4_general_ci

# Para compatibilidad con aplicaciones antiguas
sql_mode = ''
```

**Reiniciar MySQL:**
```bash
sudo systemctl restart mysql
```

### Paso 6: Configurar bdcommon.inc

**Ubicación:** `/var/www/html/sgpfl/inc/db/bdcommon.inc`

```php
<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$usuario = getenv('DB_USER') ?: 'root';
$clave   = getenv('DB_PASS') ?: '';
$db      = getenv('DB_NAME') ?: 'base_db';
$DBMS = 'mysql';
$base_url = '/sgpfl/';
?>
```

**Para producción, configure valores explícitos:**

```php
<?php
$db_host = 'localhost';
$usuario = 'sgpfl_user';
$clave   = 'contraseña_segura';
$db      = 'base_db';
$DBMS = 'mysql';
$base_url = '/sgpfl/';
?>
```

### Paso 7: Configurar config.inc

**Ubicación:** `/var/www/html/sgpfl/config.inc`

```php
<?php
// Configuración del servidor
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$cds_domain = $protocol . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/";

$base_real_path = str_replace('\\', '/', __DIR__);
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html');
$relative_from_root = str_replace($doc_root, '', $base_real_path);
$cds_locate = $relative_from_root . '/';

// Configuración LDAP
$ldap_status = 1;
$ldap_server[0] = getenv('LDAP_HOST') ?: "ldap://ldap.servidor.edu:389";
$ldap_dn = "dc=una,dc=ac,dc=cr";
$ldap_user = "cn=admin,dc=una,dc=ac,dc=cr";
$ldap_pass = "contraseña_ldap";

// Preferencias
$favicon_url = rtrim($cds_locate, '/') . "/img/logo.webp";
$page_cant = 10;
$page_title = "SGPFL";
$footer_title = "Sistema Gestor de Proyectos Finales de Licenciatura<br/>Escuela de Informática, Universidad Nacional";

// Acciones y Módulos
$act1 = 1; $act2 = 2; $act3 = 3; $act4 = 4; $act5 = 5; $act6 = 6;
$mod1 = 1; $mod2 = 2; $mod3 = 3; $mod4 = 4; $mod5 = 5;
$mod6 = 6; $mod7 = 7; $mod8 = 8;

// ============================================
// CONFIGURACIÓN DE CORREO ELECTRÓNICO
// ============================================
if (!defined('SYSTEM_EMAIL_FROM')) {
    define('SYSTEM_EMAIL_FROM', 'noreply@una.cr');
}
if (!defined('SYSTEM_EMAIL_FROM_NAME')) {
    define('SYSTEM_EMAIL_FROM_NAME', 'SGPFL - Escuela de Informática');
}
if (!defined('SYSTEM_EMAIL_REPLY_TO')) {
    define('SYSTEM_EMAIL_REPLY_TO', 'escinf@una.cr');
}

// Configuración de alertas de vencimiento
if (!defined('DEADLINE_THRESHOLDS')) {
    define('DEADLINE_THRESHOLDS', [30, 15, 7, 3, 1]);
}
if (!defined('DEADLINE_EMAIL_ENABLED')) {
    define('DEADLINE_EMAIL_ENABLED', true);
}
if (!defined('DEADLINE_FROM_EMAIL')) {
    define('DEADLINE_FROM_EMAIL', SYSTEM_EMAIL_FROM);
}
if (!defined('DEADLINE_FROM_NAME')) {
    define('DEADLINE_FROM_NAME', SYSTEM_EMAIL_FROM_NAME);
}

// Configuración de Google Calendar (producción)
$google_calendar_config = [
    'client_id'        => getenv('GOOGLE_CLIENT_ID')        ?: '',
    'client_secret'    => getenv('GOOGLE_CLIENT_SECRET')    ?: '',
    'redirect_uri'     => getenv('GOOGLE_REDIRECT_URI')     ?: 'https://suservidor.edu/sgpfl/auth/google_callback.php',
    'scopes'           => ['https://www.googleapis.com/auth/calendar'],
    'token_cipher'     => 'aes-256-gcm',
    'token_cipher_key' => getenv('GOOGLE_TOKEN_CIPHER_KEY') ?: 'CLAVE_AES_256_EN_PRODUCCION',
];
?>
```

### Paso 8: Crear Base de Datos

```bash
# Acceder a MySQL
sudo mysql -u root -p

# Crear base de datos y usuario
CREATE DATABASE base_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'sgpfl_user'@'localhost' IDENTIFIED BY 'contraseña_segura';
GRANT ALL PRIVILEGES ON base_db.* TO 'sgpfl_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importar esquema
mysql -u sgpfl_user -p base_db < /var/www/html/sgpfl/base.sql
```

### Paso 9: Configurar Apache (Virtual Host)

**Archivo:** `/etc/apache2/sites-available/sgpfl.conf`

```apache
<VirtualHost *:80>
    ServerName suservidor.edu
    ServerAlias www.suservidor.edu
    DocumentRoot /var/www/html/sgpfl

    <Directory /var/www/html/sgpfl>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sgpfl_error.log
    CustomLog ${APACHE_LOG_DIR}/sgpfl_access.log combined
</VirtualHost>
```

```bash
# Habilitar sitio
sudo a2ensite sgpfl.conf
sudo a2enmod ssl  # Si usa HTTPS

# Reiniciar Apache
sudo systemctl reload apache2
```

### Paso 10: Configurar HTTPS (Recomendado para Producción)

```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtener certificado
sudo certbot --apache -d suservidor.edu
```

---

## 4. Verificación del Sistema

### 4.1 Verificar Extensiones PHP

```bash
php -m | grep -E "mysqli|ldap|zip|mbstring"
```

### 4.2 Probar Conexión a MySQL

```bash
mysql -u sgpfl_user -p -e "SHOW DATABASES;"
```

### 4.3 Probar Conexión LDAP

Crear script de prueba `/var/www/html/sgpfl/test_ldap.php`:

```php
<?php
$ldapconn = ldap_connect("ldap://ldap.servidor.edu:389");
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
$bind = ldap_bind($ldapconn, "cn=admin,dc=una,dc=ac,r", "contraseña");
echo $bind ? "LDAP: OK" : "LDAP: ERROR";
ldap_close($ldapconn);
?>
```

### 4.4 Probar Envío de Correo

```php
<?php
$to = "destinatario@ejemplo.com";
$subject = "Prueba SGPFL";
$message = "Correo de prueba desde el sistema.";
$headers = "From: noreply@una.cr\r\n";
$headers .= "Reply-To: escinf@una.cr\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

if(mail($to, $subject, $message, $headers)) {
    echo "Correo enviado correctamente.";
} else {
    echo "Error al enviar correo.";
}
?>
```

### 4.5 Verificar desde Navegador

Acceda a `https://suservidor.edu/sgpfl/` y confirme:
- Página de login visible
- Conexión a base de datos exitosa
- Autenticación LDAP funcionando

---

## 5. Mantenimiento y Respaldo

### 5.1 Respaldo de Base de Datos

```bash
# Programar en cron (diario a las 2am)
mysqldump -u sgpfl_user -p base_db > /backup/base_db_$(date +\%Y\%m\%d).sql
```

### 5.2 Respaldo de Archivos Críticos

```bash
# Respaldar configuración
tar -czf /backup/sgpfl_config_$(date +\%Y\%m\%d).tar.gz \
  /var/www/html/sgpfl/config.inc \
  /var/www/html/sgpfl/inc/db/bdcommon.inc \
  /etc/msmtprc
```

### 5.3 Respaldar Archivos Subidos

```bash
tar -czf /backup/sgpfl_uploads_$(date +\%Y\%m\%d).tar.gz /var/www/html/sgpfl/uploads/
```

---

## 6. Solución de Problemas Comunes

| Problema | Causa Probable | Solución |
|----------|----------------|----------|
| Error de conexión LDAP | Servidor/dominio incorrecto | Verificar `$ldap_server[0]` en config.inc |
| No se envían correos | sendmail.ini o msmtprc mal configurado | Revisar credenciales y puertos (465 SSL) |
| "MySQL server has gone away" | max_allowed_packet bajo | Aumentar en my.ini y reiniciar MySQL |
| No carga la base de datos | Credenciales incorrectas | Verificar bdcommon.inc |
| Listados incompletos | `$page_cant` muy bajo | Ajustar en config.inc |
| Error de permisos | Usuario www-data sin acceso | `chown -R www-data:www-data /var/www/html/sgpfl/` |
| Archivo muy grande al subir | Límites PHP superados | Ajustar upload_max_filesize y post_max_size en php.ini |

---

## 7. Checklist de Despliegue

- [ ] Servidor con SO Linux instalado
- [ ] Apache, PHP y MySQL instalados
- [ ] Extensiones PHP habilitadas (mysqli, ldap, zip, mbstring, fileinfo)
- [ ] Archivos copiados al servidor
- [ ] Permisos correctos asignados
- [ ] php.ini configurado (extensiones, límites, correo)
- [ ] my.ini configurado (max_allowed_packet, charset)
- [ ] bdcommon.inc configurado (credenciales BD)
- [ ] config.inc configurado (dominio, LDAP, email)
- [ ] Base de datos creada e importada
- [ ] Virtual Host de Apache configurado
- [ ] Certificados SSL instalados (HTTPS)
- [ ] Pruebas de funcionamiento exitosas
- [ ] Documentación de respaldo configurada

---

## 8. Notas de Seguridad

1. **Nunca** almacene contraseñas en texto plano en repositorios
2. Use **contraseñas de aplicación** para Gmail (no la contraseña normal)
3. Active HTTPS en producción
4. Restrinja permisos de archivos de configuración (`chmod 600`)
5. Mantenga el sistema y dependencias actualizados
6. Configure firewall (ufw) para permitir solo puertos necesarios
7. Use la constante `SYSTEM_EMAIL_FROM` para el correo Remitente oficial

---

**Documento generado:** Mayo 2026
**Sistema:** SGPFL - Sistema Gestor de Proyectos Finales de Licenciatura