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
