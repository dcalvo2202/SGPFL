# Manual: Servidor LDAP con Docker para SGPFL

## ¿Qué es esto y para qué sirve?

El sistema SGPFL necesita un servidor **LDAP** para autenticar usuarios (iniciar sesión). En lugar de instalar y configurar ese servidor manualmente en tu computadora, usamos **Docker** para levantarlo en segundos dentro de un contenedor aislado.

Además, incluye **phpLDAPadmin**, una interfaz web visual para ver y administrar los usuarios y grupos del servidor LDAP, tal como se muestra en la imagen de la estructura del sistema.

> **En resumen:** Con dos comandos tendrás un servidor LDAP funcionando con todos los usuarios y grupos del sistema listos para usar.

---

## Requisitos previos

Antes de comenzar, necesitas tener instalado:

### Docker Desktop
- Descárgalo desde: **https://www.docker.com/products/docker-desktop**
- Instálalo siguiendo el asistente (siguiente, siguiente, finalizar)
- Una vez instalado, ábrelo y espera a que el ícono de la ballena 🐳 en la barra de tareas esté en verde (significa que está corriendo)

> ⚠️ En Windows, Docker Desktop requiere que **WSL 2** esté habilitado. El propio instalador te guiará si no lo tienes.

### Verificar que Docker está instalado correctamente

Abre una terminal (CMD o PowerShell) y ejecuta:

```bash
docker --version
```

Deberías ver algo como:
```
Docker version 24.x.x, build ...
```

---

## Estructura de archivos

Los archivos del servidor LDAP están en:

```
base/
└── ldap-docker/
    ├── docker-compose.yml        ← Define los 3 contenedores a levantar
    ├── Dockerfile                ← Imagen personalizada de OpenLDAP
    ├── README.md                 ← Referencia rápida de comandos
    └── bootstrap/
        ├── 01-estructura.ldif    ← Usuarios y grupos (usuarios primero, luego grupos)
        └── load-ldif.sh          ← Script de respaldo que carga el LDIF si el bootstrap falla
```

### ¿Para qué sirve cada archivo?

- **docker-compose.yml:** Orquesta los tres contenedores (OpenLDAP, phpLDAPadmin y el inicializador).
- **Dockerfile:** Construye la imagen de OpenLDAP copiando el LDIF dentro de ella. Es necesario porque en Windows no se puede montar directamente una carpeta sobre rutas internas del contenedor (ver sección de problemas conocidos).
- **01-estructura.ldif:** Define usuarios y grupos en formato LDIF. Los **usuarios se declaran antes que los grupos** porque `groupOfNames` requiere que los `member` referenciados ya existan en el directorio al momento de la carga.
- **load-ldif.sh:** Script de respaldo ejecutado por el contenedor `ldap-init`. Verifica si la estructura ya existe antes de cargar para evitar duplicados. Usa el hostname `openldap` (no `localhost`) para conectarse al servidor desde dentro de la red Docker.

---

## ¿Qué contiene el servidor LDAP?

El servidor se inicializa automáticamente con la siguiente estructura:

```
dc=una,dc=ac,dc=cr
├── ou=Groups
│   ├── cn=Administrador
│   ├── cn=Asesor Externo
│   ├── cn=CTFG
│   ├── cn=Estudiante
│   └── cn=Gestor Academico
└── ou=People
    ├── uid=205610158  → Administrador
    ├── uid=111710169  → Gestor Academico
    ├── uid=800810596  → Gestor Academico
    ├── uid=110600492  → CTFG
    ├── uid=503230754  → CTFG
    ├── uid=503020651  → CTFG
    ├── uid=206580363  → Estudiante
    ├── uid=503550224  → Estudiante
    ├── uid=504410118  → Estudiante
    ├── uid=504430777  → Estudiante
    ├── uid=118440202  → Estudiante
    ├── uid=402290345  → Estudiante
    ├── uid=116440018  → Estudiante
    ├── uid=105710421  → Asesor Externo
    ├── uid=800870458  → Asesor Externo
    ├── uid=205830110  → Asesor Externo
    ├── uid=107010122  → Asesor Externo
    └── uid=701810347  → Asesor Externo
```

### Usuarios del sistema

Todos los usuarios tienen la contraseña: **`secret123`**

| UID       | Nombre                           | Rol              |
|-----------|----------------------------------|------------------|
| 205610158 | Oscar Chaves Barrantes           | Administrador    |
| 111710169 | Miguel Arturo Corrales Ureña     | Gestor Academico |
| 800810596 | Yamileth Hernandez Cano          | Gestor Academico |
| 110600492 | Maikol Guzmán Alán               | CTFG             |
| 503230754 | Eddier López López               | CTFG             |
| 503020651 | Carlos Luis Chanto Espinoza      | CTFG             |
| 206580363 | Miguel Díaz Gutiérrez            | Estudiante       |
| 503550224 | Miguel Ángel Rodríguez Arias     | Estudiante       |
| 504410118 | Carlos Daniel López Chévez       | Estudiante       |
| 504430777 | Jose Domingo Molina Salas        | Estudiante       |
| 118440202 | Larissa Segura Arguello          | Estudiante       |
| 402290345 | Esteban Espinoza Fallas          | Estudiante       |
| 116440018 | Marco Antonio Murillo Sánchez    | Estudiante       |
| 105710421 | Georges Alfaro Salazar           | Asesor Externo   |
| 800870458 | Darinka Grbic Grbic              | Asesor Externo   |
| 205830110 | Katty Vásquez Ávila              | Asesor Externo   |
| 107010122 | Guiselle Víquez Jiménez          | Asesor Externo   |
| 701810347 | Jonathan Manrique Cordero Duarte | Asesor Externo   |

---

## Cómo levantar el servidor

### Paso 1 — Abrir una terminal en la carpeta correcta

**Opción A — Desde el explorador de archivos:**
1. Abre la carpeta `base/ldap-docker`
2. Haz clic en la barra de direcciones del explorador
3. Escribe `cmd` y presiona Enter

**Opción B — Desde CMD o PowerShell:**
```bash
cd C:\xampp\htdocs\base\ldap-docker
```

### Paso 2 — Levantar los contenedores

Siempre usa `--build` para asegurarte de que el LDIF más reciente quede dentro de la imagen:

```bash
docker-compose up -d --build
```

La primera vez tardará unos minutos porque Docker descargará las imágenes. Al finalizar verás:

```
[+] Running 3/3
 ✔ Container sgpfl-ldap       Started
 ✔ Container sgpfl-ldap-init  Started
 ✔ Container sgpfl-ldapadmin  Started
```

> El contenedor `sgpfl-ldap-init` actúa como respaldo para cargar la estructura. Una vez que termina, su estado cambia a `Exited (0)` — eso es normal y esperado.

### Paso 3 — Verificar que está corriendo

```bash
docker-compose ps
```

Resultado esperado:

```
NAME                STATUS
sgpfl-ldap          running
sgpfl-ldap-init     exited (0)    ← Normal, ya terminó su trabajo
sgpfl-ldapadmin     running
```

---

## Acceder a la interfaz gráfica (phpLDAPadmin)

1. Abre tu navegador y ve a: **http://localhost:8090**
2. Haz clic en **"login"** en el menú de la izquierda
3. Ingresa las credenciales del administrador LDAP:

| Campo    | Valor                           |
|----------|---------------------------------|
| DN       | `cn=admin,dc=una,dc=ac,dc=cr`   |
| Password | `admin`                         |

4. Haz clic en **"Authenticate"**

Verás el árbol de directorios con todos los grupos y usuarios, cada grupo con sus miembros asignados.

---

## Conectar el sistema SGPFL al servidor LDAP

El archivo `config.inc` del sistema ya está configurado para conectarse a este servidor sin cambios adicionales:

```php
$ldap_status    = 1;
$ldap_server[0] = "ldap://localhost:389";
$ldap_dn        = "dc=una,dc=ac,dc=cr";
$ldap_user      = "cn=admin,dc=una,dc=ac,dc=cr";
$ldap_pass      = "admin";
```

> ✅ No necesitas modificar nada. Solo levanta el contenedor y el sistema ya puede autenticar usuarios.

---

## Detener el servidor

```bash
docker-compose down
```

Los datos se conservan en volúmenes de Docker, por lo que la próxima vez que ejecutes `docker-compose up -d --build` todo estará igual.

---

## Reinicio completo (reset)

Si necesitas empezar desde cero, o si los grupos aparecen vacíos o la estructura no cargó correctamente:

```bash
docker-compose down -v
docker-compose up -d --build
```

- `-v` elimina los volúmenes borrando todos los datos almacenados
- `--build` reconstruye la imagen copiando el LDIF actualizado dentro de ella

Al volver a levantar, la estructura completa (usuarios y grupos con sus miembros) se recrea automáticamente.

---

## Problemas conocidos y soluciones

### ❌ Problema: La estructura LDAP no aparece tras el primer arranque

**Causa:** En Windows **no es posible montar una carpeta directamente sobre la ruta interna** que usa la imagen para el bootstrap (`/container/service/slapd/assets/config/bootstrap/ldif/custom/`), porque la imagen intenta eliminarla durante el arranque y falla con:

```
rm: cannot remove '...ldif/custom': Device or resource busy
```

**Solución implementada:** Se usa un `Dockerfile` que copia el LDIF dentro de la imagen durante el build. El contenedor auxiliar `ldap-init` actúa como respaldo en caso de que el bootstrap no cargue la estructura.

> ⚠️ Por esta razón siempre se debe usar `--build`. Sin él, la imagen no incluirá los cambios más recientes del LDIF.

---

### ❌ Problema: Los grupos aparecen con un solo miembro o vacíos

**Causa:** En LDIF, `groupOfNames` requiere que los usuarios referenciados como `member` **ya existan** en el directorio antes de que se cree el grupo. Si los grupos se definen antes que los usuarios, el servidor falla silenciosamente al agregar los miembros.

**Solución implementada:** El archivo `01-estructura.ldif` declara primero todos los usuarios (`ou=People`) y luego los grupos (`ou=Groups`). Si aun así los grupos aparecen vacíos, ejecuta el reset completo:

```bash
docker-compose down -v
docker-compose up -d --build
```

---

### ❌ Problema: Error "Unable to connect to LDAP server" en phpLDAPadmin

**Causa:** phpLDAPadmin intentó conectarse antes de que OpenLDAP terminara de inicializarse.

**Solución:** Espera unos segundos y recarga la página. Si persiste:

```bash
docker-compose ps        # verificar que sgpfl-ldap esté running
docker logs sgpfl-ldap   # revisar errores si está en Exited
```

---

### ❌ "docker-compose" no se reconoce como comando

En versiones nuevas de Docker Desktop el comando es:
```bash
docker compose up -d --build
```
(sin guion entre `docker` y `compose`)

---

### ❌ El puerto 389 ya está en uso

Edita `docker-compose.yml` y cambia el puerto externo:

```yaml
ports:
  - "3389:389"
```

Y actualiza `config.inc`:
```php
$ldap_server[0] = "ldap://localhost:3389";
```

---

## Referencia rápida de comandos

| Acción                          | Comando                          |
|---------------------------------|----------------------------------|
| Levantar / reconstruir          | `docker-compose up -d --build`   |
| Ver estado de contenedores      | `docker-compose ps`              |
| Ver logs en tiempo real         | `docker-compose logs -f`         |
| Ver logs solo de OpenLDAP       | `docker logs sgpfl-ldap`         |
| Ver logs del inicializador      | `docker logs sgpfl-ldap-init`    |
| Detener (conserva datos)        | `docker-compose down`            |
| Reset completo (borra datos)    | `docker-compose down -v`         |

---

**Versión:** 1.2
**Fecha:** 2025
**Aplica a:** SGPFL — Módulo de autenticación LDAP
