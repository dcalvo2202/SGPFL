# Guía: Eliminación de Registros de Auditoría (sis_log) por Administrador

## Restricciones de Seguridad

La tabla `sis_log` es **inalterable** por defecto:
- ❌ No se permite `UPDATE` (trigger bloquea).
- ❌ No se permite `DELETE` directo (trigger bloquea).
- ✅ Solo **administradores (rol = 1)** pueden eliminar registros mediante el procedimiento `delete_sis_log_by_admin`.
- ✅ **Purga automática** diaria elimina registros con >5 años (sin requerer permisos admin).

---

## Procedimiento: `delete_sis_log_by_admin`

### Descripción
Permite a un usuario administrador eliminar registros de auditoría anteriores a una fecha específica.

### Signatura
```sql
CALL delete_sis_log_by_admin(
    p_admin_user_id varchar(50),    -- ID del administrador
    p_date_before datetime,          -- Eliminar registros previos a esta fecha
    @deleted_rows int OUTPUT,        -- Número de filas eliminadas
    @res tinyint OUTPUT              -- Código de resultado
);
```

### Códigos de Resultado (`@res`)
- `0` - Éxito
- `1` - Error genérico
- `2` - El usuario NO es administrador
- `3` - Error en transacción

---

## Ejemplos de Uso

### Ejemplo 1: Admin elimina registros previos a una fecha
```sql
-- Admin con ID '205610158' elimina logs anteriores al 1 de enero de 2021
CALL delete_sis_log_by_admin(
    '205610158',
    DATE('2021-01-01'),
    @deleted_rows,
    @res
);

SELECT @deleted_rows AS registros_eliminados, @res AS codigo_resultado;
```

### Ejemplo 2: Admin elimina registros previos a hace 3 años
```sql
-- Eliminar registros anteriores a 3 años atrás
CALL delete_sis_log_by_admin(
    '205610158',
    DATE_SUB(NOW(), INTERVAL 3 YEAR),
    @deleted_rows,
    @res
);

SELECT @deleted_rows AS registros_eliminados, @res AS codigo_resultado;
```

### Ejemplo 3: Verificar que el usuario es admin antes de llamar
```sql
-- Validación previa
SELECT id, nombre, id_roll
FROM sis_user
WHERE id = '205610158';  -- Debe mostrar id_roll = 1
```

---

## Limitaciones

- **LIMIT 50000**: Máximo 50,000 registros por ejecución para evitar bloqueos.
- **Validación de Admin**: Solo `id_roll = 1` puede usar este procedimiento.
- **Auditoria**: El procedimiento ejecuta la eliminación, pero esta acción NO se registra en `sis_log` (ya que ocurre dentro del deleteo).

---

## Mecanismo Interno

1. El procedimiento valida que `p_admin_user_id` tenga `id_roll = 1`.
2. Si es admin, establece `@sis_log_allow_purge_admin = 1`.
3. El trigger `trg_sis_log_no_delete` permite el `DELETE` (porque detecta esta variable).
4. Ejecuta el borrado dentro de una transacción.
5. Resetea `@sis_log_allow_purge_admin = 0` después de completar.

---

## Habilitación del Scheduler (si no está activo)

Para que la **purga automática diaria** funcione, verifica que el scheduler esté habilitado:

```sql
-- Verificar estado
SELECT @@global.event_scheduler;
-- Resultado: 1 = ON, 0 = OFF

-- Si está OFF, habilitarlo
SET GLOBAL event_scheduler = ON;
```

---

## Consultas de Monitoreo

### Ver logs recientes
```sql
SELECT id_bi, id_user, date_bi, action_type, action_result, ip_address
FROM sis_log
ORDER BY date_bi DESC
LIMIT 10;
```

### Ver estadísticas de logs
```sql
SELECT 
    COUNT(*) AS total_logs,
    MIN(date_bi) AS log_mas_antiguo,
    MAX(date_bi) AS log_mas_reciente,
    DATEDIFF(NOW(), MIN(date_bi)) AS dias_antiguedad_maxima
FROM sis_log;
```

### Ver eventos programados
```sql
SELECT 
    EVENT_SCHEMA,
    EVENT_NAME,
    STATUS,
    EXECUTE_AT,
    LAST_EXECUTED
FROM INFORMATION_SCHEMA.EVENTS
WHERE EVENT_NAME = 'evt_purge_old_sis_log';
```

---

## Seguridad

- ✅ Solo administrators (rol=1) pueden ejecutar `delete_sis_log_by_admin`.
- ✅ Logs de auditoría no se pueden modificar ni borrar manualmente (protegidos por triggers).
- ✅ La purga automática (>5 años) ocurre sin intervención humana.
- ✅ Cada operación se ejecuta en transacción para garantizar integridad.

