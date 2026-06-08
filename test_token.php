<?php
include('config.php');
$token = getGraphToken();
if ($token) {
    echo " ✅ ¡Éxito! El token empieza por: " . substr($token, 0, 15) . "...";
} else {
    echo " ❌ Error: No se pudo obtener el token. Revisa el Client Secret y los permisos en Azure.";
}
?>
