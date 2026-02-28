# Guía Rápida de Implementación

## Sistema de Recuperación de Contraseña para Asesores Externos

###  Archivos Creados

```
mod/login/
├── cambiar_contrasena_asesor.php          ← Página principal del flujo
├── send_password_recovery_email.php       ← API: Envía email con token
├── reset_password_advisor.php             ← API: Cambia la contraseña
└── verify_password_recovery.php           ← Panel de diagnóstico (localhost)

inc/db/
└── init_password_recovery.php             ← Inicializa tabla de tokens

base_add_password_recovery_table.sql       ← Script SQL alternativo
RECOVERY_PASSWORD_README.md                ← Documentación completa
INSTALL_QUICK_GUIDE.md                    ← Esta guía
```

## ¿Qué hace?

Cuando un asesor externo presiona el botón "Asesor Externo" en el modal de recuperación de contraseña (login.php), ahora:

1. ✅ Se abre un formulario para ingresar correo
2. ✅ Se valida que sea asesor externo aprobado
3. ✅ Se genera token único (64 caracteres)
4. ✅ Se envía correo HTML con link (válido 1 hora)
5. ✅ Usuario hace clic en link
6. ✅ Ingresa nueva contraseña (mín. 8 caracteres)
7. ✅ Contraseña se actualiza en BD
8. ✅ Se registra en bitácora
9. ✅ Se envía correo de confirmación

## Pasos de Implementación

### 1️⃣ Crear la Tabla de Tokens (Elegir UNO)

#### Opción A: Automática (Sin hacer nada)
- Los scripts inicializan la tabla automáticamente
- No requiere acción manual

#### Opción B: Manual por URL
1. Abrir en navegador:
```
http://localhost/base/inc/db/init_password_recovery.php
```
2. Debe ver mensaje: "Tabla de recuperación de contraseña inicializada"

#### Opción C: Manual por MySQL
```bash
mysql -u root -p base_db < base_add_password_recovery_table.sql
```

### 2️⃣ Verificar Configuración de Correos

✅ Revisar que `C:\xampp\sendmail\sendmail.ini` tenga:
```ini
smtp_server=smtp.gmail.com
auth_username=rodri100ro@gmail.com
force_sender=noreply@una.cr
```

### 3️⃣ Panel de Diagnóstico (Testing)

Acceder a (solo en localhost):
```
http://localhost/base/mod/login/verify_password_recovery.php
```

Este panel permite:
- ✓ Ver estado del sistema
- ✓ Listar asesores aprobados
- ✓ Ver tokens generados
- ✓ Ver cambios de contraseña
- ✓ **Generar token de prueba Y probarlo**

### 4️⃣ Prueba Completa del Flujo

#### Desde el Panel de Diagnostico:
1. Click en "Test Email"
2. Ingresa email de asesor aprobado (ej: `guiselle.viquez@gmail.com`)
3. Click "Generar Token de Prueba"
4. Copia la URL que aparece
5. Pega en navegador
6. Forma aparece para cambiar contraseña
7. Ingresa nueva contraseña
8. ¡Listo!

#### Desde el Login Normal:
1. Abrir `http://localhost/base/login.php`
2. Click en "¿Olvidó su contraseña?"
3. Click en botón "Asesor Externo"
4. Ingresa correo de asesor aprobado
5. Revisa correo (o carpeta de spam)
6. Hace clic en link del correo
7. Ingresa nueva contraseña
8. ¡Listo!

## Validación SQL

Verificar que todo funciona:

```sql
-- Ver tabla creada
SHOW TABLES LIKE 'password_recovery_tokens';

-- Ver estructura
DESC password_recovery_tokens;

-- Ver tokens recientes
SELECT id, user_id, email, used, created_at, expires_at 
FROM password_recovery_tokens 
ORDER BY created_at DESC LIMIT 5;

-- Ver cambios de contraseña
SELECT id_user, date_bi, detail 
FROM sis_log 
WHERE detail LIKE '%Cambio de contraseña%' 
LIMIT 5;
```

## Solución de Problemas

### ❌ "Token inválido o expirado"
-  Verifica que la hora del servidor sea correcta
- El token expira en 1 hora desde su creación

### ❌ Los correos no llegan
1. Revisa `C:\xampp\tmp\sendmail_error.log`
2. Verifica credenciales en `sendmail.ini`
3. Verifica que `force_sender` esté correcto

### ❌ "No encontramos un asesor externo"
- Verifica email exacto en `external_advisor_profile_requests`
- Verifica que status sea 'Aprobado'
- Verifica que `applicant_id` exista en `sis_login`

### ❌ Tabla no se crea
- Ejecutar manualmente desde MySQL:
```sql
CREATE TABLE `password_recovery_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL UNIQUE,
  `type` varchar(50) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`token`),
  KEY `idx_user_type` (`user_id`, `type`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

## Variables Personalizables

### Tiempo de expiración del token
**Archivo:** `send_password_recovery_email.php` línea ~82
```php
$expires_at = date('Y-m-d H:i:s', time() + 3600); // 3600 segundos = 1 hora
// Cambiar a: time() + 7200 para 2 horas
```

### Longitud mínima de contraseña
**Archivos:** 
- `cambiar_contrasena_asesor.php` línea ~69
- `reset_password_advisor.php` línea ~28
```php
minlength="8" // Cambiar número según necesidad
```

### Remitente de correo
**Archivo:** `cambiar_contrasena_asesor.php` y `reset_password_advisor.php`
```php
$headers .= "From: noreply@una.cr\r\n"; // Cambiar si es necesario
```

## URLs del Sistema

| Página | URL |
|--------|-----|
| Inicio de recuperación | `/base/mod/login/cambiar_contrasena_asesor.php` |
| Con token (después de email) | `/base/mod/login/cambiar_contrasena_asesor.php?token=ABC...` |
| API: Enviar email | `/base/mod/login/send_password_recovery_email.php` (POST) |
| API: Reset password | `/base/mod/login/reset_password_advisor.php` (POST) |
| Panel diagnóstico | `/base/mod/login/verify_password_recovery.php` (localhost only) |

## Seguridad Implementada

✅ Tokens únicos de 64 caracteres
✅ Expiración de 1 hora
✅ Un solo uso por token
✅ Validación de asesor aprobado
✅ Contraseña mínimo 8 caracteres
✅ Hash MD5 (consistente con sistema)
✅ Registro en bitácora
✅ No revela si usuario existe (previene enumeration)
✅ Limpieza de tokens antiguos
✅ CORS headers seguros
✅ Prepared statements para SQL injection

## Próximos Pasos Opcionales

1. **Rate limiting:** Agregar límite de intentos por IP
2. **HTTPS:** Usar en producción
3. **2FA:** Agregar verificación de 2 factores
4. **Notificación al admin:** Registrar intentos fallidos
5. **SMS alternativo:** Opción de envío por SMS

## Soporte

Para problemas contactar: escinf@una.cr

---

**Versión:** 1.0  
**Fecha:** 2026-02-27  
**Estado:** ✅ Listo para usar
