<?php


//header('Content-Type: application/json');


$ess_id = $_SESSION['ess_id'];

$result = q("SELECT * FROM esamyn.esa_rol");
$roles = array();
foreach($result as $r){
    $roles[$r['rol_id']] = $r['rol_nombre']; 
}

if (isset($_POST['dataset_json']) && !empty($_POST['dataset_json'])) {
    $dataset_json = $_POST['dataset_json'];
} else {
    $dataset_json = file_get_contents("php://input");
}

if (!empty($dataset_json)) {

    $dataset = json_decode($dataset_json);
    if (isset($dataset->cedula) && !empty($dataset->cedula)) {
        $id = ( (isset($dataset->id) && !empty($dataset->id)) ? $dataset->id : null);
        $cedula = $dataset->cedula;
        $username = $cedula;
        $max_usuarios = q("SELECT ess_max_usuarios FROM esamyn.esa_establecimiento_salud WHERE ess_id=$ess_id")[0]['ess_max_usuarios'];
        $count_usuarios = q("SELECT COUNT(*) FROM esamyn.esa_permiso_ingreso WHERE pei_establecimiento_salud=$ess_id")[0]['count'];

        if (isset($dataset->reiniciar) && !empty($dataset->reiniciar)) {
            //resetea clave

            $password = md5($cedula);
            $result = q("UPDATE esamyn.esa_usuario SET usu_password='$password' WHERE usu_id=$id RETURNING *");
        } else if (isset($dataset->borrar) && !empty($dataset->borrar)) {
            if ($_SESSION['cedula'] != $cedula) {
                $result = q("UPDATE esamyn.esa_usuario SET usu_borrado=now() WHERE usu_id=$id RETURNING *");
                q("DELETE FROM esamyn.esa_permiso_ingreso WHERE pei_usuario=$id");
            } else {
                $result = array(array('ERROR'=>"No se puede borrar el mismo usuario con el que se encuentra abierta la sesion"));
            }
        } else if (isset($dataset->recuperar) && !empty($dataset->recuperar)) {
            $sql= ("SELECT COUNT(*) FROM esamyn.esa_usuario WHERE usu_borrado IS NULL AND usu_cedula='$cedula'");
            $result = q($sql);
            $count_usuarios_cedula = $result[0]['count']; 

            if ($count_usuarios_cedula == 0) {
                $result = q("UPDATE esamyn.esa_usuario SET usu_borrado=null WHERE usu_id=$id RETURNING *");
                if ($count_usuarios < $max_usuarios) {
                    $usu_id = $id;
                    q("INSERT INTO esamyn.esa_permiso_ingreso(pei_usuario, pei_establecimiento_salud) VALUES ($usu_id, $ess_id)");
                }
            } else {
                $result = array(array('ERROR'=>"No se puede recuperar, ya existe usuario con cedula $cedula"));
            }
        } else {
            //guarda datos de usuario

            $sql= ("SELECT COUNT(*) FROM esamyn.esa_usuario WHERE usu_borrado IS NULL AND usu_cedula='$cedula'");
            //echo "[$sql]";
            $result = q($sql);
            $count_usuarios_cedula = $result[0]['count']; 
            //echo "[count_usuarios_cedula: $count_usuarios_cedula]";

            if ($count_usuarios_cedula == 0) {
                if ($count_usuarios < $max_usuarios || $_SESSION['rol'] == 1) {
                    //crea usuario
                    $campos = 'rol,nombres,apellidos,cedula,telefono,correo_electronico';
                    $campos_array = explode(',', $campos);
                    $sql_insert_campos = '';
                    $sql_insert_valores = '';
                    $glue = '';
                    foreach ($campos_array as $campo){

                        if (isset($dataset->$campo) && !empty($dataset->$campo)) {
                            $_ = '';

                            switch ($campo){
                            case 'rol':
                                break;
                            case 'nombres':
                                $_ = "'";
                                break;
                            case 'apellidos':
                                $_ = "'";
                                break;
                            case 'username':
                                $_ = "'";
                                break;
                            case 'password':
                                $_ = "'";
                                break;
                            case 'cedula':
                                $_ = "'";
                                break;
                            case 'telefono':
                                $_ = "'";
                                break;
                            case 'correo_electronico':
                                $_ = "'";
                                break;
                            }

                            $sql_insert_campos .= $glue . 'usu_' . $campo;
                            $sql_insert_valores .= $glue . $_ . $dataset->$campo . $_;
                            $glue = ',';
                        }

                    }
                    $sql_insert_campos .= ',usu_username,usu_password';
                    $username = $dataset->cedula;
                    $password = md5($dataset->cedula);
                    $sql_insert_valores .= ",'$username','$password'";
                    $result = q("INSERT INTO esamyn.esa_usuario($sql_insert_campos) VALUES($sql_insert_valores) RETURNING *");
                    if ($result) {
                        $usu_id = $result[0]['usu_id'];
                        q("INSERT INTO esamyn.esa_permiso_ingreso(pei_usuario, pei_establecimiento_salud) VALUES ($usu_id, $ess_id)");
                    }
                } else {
                    $result = array(array('ERROR' => 'No se puede crear el usuario, ya que el Establecimiento de Salud ha alcanzado el numero maximo de usuarios ('.$max_usuarios.')'));
                }
            } else if (!empty($id) && $count_usuarios_cedula == 1) {
                //actualiza usuario
                $campos = 'rol,nombres,apellidos,telefono,correo_electronico';
                $campos_array = explode(',', $campos);
                $sql_update = '';
                $glue = '';
                foreach ($campos_array as $campo){

                    if (isset($dataset->$campo) && !empty($dataset->$campo)) {
                        $_ = '';

                        switch ($campo){
                        case 'rol':
                            break;
                        case 'nombres':
                            $_ = "'";
                            break;
                        case 'apellidos':
                            $_ = "'";
                            break;
                        case 'telefono':
                            $_ = "'";
                            break;
                        case 'correo_electronico':
                            $_ = "'";
                            break;
                        }

                        $sql_update .= "$glue usu_$campo = ". $_ . $dataset->$campo . $_;
                        $glue = ',';
                    }

                }
                $sql = ("UPDATE esamyn.esa_usuario SET $sql_update WHERE usu_id=$id RETURNING *");
                $result = q($sql);
                if ($result) {
                    $usu_id = $id;
                    $count_pei = q("SELECT COUNT(*) FROM esamyn.esa_permiso_ingreso WHERE pei_usuario=$usu_id")[0]['count'];
                    if ($count_pei == 0) {
                        //no tiene permisos, se le asigna por defecto permisos al ES actual
                        if ($count_usuarios < $max_usuarios) {
                            q("INSERT INTO esamyn.esa_permiso_ingreso(pei_usuario, pei_establecimiento_salud) VALUES ($usu_id, $ess_id)");
                        }
                    }
                }
            } else {
                //borra usuarios con cedula repetida
                $result = array(array('ERROR' => "Ya existe un usuario con cedula $cedula"));
            }
        }
    } else {
        $result = array(array('ERROR' => 'No se ha enviado la cedula', 'dataset' => $dataset));
    }
} else {
    $result = array(array('ERROR' => 'No se han enviado datos'));
}
$respuesta = array();
foreach($result[0] as $k => $v) {
    $respuesta[str_replace('usu_', '', $k)] = $v;
}
if (isset($respuesta['rol'])) {
    $respuesta['rol'] = $roles[$respuesta['rol']];
}
echo json_encode(array($respuesta));

