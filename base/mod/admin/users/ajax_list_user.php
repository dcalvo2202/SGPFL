<?php

include("../../login/check.php");
include("../../../inc/db/db.php");

/* Tabla de la base de datos */
$sTable = "vis_user"; //Escriba el nombre de la tabla a consultar

/* Columnas de la base de datos */
$aColumns = array('id', 'nombre', 'roll'); //Columnas de la base de datos

/* Index de la tabla */
$sIndexColumn = "id"; //Normalmente el nombre de la columna ID de la tabla

/**
 * Arreglo de acciones para cada fila a mostrar en la tabla 
 * 
 * Aquí colocamos la instruccion AJAX para cada accion
 * 
 * 
 */
$aActions = array(
    "sView" => 'view_user',
    "sEdit" => 'edit_user',
    "sPrin" => '',
    "sDele" => 'delete_user',
    "sAdd" => '');


/*
 * Paging
 */
$sLimit = "";
if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
    $sLimit = "LIMIT " . intval($_GET['iDisplayStart']) . ", " .
            intval($_GET['iDisplayLength']);
}

/*
 * Ordering
 */
$sOrder = "";
if (isset($_GET['iSortCol_0'])) {
    $sOrder = "ORDER BY  ";
    for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
        if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
            $sOrder .= "`" . $aColumns[intval($_GET['iSortCol_' . $i])] . "` " .
                    ($_GET['sSortDir_' . $i] === 'asc' ? 'asc' : 'desc') . ", ";
        }
    }

    $sOrder = substr_replace($sOrder, "", -2);
    if ($sOrder == "ORDER BY") {
        $sOrder = "";
    }
}

/*
 * Filtering
 * NOTE this does not match the built-in DataTables filtering which does it
 * word by word on any field. It's possible to do here, but concerned about efficiency
 * on very large tables, and MySQL's regex functionality is very limited
 */
$sWhere = "";
if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
    $sWhere = "WHERE (";
    for ($i = 0; $i < count($aColumns); $i++) {
        $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . mysqli_real_escape_string($id_con, $_GET['sSearch']) . "%' OR ";
    }
    $sWhere = substr_replace($sWhere, "", -3);
    $sWhere .= ')';
}

/* Individual column filtering */
for ($i = 0; $i < count($aColumns); $i++) {
    if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '') {
        if ($sWhere == "") {
            $sWhere = "WHERE ";
        } else {
            $sWhere .= " AND ";
        }
        $sWhere .= "`" . $aColumns[$i] . "` LIKE '%" . mysqli_real_escape_string($id_con, $_GET['sSearch_' . $i]) . "%' ";
    }
}

/*
 * SQL queries
 * Get data to display
 */

$sQuery = "SELECT SQL_CALC_FOUND_ROWS `" . str_replace(" , ", " ", implode("`, `", $aColumns)) . "` FROM   $sTable $sWhere $sOrder $sLimit ;";
//echo $sQuery;
$sql = "SELECT COUNT(`" . $sIndexColumn . "`) FROM   $sTable $sWhere;";
$rResultFilterTotal = seleccion($sql);
$iFilteredTotal = $rResultFilterTotal[0][0];


$sql = "SELECT COUNT(`" . $sIndexColumn . "`) FROM   $sTable ;";
$rResultTotal = seleccion($sql);
$iTotal = $rResultTotal[0][0];

$output = array(
    "sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array()
);
$output['aaData'] = seleccion_tabla($sQuery, $sIndexColumn, $aColumns, $aActions);

echo json_encode($output);
mysqli_close($id_con);
?>