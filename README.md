## Introducción

Este documento describe la configuración técnica y los parámetros esenciales del sistema **SGPFL (Sistema Gestor de Proyectos Finales de Licenciatura)**. Su objetivo es servir como guía de referencia para nuevos administradores o personal de soporte que requieran comprender, mantener o desplegar el sistema en diferentes entornos.

El contenido abarca los archivos de configuración principales, la estructura de base de datos, la integración con el servicio de autenticación LDAP y las configuraciones necesarias en PHP para garantizar el funcionamiento correcto del sistema.

A lo largo del documento se detallan los siguientes aspectos:

* Archivos de configuración del sistema (`config.inc`, `dbcommon.inc`).
* Integración con el servidor LDAP y sincronización con la base de datos.
* Configuración de versiones y dependencias (LDAPv3, PHP, MySQL).
* Parámetros de envío de correos electrónicos (archivos `php.ini` y `sendmail.ini`).
* Consideraciones de seguridad y despliegue en entornos productivos.

Esta documentación debe mantenerse actualizada conforme se realicen cambios en el entorno del servidor o en la estructura interna del sistema, con el fin de asegurar su estabilidad, compatibilidad y seguridad operativa.


# Configuración del sistema

## Configuración requerida en `php.ini`

Para que el sistema funcione correctamente, es necesario habilitar y configurar ciertos módulos en el archivo de configuración de PHP (`php.ini`). Estos componentes permiten la conexión con LDAP, la base de datos MySQL y el envío de correos electrónicos institucionales.

---

### 1. Extensiones obligatorias

Asegúrese de que las siguientes extensiones estén habilitadas en `php.ini`:

```ini
extension=ldap
extension=mysqli
```

* **ldap:** Permite la autenticación y comunicación con el servidor LDAP.
* **mysqli:** Gestiona la conexión con la base de datos MySQL utilizada por el sistema.

> Si las líneas anteriores están comentadas (precedidas por `;`), deben descomentarse eliminando el punto y coma.

---

### 2. Configuración de envío de correos

Para el envío de notificaciones institucionales desde el sistema, debe configurarse el remitente predeterminado:

```ini
sendmail_from = correo@institucional.edu
```

* **sendmail_from:** Dirección de correo que se utilizará como remitente en los mensajes automáticos generados por el sistema.

  * Debe pertenecer al dominio institucional o autorizado por el servidor SMTP correspondiente.

---

### 3. Consideraciones adicionales

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

---

### 1. Configuración básica

Ejemplo de configuración actual:

```ini
force_sender=rodri100ro@gmail.com
auth_username=rodri100ro@gmail.com
auth_password=(contraseña de app de Gmail)
```

* **force_sender:** Dirección de correo electrónico que el sistema utilizará como remitente en todos los mensajes salientes.

  * Este valor debe pertenecer al dominio autorizado por el servicio de correo utilizado.
  * En entornos institucionales, se recomienda reemplazarlo por una cuenta oficial (por ejemplo, `no-reply@institucional.edu`).

* **auth_username:** Usuario utilizado para la autenticación en el servidor SMTP.

  * Generalmente coincide con la dirección del remitente.

* **auth_password:** Contraseña de aplicación o token generado para la cuenta configurada.

  * En el caso de Gmail, debe usarse una **contraseña de aplicación** generada desde la cuenta con autenticación en dos pasos activada.

---

### 2. Consideraciones de seguridad

* Nunca almacene contraseñas reales en texto plano dentro de entornos compartidos o repositorios.
* Si es posible, limite los permisos de lectura del archivo `sendmail.ini` solo al usuario que ejecuta el servidor web.
* Para entornos productivos, se recomienda configurar un **correo institucional dedicado** y no una cuenta personal.

---

### 3. Verificación de funcionamiento

Una vez configurado, puede probar el envío de correos ejecutando un script PHP sencillo:

```php
<?php
$to = "destinatario@institucional.edu";
$subject = "Prueba de correo desde el sistema";
$message = "Este es un correo de prueba para verificar la configuración de sendmail.";
$headers = "From: rodri100ro@gmail.com";

if(mail($to, $subject, $message, $headers)) {
    echo "Correo enviado correctamente.";
} else {
    echo "Error al enviar el correo.";
}
?>
```

Si el mensaje se envía correctamente, la configuración del archivo `sendmail.ini` es funcional.

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

```php
$ldap_status = 1;
$ldap_server[0] = "ldap://localhost:389";
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

```php
$db_host = 'localhost';
$usuario = 'root';
$clave = '';
$db = 'base_db';
$DBMS = 'mysql';
```

* **$db_host:** Dirección del servidor de base de datos.

  * Valor por defecto: `'localhost'`
  * Debe reemplazarse por el dominio, nombre de host o dirección IP del servidor de base de datos en el entorno productivo.

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

* Todos los valores con `'localhost'` deben ser reemplazados por la información del servidor donde esté alojada la base de datos.
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
