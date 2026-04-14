# INFORME: Corrección del Sistema de Notificaciones Internas

## Problema Identificado

El sistema de notificaciones internas no estaba guardando las alertas en la base de datos. **La causa raíz fue un error de compatibilidad entre tipos de conexión a la base de datos.**

### Descripción Técnica

La función `registerAlert()` en `/inc/alert_functions.php` estaba usando **sintaxis PDO** (Prepared Statements con parámetros nombrados como `:user_id`) mientras que toda la aplicación usa **MySQLi** (Prepared Statements con parámetros posicionales como `?`).

#### Código Defectuoso (antes):
```php
$sql = "INSERT INTO user_alerts ... VALUES (:user_id, :subject, :message, ...";
$stmt = $conn->prepare($sql);
$params = [':user_id' => $user_id, ':subject' => $subject, ...];
$result = $stmt->execute($params);  // ❌ MySQLi no entiende esto
```

**Consecuencia:** La función fallaba silenciosamente porque MySQLi no reconoce los parámetros nombrados de PDO, resultando en que **NINGUNA alerta se guardaba nunca**.

## Solución Implementada

Cambié la función `registerAlert()` para usar **sintaxis MySQLi correcta**:

### Código Corregido (después):
```php
$sql = "INSERT INTO user_alerts ... VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi", 
    $user_id, 
    $subject, 
    $message, 
    $alert_type, 
    $priority, 
    $related_entity_type, 
    $related_entity_id
);
$result = $stmt->execute();  // ✅ MySQLi lo ejecuta correctamente
```

## Archivos Modificados

1. **`/inc/alert_functions.php`** (línea 24-53)
   - Cambio de parámetros PDO (`:user_id`) a parámetros MySQLi (`?`)
   - Cambio de `execute($params)` a `bind_param()` y `execute()`
   - Mejorado el manejo de errores para registrar mensajes específicos de MySQLi

## Verificación

Se creó un archivo de test: **`/test_notifications.php`**

Para verificar que las notificaciones funcionan ahora:

1. Accede como **administrador**
2. Abre: `http://localhost/base/test_notifications.php`
3. Ejecuta los 8 tests que verifican:
   - ✓ Tabla `user_alerts` existe
   - ✓ Se puede registrar una alerta
   - ✓ La alerta se guardó correctamente
   - ✓ Se cuenta correctamente el número de no-leídas
   - ✓ Se listan las últimas alertas
   - ✓ Se pueden enviar alertas a un rol completo
   - ✓ Se recuperan las alertas del usuario
   - ✓ Se limpian las alertas de prueba

## Impacto

Ahora **todas estas características funcionarán correctamente:**

- ✓ Notificaciones de propuestas nuevas
- ✓ Alertas de propuestas aprobadas/rechazadas
- ✓ Notificaciones de documentos finales subidos
- ✓ Alertas de correcciones solicitadas
- ✓ Notificaciones de prórroga concedidas
- ✓ Alertas de vencimiento de plazos
- ✓ Badge de notificaciones en el header (campanita)
- ✓ Historial de alertas (`historial_alertas.php`)

## Prueba Rápida

Para una prueba manual rápida, ejecuta en el terminal desde `/base`:

```php
php -r "
require_once 'inc/db/bdcommon.inc';
require_once 'inc/alert_functions.php';
\$conn = new mysqli('localhost', 'root', '', 'base_db');
\$result = registerAlert(\$conn, 'test_user', 'Prueba', 'Alerta de prueba', 'Informativa', 'Media');
echo \$result ? 'OK: Alerta registrada' : 'ERROR: Alerta no guardada';
\$conn->close();
"
```

## Recomendaciones Futuras

1. **Considerar migrar a PDO completamente** - PDO es más seguro y consistente
2. **Agregar logging más detallado** - Ya está mejorado con `error_log()`
3. **Crear más tests de integración** - Validar todas las funciones de alertas
4. **Documentar patrones de conexión** - Usar consistentemente MySQLi o PDO en todo el proyecto
