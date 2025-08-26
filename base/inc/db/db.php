<?php
/**
 * Identifica el charset de la conexión para los filtros de busqueda
 */
require 'bdcommon.inc';
$id_con = mysqli_connect($db_host, $usuario, $clave, $db);
mysqli_set_charset($id_con, "utf8");

/** Ejecuta SELECT y retorna un arrreglo con los resultados
 * @param string $sql sentencia SQL
 * @return arreglo $a 
 */
function seleccion($sql) {
    require 'bdcommon.inc';
    $a = array();
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    $resultado = mysqli_query($id_con, $sql);
    $x = 0;
    while ($row = mysqli_fetch_array($resultado)) { 
        $a[$x] = $row; 
        $x++;
    }
    mysqli_close($id_con); 
    return $a; 
}
/** Retorna en un arreglo de un solo row con la respuesta de mysql de una 
 * transacion es decir 0 si todo se ejecuto bien, otro valor dependiendo del
 * error.
 * 
 * @param string $sql Sentencia SQL
 * @return arreglo $resultado un arreglo de posicion unica con el resultado de mysql
 */
function transaccion($sql) {
    require 'bdcommon.inc';
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    $resultado[0] = mysqli_query($id_con,$sql);
    mysqli_close($id_con);
    return $resultado;
}
/** Funcion que ejecuta transacciones tipo CALL que tienen verificacion con 
 * varaibles de tipo output en procedimientos almacenados.
 * 
 * @param string $sql_a Llamada al procedimeinto almacenado
 * @param string $sql_b Verificacion de la variable @respuesta
 * @return array $a resultado de la consulta $sql_b,
 */
function transaccion_verificada($sql_a,$sql_b){
    require 'bdcommon.inc';
    $a = array();
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    $resultado_a[0] = mysqli_query($id_con,$sql_a);
    $resultado_b = mysqli_query($id_con, $sql_b);
    $x = 0;
    while ($row = mysqli_fetch_array($resultado_b)) { 
        $a[$x] = $row; 
        $x++;
    }
    mysqli_close($id_con); 
    return $a; 
}
/**
 * Esta función esta diseñada para generar el contedido de una tabal en formato
 * JSON para que sea procesado por la libreria DataTables
 * Devuelve cara row con la información solicitada mas los botones para
 * las acciones como VER, EDITAR, ELIMINAR ...
 * 
 * En casos especifico se puede generar una funcion especial utilizando esta como 
 * punto de partida, ver ejemplos en sistema de Acuerdos y de Inventarios en bodega
 * 
 * @param string $sQuery sentensia SQL generada por el controlador de datos de la tabla
 * @param string $sIndexColumn nombre de la llave primaria o llave a usar
 * @param array $aColumns columnas a consultar en la base de datos, tiene que incluir la index tambien
 * @param array $aActions arreglo con el nombre de las funciones ajax a ejecutar con el id
 * @return string JSON Retorna los datos consultados
 */
function seleccion_tabla($sQuery, $sIndexColumn, $aColumns, $aActions) {
    require 'bdcommon.inc';
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    $rResult = mysqli_query($id_con, $sQuery);
    $output = array();
    while ($aRow = mysqli_fetch_array($rResult)) {
        $row = array();
        for ($i = 0; $i < count($aColumns); $i++) {
            if ($aColumns[$i] != ' ') {
                /* Informacion general */
                $row[] = $aRow[$aColumns[$i]];
            }
        }
        if ($aActions["sView"] != '') {
            $row[] = "<a class='puntero' title='Ver' onClick = 'javascript:" . $aActions["sView"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-eye text-primary' title='Ver'></i></div></a>";
        }
        if ($aActions["sEdit"] != '') {
            $row[] = "<a class='puntero' title='Editar' onClick = 'javascript:" . $aActions["sEdit"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-pencil text-success' title='Editar'></i></div></a>";
        }
        if ($aActions["sPrin"] != '') {
            $row[] = "<a class='puntero' title='Imprimir' onClick = 'javascript:" . $aActions["sPrin"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-print text-primary' title='Imprimir'></i></div></a>";
        }
        if ($aActions["sDele"] != '') {
            $row[] = "<a class='puntero' title='Eliminar' onClick = 'javascript:" . $aActions["sDele"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-close text-danger' title='Eliminar'></i></div></a>";
        }
        if ($aActions["sAdd"] != '') {
            $row[] = "<a class='puntero' title='A&ntilde;adir' onClick = 'javascript:" . $aActions["sAdd"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-pluss text-success' title='A&ntilde;adir'></i></div></a>";
        }
        $output[] = $row;
    }
    return $output;
}
/**
 * Esta función esta diseñada para generar el contedido de una tabal en formato
 * JSON para que sea procesado por la libreria DataTables
 * Devuelve cara row con la información solicitada mas los botones para
 * las acciones como VER, EDITAR, ELIMINAR ...
 * 
 * Basado en su antesesor, esta versión modifica el metodo add para añadir más parametros
 * 
 * @param string $sQuery sentensia SQL generada por el controlador de datos de la tabla
 * @param string $sIndexColumn nombre de la llave primaria o llave a usar
 * @param array $aColumns columnas a consultar en la base de datos, tiene que incluir la index tambien
 * @param array $aActions arreglo con el nombre de las funciones ajax a ejecutar con el id
 * @return string JSON Retorna los datos consultados
 */
function seleccion_tabla_t($sQuery, $sIndexColumn, $aColumns, $aActions) {
    require 'bdcommon.inc';
    $id_con = mysqli_connect($db_host, $usuario, $clave, $db);
    mysqli_set_charset($id_con, "utf8");
    $rResult = mysqli_query($id_con, $sQuery);
    $output = array();
    while ($aRow = mysqli_fetch_array($rResult)) {
        $row = array();
        for ($i = 0; $i < count($aColumns); $i++) {
            if ($aColumns[$i] != ' ') {
                /* Informacion general */
                $row[] = $aRow[$aColumns[$i]];
            }
        }
        if ($aActions["sView"] != '') {
            $row[] = "<a class='puntero' title='Ver' onClick = 'javascript:" . $aActions["sView"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-eye text-primary' title='Ver'></i></div></a>";
        }
        if ($aActions["sEdit"] != '') {
            $row[] = "<a class='puntero' title='Editar' onClick = 'javascript:" . $aActions["sEdit"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-pencil text-success' title='Editar'></i></div></a>";
        }
        if ($aActions["sPrin"] != '') {
            $row[] = "<a class='puntero' title='Imprimir' onClick = 'javascript:" . $aActions["sPrin"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-print text-primary' title='Imprimir'></i></div></a>";
        }
        if ($aActions["sDele"] != '') {
            $row[] = "<a class='puntero' title='Eliminar' onClick = 'javascript:" . $aActions["sDele"] . '("' . $aRow[$sIndexColumn] . '"' . ");'><div class='text-center'><i class='fa fa-close text-danger' title='Eliminar'></i></div></a>";
        }
        if ($aActions["sAdd"] != '') {
            $row[] = "<a class='puntero' title='A&ntilde;adir' onClick = 'javascript:" . $aActions["sAdd"] . '("' . $aRow['id_activo'] . '","'. $aRow['no_activo'] . '","'. $aRow['no_serie'] . '","'. $aRow['descripcion'] .'"'. ");'><div class='text-center'><i class='fa fa-pluss text-success' title='A&ntilde;adir'></i></div></a>";
        }
        $output[] = $row;
    }
    return $output;
}

?>