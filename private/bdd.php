<?php

function q($sql, $callback = false) {
    global $conn;

    l('SQL: ' . $sql);

    $data = null;
    $result = pg_query($conn, $sql);
    if ($result) {
        if ($callback) {
            while($row = pg_fetch_array($result)){
                $callback($row);
            }
        } else {
            $data = pg_fetch_all($result);
            //var_dump($data);
            //$data = count($data) === 1 ? (count($data[0]) === 1 ? $data[0][0] : $data[0]) : $data;
        }
    }
    return $data;
}

function l($texto){
    global $conn;
    $log = pg_escape_literal($texto);
    $usuario = ((isset($_SESSION['usu_id']) && !empty($_SESSION['usu_id'])) ? $_SESSION['usu_id'] : 'null');
    pg_send_query($conn, "INSERT INTO esamyn.esa_log(log_texto, log_creado_por) VALUES ($log, $usuario)");
}
