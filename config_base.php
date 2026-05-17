<?php
// Credenciales de la Aplicación Azure (App-AWS-720Tec)
define('TENANT_ID', 'xxxx-xxxx-xxxx-xxxx-xxxx');
define('CLIENT_ID', 'xxxx-xxxx-xxxx-xxxx-xxxx');
define('CLIENT_SECRET', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');

// ID del Sitio Proyecto ASIR (Confirmado por Graph Explorer)
define('SITE_ID', '720teces.sharepoint.com,xxxx-xxxx-xxxx-xxxx-xxxx,yyyy-yyyy-yyyy-yyyy-yyyy');

// ID de tu Lista Nueva
define('LIST_ID', 'xxxx-xxxx-xxxx-xxxx-xxxx');

/**
 * Función para obtener el Token de acceso automáticamente
 */
function getGraphToken()
{
    $url = "https://login.microsoftonline.com/" . TENANT_ID . "/oauth2/v2.0/token";
    $data = [
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials',
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res['access_token'] ?? null;
}