# Guía de Despliegue con Docker - SGPFL

## Sistema Gestor de Proyectos Finales de Licenciatura

> **Advertencia:** La configuración de Docker en este documento **NO está diseñada para producción**. Use únicamente para entornos de prueba, desarrollo local o demostraciones.

---

## 1. Cuándo Usar Docker

### 1.1 Usar Docker para

- Desarrollo local en máquina personal
- Pruebas de nuevas funcionalidades
- Demostración del sistema sin instalación de servicios
- Formación y capacitación

### 1.2 NO Usar Docker para

- Producción (use `MANUAL_DEPLOYMENT.md`)
- Sistemas con datos reales sensibles
- Alto volumen de usuarios simultáneos

---

## 2. Estructura de Docker

El proyecto incluye una configuración Docker que simula un entorno completo con:

```
docker/
├── docker-compose.yml      # Orquestación de servicios
├── Dockerfile              # Imagen PHP personalizada
├── php.ini                 # Configuración PHP
├── msmtp/                  # Configuración de correo
│   └── msmtprc
├── ldap-docker/            # Servidor LDAP simulado (para pruebas)
│   ├── docker-compose.yml
│   └── ldap/
└── .env                    # Variables de entorno
```

---

## 3. Servicios Incluidos

| Servicio | Imagen | Descripción |
|----------|--------|-------------|
| **App** | PHP 8.4 personalizado | Servidor web Apache con PHP |
| **DB** | MariaDB 10.11 | Base de datos MariaDB |
| **LDAP** | OpenLDAP | Servidor LDAP simulado |
| **LDAP Admin** | phpLDAPadmin | Interfaz administrativa para LDAP |

---

## 4. Requisitos

- Docker Engine 24.0+
- Docker Compose v2
- Puertos disponibles: 8080, 3306, 389, 8090

---

## 5. Configuración Inicial

### 5.1 Variables de Entorno

Crear archivo `.env` en el directorio `docker/`:

```bash
# Base de datos
DB_HOST=db
DB_NAME=base_db
DB_USER=sgpfl
DB_PASS=sgpfl1234

# LDAP (contenedor simulado)
LDAP_HOST=ldap://sgpfl-ldap:389
```

### 5.2 Editar docker-compose.yml

El archivo ya viene configurado para usar MariaDB:

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: sgpfl-app
    ports:
      - "8080:80"
    volumes:
      - ../:/var/www/html/base
      - ./msmtp:/etc/msmtp
    depends_on:
      - db
      - ldap
    environment:
      - DB_HOST=db
      - DB_NAME=base_db
      - DB_USER=sgpfl
      - DB_PASS=sgpfl1234
      - LDAP_HOST=ldap://sgpfl-ldap:389
    networks:
      - sgpfl-network

  db:
    image: mariadb:10.11
    container_name: sgpfl-db
    environment:
      MYSQL_ROOT_PASSWORD: root1234
      MYSQL_DATABASE: base_db
      MYSQL_USER: sgpfl
      MYSQL_PASSWORD: sgpfl1234
    ports:
      - "3306:3306"
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - sgpfl-network

  ldap:
    build: ../ldap-docker
    container_name: sgpfl-ldap
    ports:
      - "389:389"
    environment:
      LDAP_DOMAIN: una.ac.cr
      LDAP_ADMIN_PASSWORD: admin
    networks:
      - sgpfl-network

  ldap-admin:
    image: osixia/phpldapadmin
    container_name: sgpfl-ldapadmin
    ports:
      - "8090:80"
    environment:
      PHPLDAPADMIN_LDAP_HOSTS: ldap
      PHPLDAPADMIN_HTTPS: "false"
    depends_on:
      - ldap
    networks:
      - sgpfl-network

networks:
  sgpfl-network:
    driver: bridge

volumes:
  db-data:
```

---

## 6. Despliegue

### 6.1 Crear red compartida

```bash
docker network create sgpfl-shared
```

### 6.2 Iniciar el servidor LDAP

```bash
cd /var/www/html/base/ldap-docker
docker-compose up -d --build
```

**Verificar que el LDAP está funcionando:**

```bash
docker ps | grep sgpfl-ldap
docker logs sgpfl-ldap
```

**Acceso a phpLDAPadmin:** http://localhost:8090
- DN: `cn=admin,dc=una,dc=ac,dc=cr`
- Contraseña: `admin`

### 6.3 Iniciar la aplicación + MariaDB

```bash
cd /var/www/html/base/docker

# Iniciar los contenedores
docker-compose up -d --build
```

**Verificar servicios:**

```bash
docker ps
# Debe mostrar: sgpfl-app, sgpfl-db, sgpfl-ldap, sgpfl-ldapadmin
```

**Acceso al sistema:** http://localhost:8080/base/

### 6.4 Importar la base de datos

```bash
# Copiar archivo SQL al contenedor
docker cp ../base.sql sgpfl-app:/tmp/base.sql

# Importar
docker exec -i sgpfl-app mysql -u sgpfl -psgpfl1234 base_db < /tmp/base.sql
```

---

## 7. Configuración de Correo en Docker

### 7.1 Editar msmtprc

Editar `docker/msmtp/msmtprc` con las credenciales SMTP:

```ini
account default
host smtp.gmail.com
port 465
tls on
tls_starttls off
from rodri100ro@gmail.com
user rodri100ro@gmail.com
password tu_contraseña_de_aplicacion

logfile /var/log/msmtp.log
```

> **Nota:** Para Gmail, usar una contraseña de aplicación (no la contraseña normal).

### 7.2 Permisos

```bash
chmod 600 docker/msmtp/msmtprc
```

---

## 8. Configuración de LDAP en Docker

### 8.1 El contenedor LDAP simula un servidor institucional

Editar `config.inc` para conectar al LDAP simulado:

```php
$ldap_status = 1;
$ldap_server[0] = "ldap://sgpfl-ldap:389";  // Nombre del contenedor Docker
$ldap_dn = "dc=una,dc=ac,dc=cr";
$ldap_user = "cn=admin,dc=una,dc=ac,dc=cr";
$ldap_pass = "admin";
```

O usando la IP del contenedor:

```php
$ldap_status = 1;
$ldap_server[0] = "ldap://172.18.0.2:389";  // IP asignada por Docker
$ldap_dn = "dc=una,dc=ac,dc=cr";
$ldap_user = "cn=admin,dc=una,dc=ac,dc=cr";
$ldap_pass = "admin";
```

### 8.2 Obtener IP del contenedor LDAP

```bash
docker network inspect sgpfl-network | grep -A 5 sgpfl-ldap
```

---

## 9. Verificación del Despliegue

### 9.1 Ver logs

```bash
# Ver logs del contenedor PHP
docker logs sgpfl-app

# Ver logs de la base de datos
docker logs sgpfl-db

# Ver logs del LDAP
docker logs sgpfl-ldap
```

### 9.2 Probar acceso web

```bash
curl -I http://localhost:8080/base/
```

### 9.3 Probar conexión a la base de datos

```bash
docker exec -it sgpfl-app php -r "new mysqli('db', 'sgpfl', 'sgpfl1234', 'base_db'); echo 'OK';"
```

### 9.4 Probar conexión LDAP

```bash
docker exec -it sgpfl-app php -r "
\$conn = ldap_connect('ldap://sgpfl-ldap:389');
ldap_set_option(\$conn, LDAP_OPT_PROTOCOL_VERSION, 3);
\$bind = ldap_bind(\$conn, 'cn=admin,dc=una,dc=ac,dc=cr', 'admin');
echo \$bind ? 'LDAP OK' : 'LDAP FAIL';
"
```

---

## 10. Comandos Útiles

### 10.1 Gestión de contenedores

```bash
# Iniciar todos los servicios
docker-compose up -d

# Detener todos los servicios
docker-compose down

# Reiniciar un servicio específico
docker-compose restart app

# Ver logs en tiempo real
docker-compose logs -f

# Acceder al contenedor
docker exec -it sgpfl-app bash
```

### 10.2 Gestión de la base de datos

```bash
# Acceder a MySQL
docker exec -it sgpfl-db mysql -u sgpfl -psgpfl1234 base_db

# Realizar respaldo
docker exec -it sgpfl-db mysqldump -u sgpfl -psgpfl1234 base_db > backup.sql

# Restaurar respaldo
docker exec -i sgpfl-db mysql -u sgpfl -psgpfl1234 base_db < backup.sql
```

### 10.3 Gestión del LDAP

```bash
# Ver contenido LDAP
docker exec -it sgpfl-ldap ldapsearch -x -H ldap://localhost -b "dc=una,dc=ac,dc=cr" -D "cn=admin,dc=una,dc=ac,dc=cr" -W
```

---

## 11. Limitaciones en Docker

### 11.1 Limitaciones已知

- No optimizado para producción
- No incluye configuración de seguridad (firewall, fail2ban, etc.)
- Logs menos accesibles para diagnóstico
- Actualizaciones de seguridad dependen de la imagen base
- Rendimiento inferior a instalación nativa
- No diseñado para alto tráfico

### 11.2 Para desarrollo únicamente

Este entorno debe usarse exclusivamente para:
- Pruebas unitarias y de integración
- Desarrollo de nuevas funcionalidades
- Demostraciones a stakeholders
- Capacitación de usuarios

---

## 12. Solución de Problemas

### 12.1 Error: "Connection refused" al contenedor DB

```bash
# Verificar que el contenedor esté corriendo
docker ps | grep sgpfl-db

# Ver logs
docker logs sgpfl-db

# Verificar red
docker network inspect sgpfl-network
```

### 12.2 Error: "Can't connect to LDAP"

```bash
# Verificar que el contenedor LDAP esté corriendo
docker ps | grep sgpfl-ldap

# Verificar red entre contenedores
docker exec -it sgpfl-app ping sgpfl-ldap

# Ver IP del contenedor LDAP
docker inspect sgpfl-ldap | grep IPAddress
```

### 12.3 Error: "mail() returns false"

```bash
# Verificar configuración de msmtp
docker exec -it sgpfl-app cat /etc/msmtprc

# Probar msmtp manualmente
docker exec -it sgpfl-app sh -c "echo 'Subject: Test\n\nTest' | msmtp -a default test@example.com"
```

### 12.4 Puerto ya en uso

```bash
# Ver qué proceso usa el puerto
netstat -tuln | grep 8080

# Cambiar puerto en docker-compose.yml
ports:
  - "8081:80"
```

---

## 13. Limpieza

```bash
# Detener y eliminar contenedores
docker-compose down

# Eliminar volúmenes (¡CUIDADO: elimina los datos!)
docker-compose down -v

# Eliminar imágenes no usadas
docker system prune -a
```

---

*Documento actualizado: Mayo 2026*
*Versión del sistema: SGPFL 1.0*