<?php

?>

<h1>Información del usuario</h1>

<div class="row">
    <div class="col-md-2">
      <label  for="">Nombre:</label>
    </div>
    <div class="col-md-4">
        <?php echo q("SELECT usu_nombres || ' ' || usu_apellidos AS nombre FROM esamyn.esa_usuario WHERE usu_cedula='".$_SESSION['cedula']."'")[0]['nombre']; ?>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
      <label  for="">N&uacute;mero de C&eacute;dula:</label>
    </div>
    <div class="col-md-4">
        <?php echo $_SESSION['cedula']; ?>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
      <label  for="oldpassword">Rol:</label>
    </div>
    <div class="col-md-4">
        <?php echo q("SELECT rol_nombre FROM esamyn.esa_rol WHERE rol_id=" .$_SESSION['rol'])[0]['rol_nombre']; ?>
    </div>
</div>

