# Sistema de Recuperación de Contraseña para Asesores Externos

## Descripción

Sistema completo de recuperación de contraseña con verificación por correo electrónico para asesores externos del SGPFL.

## Flujo del Sistema

```
1. Usuario hace clic en "Asesor Externo" en modal de recuperación (login.php)
2. Se abre cambiar_contrasena_asesor.php
3. Usuario ingresa su correo electrónico
4. Sistema valida que es asesor aprobado
5. Se genera token único y se guarda en BD
6. Se envía correo con link de verificación (válido 1 hora)
7. Usuario hace clic en el link
8. Formulario para ingresar nueva contraseña
9. Sistema valida y actualiza contraseña en sis_login
10. Token se marca como usado
11. Correo de confirmación
```

## Archivos Creados

### 1. **mod/login/cambiar_contrasena_asesor.php**
- Página principal del flujo de recuperación
- Presenta 2 interfaces:
  - Formulario para solicitar recuperación (por defecto)
  - Formulario para cambiar contraseña (si hay token válido en URL)

### 2. **mod/login/send_password_recovery_email.php**
- Endpoint API que recibe la solicitud del correo
- Valida que sea un asesor externo aprobado
- Genera token único de 64 caracteres
- Inserta en tabla `password_recovery_tokens`
- Envía correo HTML con link de verificación
- **Token expira en 1 hora**

### 3. **mod/login/reset_password_advisor.php**
- Endpoint API que recibe token + nueva contraseña
- Valida token (existe, no usado, no expirado)
- Actualiza contraseña en `sis_login`
- Marca token como usado
- Registra en bitácora (sis_log)
- Envía correo de confirmación

### 4. **inc/db/init_password_recovery.php**
- Inicializa la tabla automáticamente
- Se puede llamar como: `inc/db/init_password_recovery.php`

### 5. **base_add_password_recovery_table.sql**
- Script SQL para crear la tabla manualmente si es necesario

## Estructura de la Nueva Tabla

```sql
password_recovery_tokens
├── id (int) - PK, auto-increment
├── user_id (varchar 50) - Cédula del usuario
├── email (varchar 100) - Correo para registro
├── token (varchar 255) - Token único
├── type (varchar 50) - Tipo de usuario ('external_advisor')
├── used (tinyint) - 0/1 si fue usado
├── used_at (datetime) - Cuándo se usó
├── created_at (datetime) - Cuándo se creó
├── expires_at (datetime) - Vencimiento (NOW() + 1 hora)
```

## Instalación

### Opción 1: Inicialización Automática
Los scripts verificarán e inicializarán la tabla automáticamente. No necesita acción manual.

### Opción 2: Inicialización Manual

#### Via PHP:
```bash
# Acceder a través del navegador
http://localhost/base/inc/db/init_password_recovery.php
```

#### Via MySQL:
```bash
mysql -u usuario -p nombre_bd < base_add_password_recovery_table.sql
```

## Configuración Requerida

### 1. PHP Mail Configuration
Asegúrese que sendmail.ini está configurado correctamente:
```
# C:\xampp\sendmail\sendmail.ini
smtp_server=smtp.gmail.com
auth_username=su_correo@gmail.com
force_sender=noreply@una.cr
```

### 2. php.ini
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = noreply@una.cr
```

## Flujo de Uso por el Usuario

### Recuperar Contraseña:
1. Login → "¿Olvidó su nombre de usuario o contraseña?"
2. Modal → botón "Asesor Externo"
3. Ingresa correo electrónico
4. Recibe email con link (válido 1 hora)
5. Hace clic en el link
6. Ingresa nueva contraseña (mín. 8 caracteres)
7. Confirma la contraseña
8. ¡Listo! Puede iniciar sesión

## Seguridad

✅ **Implementado:**
- Tokens únicos de 64 caracteres (bin2hex random_bytes)
- Tokens con expiración de 1 hora
- Tokens de un solo uso
- Validación de email registrado
- Validación de asesor aprobado
- Sincronización automática de registros faltantes en sis_login
- Hash MD5 de contraseña (consistente con sistema)
- Contraseña mínimo 8 caracteres
- Registro en bitácora
- Correos HTML con confirmación
- No revela si usuario existe (seguridad)
- Limpieza de tokens antiguos

## Pruebas

### Test de Solicitud de Recuperación:
```javascript
// En consola del navegador
fetch('http://localhost/base/mod/login/send_password_recovery_email.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        email: 'test@email.com',
        user_type: 'external_advisor'
    })
}).then(r => r.json()).then(console.log);
```

### Verificar Base de Datos:
```sql
SELECT * FROM password_recovery_tokens ORDER BY created_at DESC LIMIT 5;
SELECT * FROM sis_log WHERE detail LIKE '%Cambio de contraseña%' LIMIT 5;
```

### Verificar Sincronización de Asesores Externos:
```sql
-- Asesores aprobados pero sin sis_login
SELECT ear.applicant_id, ear.full_name, ear.email
FROM external_advisor_profile_requests ear
LEFT JOIN sis_login sl ON ear.applicant_id = sl.id
WHERE ear.status = 'Aprobado' AND sl.id IS NULL;

-- Asesores aprobados pero sin sis_user
SELECT ear.applicant_id, ear.full_name, ear.email
FROM external_advisor_profile_requests ear
LEFT JOIN sis_user su ON ear.applicant_id = su.id
WHERE ear.status = 'Aprobado' AND su.id IS NULL;
```

## Troubleshooting

### Los correos no se envían:
1. Verificar sendmail.ini está en C:\xampp\sendmail\
2. Verificar credenciales de Gmail en sendmail.ini
3. Revisar C:\xampp\tmp\sendmail_error.log

### Token expira muy rápido:
1. Revisar que la hora del servidor sea correcta
2. Cambiar tiempo de expiración en `send_password_recovery_email.php` línea: `time() + 3600`

### "El usuario no está completamente registrado en el sistema"
**Solución:** Este error ya NO debería aparecer. El sistema crea automáticamente los registros faltantes en `sis_login` y `sis_user`.

Si aún aparece:
1. Verificar que email exacto en `external_advisor_profile_requests`
2. Verificar que status sea 'Aprobado'
3. Revisar logs en `C:\xampp\tmp\sendmail_error.log`

### Asesor no puede recuperar contraseña:
1. Abrir panel: `http://localhost/base/mod/login/verify_password_recovery.php`
2. Click en "Asesores Aprobados"
3. Verificar que muestre "✓" en columnas "En sis_login" y "En sis_user"
4. Si faltan, el sistema los creará automáticamente en el primer intento
5. Si problema persiste, verificar logs del servidor

## Personalización

### Cambiar tiempo de expiración del token:
**Archivo:** `send_password_recovery_email.php` línea ~82
```php
$expires_at = date('Y-m-d H:i:s', time() + 3600); // 3600 = 1 hora
// Cambiar a:
$expires_at = date('Y-m-d H:i:s', time() + 7200); // 7200 = 2 horas
```

### Cambiar requisito mínimo de contraseña:
**Archivo:** `cambiar_contrasena_asesor.php` línea ~65 y reset_password_advisor.php
```php
minlength="8" // Cambiar número
```

### Personalizar templates de email:
- **Email de inicio:** `send_password_recovery_email.php` línea ~125
- **Email de confirmación:** `reset_password_advisor.php` línea ~105

## Monitoreo

### Verificar intentos fallidos:
```sql
SELECT * FROM password_recovery_tokens 
WHERE used = 0 AND expires_at < NOW() 
ORDER BY created_at DESC;
```

### Verificar cambios exitosos:
```sql
SELECT * FROM password_recovery_tokens 
WHERE used = 1 
ORDER BY used_at DESC LIMIT 10;
```

## Notas Importantes

1. **Contraseña MD5:** El sistema usa MD5 para hashear contraseñas (consistente con sis_login)
2. **Zona horaria:** Asegúrese que la BD y PHP tienen la misma zona horaria
3. **Limpieza automática:** Los tokens expirados se limpian automáticamente (si MySQLEvents está habilitado)
4. **Rate limiting:** No implementado. Considere agregar si hay muchos intentos maliciosos
5. **HTTPS recomendado:** Para URLs de correo en producción

## Soporte

Para problemas contactar a: escinf@una.cr

---
**Versión:** 1.0  
**Fecha:** 2026-02-27  
**Autor:** Sistema de Recuperación de Contraseña SGPFL
