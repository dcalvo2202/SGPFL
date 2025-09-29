<?php
/**
 * Protección contra inclusión múltiple
 */
if (!defined('DB_FUNCTIONS_LOADED')) {
    define('DB_FUNCTIONS_LOADED', true);

require 'bdcommon.inc';
$id_con = mysqli_connect($db_host, $usuario, $clave, $db);
mysqli_set_charset($id_con, "utf8");

/** Ejecuta SELECT y retorna un arrreglo con los resultados
 */
function seleccion($sql) {
    require 'bdcommon.inc';
    $a = array();
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    $resultado = mysqli_query($id_con, $sql);
    while ($row = mysqli_fetch_array($resultado)) { 
        $a[] = $row; 
    }
    mysqli_close($id_con); 
    return $a; 
}

/** Retorna en un arreglo de un solo row con la respuesta de mysql de una 
 */
function transaccion($sql) {
    require 'bdcommon.inc';
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    $resultado = mysqli_query($id_con, $sql);
    $a = mysqli_fetch_array($resultado);
    mysqli_close($id_con); 
    return $a;
}

/**
 * Función mejorada de selección con parámetros seguros
 * @param string $sql sentencia SQL con placeholders ?
 * @param array $params parámetros para los placeholders
 * @return array resultado de la consulta
 */
function seleccion_segura($sql, $params = []) {
    require 'bdcommon.inc';
    $a = array();
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    
    if (empty($params)) {
        $resultado = mysqli_query($id_con, $sql);
        if ($resultado) {
            while ($row = mysqli_fetch_array($resultado)) { 
                $a[] = $row; 
            }
        }
    } else {
        $stmt = mysqli_prepare($id_con, $sql);
        if ($stmt) {
            // Determinar tipos de parámetros
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            
            if ($resultado) {
                while ($row = mysqli_fetch_array($resultado)) { 
                    $a[] = $row; 
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($id_con); 
    return $a; 
}

/**
 * Función para ejecutar una sola query con parámetros seguros
 * @param string $sql sentencia SQL con placeholders ?
 * @param array $params parámetros para los placeholders
 * @return array resultado con éxito y datos adicionales
 */
function ejecutar_query($sql, $params = []) {
    require 'bdcommon.inc';
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    
    try {
        if (empty($params)) {
            $resultado = mysqli_query($id_con, $sql);
            if (!$resultado) {
                throw new Exception(mysqli_error($id_con));
            }
        } else {
            $stmt = mysqli_prepare($id_con, $sql);
            if (!$stmt) {
                throw new Exception(mysqli_error($id_con));
            }
            
            // Determinar tipos de parámetros
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_stmt_error($stmt));
            }
            
            mysqli_stmt_close($stmt);
        }
        
        $insert_id = mysqli_insert_id($id_con);
        $affected_rows = mysqli_affected_rows($id_con);
        
        mysqli_close($id_con);
        
        return [
            'success' => true, 
            'insert_id' => $insert_id,
            'affected_rows' => $affected_rows
        ];
        
    } catch (Exception $e) {
        mysqli_close($id_con);
        return [
            'success' => false, 
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Función para transacciones múltiples con prepared statements
 * @param array $queries array de queries con sus parámetros
 * @return array resultado de la operación
 */
function transaccion_multiple($queries) {
    require 'bdcommon.inc';
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    mysqli_autocommit($id_con, false);
    
    $results = [];
    $project_id = null;
    
    try {
        foreach ($queries as $index => $query_data) {
            $sql = $query_data['sql'];
            $params = $query_data['params'] ?? [];
            
            // Reemplazar placeholder del project_id si existe
            if ($project_id && isset($params)) {
                foreach ($params as $key => $param) {
                    if ($param === '{{PROJECT_ID}}') {
                        $params[$key] = $project_id;
                    }
                }
            }
            
            if (empty($params)) {
                $resultado = mysqli_query($id_con, $sql);
                if (!$resultado) {
                    throw new Exception("Error en query $index: " . mysqli_error($id_con));
                }
                $insert_id = mysqli_insert_id($id_con);
            } else {
                $stmt = mysqli_prepare($id_con, $sql);
                if (!$stmt) {
                    throw new Exception("Error preparando query $index: " . mysqli_error($id_con));
                }
                
                // Determinar tipos de parámetros
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                }
                
                mysqli_stmt_bind_param($stmt, $types, ...$params);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error ejecutando query $index: " . mysqli_stmt_error($stmt));
                }
                
                $insert_id = mysqli_insert_id($id_con);
                mysqli_stmt_close($stmt);
            }
            
            // Guardar el project_id del primer INSERT
            if ($index === 0 && $insert_id > 0) {
                $project_id = $insert_id;
            }
            
            $results[] = [
                'success' => true, 
                'insert_id' => $insert_id,
                'affected_rows' => mysqli_affected_rows($id_con)
            ];
        }
        
        mysqli_commit($id_con);
        mysqli_close($id_con);
        
        return [
            'success' => true, 
            'results' => $results,
            'project_id' => $project_id
        ];
        
    } catch (Exception $e) {
        mysqli_rollback($id_con);
        mysqli_close($id_con);
        return [
            'success' => false, 
            'error' => $e->getMessage()
        ];
    }
}

} // Fin de la protección contra inclusión múltiple
?>