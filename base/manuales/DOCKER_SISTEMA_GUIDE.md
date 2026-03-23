# Manual: Entorno Docker para SGPFL (Apache + MySQL)

## ¿Qué es esto y para qué sirve?

Este entorno Docker permite correr el sistema SGPFL completo **sin necesidad de XAMPP**. Levanta dos contenedores:

- **sgpfl-app** — Apache + PHP 8.4 con todas las extensiones requeridas y soporte de correo
- **sgpfl-db** — MySQL 8.0 con la base de datos importada automáticamente

Además se conecta con el servidor LDAP definido en `ldap-docker/` a través de una red compartida.

> El sistema sigue funcionando en XAMPP sin cambios, ya que `bdcommon.inc` y `config.inc` usan variables de entorno con fallback a `localhost`.

---

## Requisitos previos

- Docker Desktop instalado y corriendo (ver manual `LDAP_DOCKER_GUIDE.md`)
- Haber configurado las credenciales de correo en `docker/msmtp/msmtprc`

---

## Estructura de archivos

```
base/
└── docker/
    ├── docker-compose.yml     ← Orquesta app (PHP+Apache) y db (MySQL)
    ├── php/
    │   ├── Dockerfile         ← Imagen PHP 8.4 + Apache con extensiones
    │   └── php.ini            ← Configuración PHP (límites, correo, zona horaria)
    ├── msmtp/
    │   └── msmtprc            ← Credenciales SMTP (equivalente a sendmail.ini de XAMPP)
    └── mysql/
        └── my.cnf             ← Configuración MySQL (charset, max_allowed_packet)
```

### ¿Para qué sirve cada archivo?

- **Dockerfile:** Construye la imagen PHP+Apache instalando las extensiones `mysqli`, `ldap`, `zip`, `mbstring`, `fileinfo` y la herramienta `msmtp` para envío de correos.
- **php.ini:** Aplica los límites de subida de archivos, zona horaria y configura `mail()` para usar `msmtp` como relay SMTP.
- **msmtprc:** Define las credenciales del servidor SMTP. Es el equivalente directo al `sendmail.ini` de XAMPP. **Debes editarlo antes de levantar el entorno.**
- **my.cnf:** Configura MySQL con charset `utf8mb4` y `max_allowed_packet = 64M` para soportar archivos almacenados en la base de datos.

---

## Configuración antes de levantar

### 1. Credenciales de correo (`msmtprc`)

El archivo `docker/msmtp/msmtprc` ya tiene las credenciales tomadas directamente de tu `sendmail.ini` de XAMPP:

| Campo en `msmtprc` | Campo en `sendmail.ini` de XAMPP | Valor actual          |
|--------------------|----------------------------------|-----------------------|
| `host`             | `smtp_server`                    | `smtp.gmail.com`      |
| `port`             | `smtp_port`                      | `465`                 |
| `tls_starttls off` | `smtp_ssl=ssl`                   | SSL directo           |
| `from`             | `force_sender`                   | `rodri100ro@gmail.com`|
| `user`             | `auth_username`                  | `rodri100ro@gmail.com`|
| `password`         | `auth_password`                  | contraseña de app     |

> Si cambias las credenciales, edita `docker/msmtp/msmtprc` y vuelve a levantar con `docker-compose up -d --build`.

### 2. Credenciales de base de datos

Las credenciales están definidas directamente en `docker-compose.yml`. Si las cambias, deben coincidir en los dos servicios (`app` y `db`):

```yaml
# Servicio db
MYSQL_USER: sgpfl
MYSQL_PASSWORD: sgpfl1234

# Servicio app
DB_USER: sgpfl
DB_PASS: sgpfl1234
```

---

## Cómo levantar el entorno

### Paso 1 — Crear la red compartida con LDAP

La red compartida debe existir antes de levantar cualquiera de los dos compose. Solo se crea una vez:

```bash
docker network create sgpfl-shared
```

### Paso 2 — Levantar el servidor LDAP

```bash
cd base/ldap-docker
docker-compose up -d --build
```

### Paso 3 — Levantar el sistema (Apache + MySQL)

```bash
cd base/docker
docker-compose up -d --build
```

La primera vez tardará varios minutos porque Docker descarga las imágenes y compila las extensiones PHP.

### Paso 4 — Verificar que todo está corriendo

```bash
docker ps
```

Resultado esperado:

```
NAME                STATUS
sgpfl-app           running
sgpfl-db            running
sgpfl-ldap          running
sgpfl-ldap-init     exited (0)    ← Normal
sgpfl-ldapadmin     running
```

---

## Acceder al sistema

| Servicio       | URL                      | Notas                              |
|----------------|--------------------------|------------------------------------|
| Sistema SGPFL  | http://localhost:8080/base/ | Apache + PHP                    |
| phpLDAPadmin   | http://localhost:8090    | Interfaz gráfica del servidor LDAP |
| MySQL          | localhost:3306           | Usuario: `sgpfl` / Pass: `sgpfl1234` |

---

## Cómo funciona la conexión entre contenedores

Los tres compose se comunican a través de la red externa `sgpfl-shared`:

```
sgpfl-app ──── sgpfl-shared ──── sgpfl-ldap
    │
sgpfl-net
    │
sgpfl-db
```

- `sgpfl-app` se conecta a MySQL usando el hostname `db` (interno a `sgpfl-net`)
- `sgpfl-app` se conecta a LDAP usando el hostname `sgpfl-ldap` (a través de `sgpfl-shared`)
- Las variables de entorno inyectadas al contenedor `app` son:

```yaml
DB_HOST:   db
DB_NAME:   base_db
DB_USER:   sgpfl
DB_PASS:   sgpfl1234
LDAP_HOST: ldap://sgpfl-ldap:389
```

Estas son leídas por `bdcommon.inc` y `config.inc` con `getenv()`. Si no existen (XAMPP), usan `localhost` como fallback.

---

## Extensiones PHP instaladas

| Extensión  | Uso en el sistema                              |
|------------|------------------------------------------------|
| `mysqli`   | Conexión a MySQL en `db.php`                   |
| `ldap`     | Autenticación en `class.AuthLdap.php`          |
| `zip`      | Manejo de archivos comprimidos en uploads      |
| `mbstring` | Manejo de strings multibyte                    |
| `fileinfo` | Validación de tipos de archivos subidos        |

---

## Configuración PHP aplicada

Valores tomados directamente del `php.ini` de XAMPP:

| Parámetro            | Valor                  | Fuente                           |
|----------------------|------------------------|----------------------------------|
| `upload_max_filesize`| 40M                    | `php.ini` XAMPP                  |
| `post_max_size`      | 40M                    | `php.ini` XAMPP                  |
| `max_execution_time` | 300s                   | `php.ini` XAMPP                  |
| `max_input_time`     | 300s                   | `php.ini` XAMPP                  |
| `memory_limit`       | 512M                   | `php.ini` XAMPP                  |
| `sendmail_from`      | `rodri100ro@gmail.com` | `php.ini` XAMPP                  |
| `sendmail_path`      | `/usr/bin/msmtp -t`    | Relay SMTP para `mail()` nativo  |
| `date.timezone`      | `America/Costa_Rica`   | Zona horaria del sistema         |
| `display_errors`     | `On`                   | Cambiar a `Off` en producción    |
| `error_reporting`    | `E_ALL`                | Cambiar a valor restrictivo en producción |

## Configuración MySQL aplicada

Valores tomados directamente del `my.ini` de XAMPP:

| Parámetro                       | Valor            |
|---------------------------------|------------------|
| `character-set-server`          | `utf8mb4`        |
| `collation-server`              | `utf8mb4_general_ci` |
| `max_allowed_packet`            | `64M`            |
| `innodb_log_file_size`          | `128M`           |
| `innodb_buffer_pool_size`       | `16M`            |
| `innodb_log_buffer_size`        | `8M`             |
| `innodb_flush_log_at_trx_commit`| `1`              |
| `innodb_lock_wait_timeout`      | `50`             |

---

## Detener el entorno

```bash
# Solo detener (conserva datos)
cd base/docker
docker-compose down

cd base/ldap-docker
docker-compose down
```

---

## Reset completo (borrar todos los datos)

```bash
cd base/docker
docker-compose down -v

cd base/ldap-docker
docker-compose down -v

# Volver a levantar
cd base/ldap-docker
docker-compose up -d --build

cd base/docker
docker-compose up -d --build
```

>  `-v` elimina los volúmenes. La base de datos se reimporta desde `base.sql` y el LDAP se recarga desde el LDIF automáticamente.

---

## Problemas conocidos y soluciones

### ❌ Error "network sgpfl-shared not found"

La red compartida no fue creada antes de levantar los compose.

```bash
docker network create sgpfl-shared
```

---

### ❌ El sistema no conecta a LDAP

Verifica que el contenedor LDAP esté corriendo y en la red compartida:

```bash
docker network inspect sgpfl-shared
```

Debes ver `sgpfl-ldap` y `sgpfl-app` listados como contenedores conectados.

---

### ❌ La base de datos no se importó

El `base.sql` solo se importa la **primera vez** que se crea el volumen. Si el volumen ya existía, no se reimporta. Solución:

```bash
cd base/docker
docker-compose down -v
docker-compose up -d --build
```

---

### ❌ Los correos no se envían

1. Verifica que `msmtprc` tenga las credenciales correctas
2. Revisa el log de msmtp dentro del contenedor:

```bash
docker exec sgpfl-app cat /var/log/msmtp.log
```

3. Para Gmail, asegúrate de usar una **contraseña de aplicación** (no la contraseña normal)

---

### ❌ Error de permisos en `/uploads`

```bash
docker exec sgpfl-app chmod -R 775 /var/www/html/uploads
docker exec sgpfl-app chown -R www-data:www-data /var/www/html/uploads
```

---

## Referencia rápida de comandos

| Acción                              | Comando                                          |
|-------------------------------------|--------------------------------------------------|
| Crear red compartida (solo 1 vez)   | `docker network create sgpfl-shared`             |
| Levantar sistema                    | `docker-compose up -d --build` (en `docker/`)    |
| Levantar LDAP                       | `docker-compose up -d --build` (en `ldap-docker/`) |
| Ver todos los contenedores          | `docker ps`                                      |
| Ver logs del sistema                | `docker logs sgpfl-app`                          |
| Ver logs de MySQL                   | `docker logs sgpfl-db`                           |
| Acceder al contenedor PHP           | `docker exec -it sgpfl-app bash`                 |
| Detener todo (conserva datos)       | `docker-compose down` en cada carpeta            |
| Reset completo                      | `docker-compose down -v` en cada carpeta         |

---

**Versión:** 1.0
**Fecha:** 2025
**Aplica a:** SGPFL — Entorno completo Docker (Apache + MySQL + LDAP)
