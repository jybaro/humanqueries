<?php

//header('Content-Type: application/json');

$ess_id = $_SESSION['ess_id'];

/*
$result = q("SELECT * FROM esamyn.esa_rol");
$roles = array();
foreach($result as $r){
    $roles[$r['rol_id']] = $r['rol_nombre']; 
}
 */
if (isset($_POST['dataset_json']) && !empty($_POST['dataset_json'])) {
    $dataset_json = $_POST['dataset_json'];
} else {
    $dataset_json = file_get_contents("php://input");
}

//$rol = $_SESSION['rol'];
$result = array();

if (!empty($dataset_json)) {

    $dataset = json_decode($dataset_json);
    if (isset($dataset->establecimiento_salud) && !empty($dataset->establecimiento_salud && isset($dataset->usuario) && !empty($dataset->usuario))) {
        $establecimiento_salud = $dataset->establecimiento_salud;
        $usuario = $dataset->usuario;
        $result_rol = q("SELECT usu_rol FROM esamyn.esa_usuario WHERE usu_id=$usuario");


        if ($result_rol) {
            $rol = $result_rol[0]['usu_rol'];

            if (isset($dataset->borrar) && !empty($dataset->borrar)) {
                //borrar
                $count_pei_total = q("SELECT COUNT(*) FROM esamyn.esa_permiso_ingreso WHERE pei_usuario=$usuario")[0]['count'];
                if ($count_pei_total > 1 || $rol == 1) {
                    $result = q("DELETE FROM esamyn.esa_permiso_ingreso WHERE pei_usuario=$usuario AND pei_establecimiento_salud=$establecimiento_salud RETURNING *");
                } else {
                    $result = array(array('ERROR' => 'No se puede eliminar, ya que el usuario no tiene permisos de ingreso en otro Establecimiento de Salud. Asigne permisos en otro lado antes de eliminar este permiso, o borre al usuario.'));
                }
            } else {
                //guardar
                $result = q("SELECT COUNT(*) FROM esamyn.esa_permiso_ingreso WHERE pei_usuario=$usuario AND pei_establecimiento_salud=$establecimiento_salud");
                $count = $result[0]['count'];
                if ($count == 0) {
                    $max_usuarios = q("SELECT ess_max_usuarios FROM esamyn.esa_establecimiento_salud WHERE ess_id=$establecimiento_salud")[0]['ess_max_usuarios'];
                    $count_usuarios = q("SELECT COUNT(*) FROM esamyn.esa_permiso_ingreso WHERE pei_establecimiento_salud=$establecimiento_salud")[0]['count'];
                    if ($count_usuarios < $max_usuarios) {
                        $result = q("INSERT INTO esamyn.esa_permiso_ingreso (pei_usuario, pei_establecimiento_salud) VALUES ($usuario, $establecimiento_salud) RETURNING *");
                    } else {
                        $result = array(array('ERROR' => 'No se puede asignar el permiso, ya que el Establecimiento de Salud ha alcanzado el numero maximo de usuarios ('.$max_usuarios.')'));
                    }
                } else {
                    $result = array(array('ERROR' => 'El usuario ya tiene permisos de ingreso al Establecimiento de Salud'));
                }
            }
        } else {
            $result = array(array('ERROR' => 'El usuario no esta registrado en el sistema'));
        }
    }
} else {
    $result = array(array('ERROR' => 'No se han enviado datos'));
}
$respuesta = array();
if ($result) {
    foreach($result[0] as $k => $v) {
        $respuesta[str_replace('pei_', '', $k)] = $v;
    }
}
echo json_encode(array($respuesta));


