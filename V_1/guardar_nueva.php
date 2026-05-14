<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = getGraphToken();
    if (!$token)
        die("Error de autenticación.");

    // Separar Nombre y Email oficial
    $datos = explode('|', $_POST['tecnico_data']);
    $nombre = $datos[0];
    $email = $datos[1];

    $fields = [
        'Title' => $_POST['titulo'],
        'Prioridad' => $_POST['prioridad'],
        'Periodicidad' => $_POST['periodicidad'],
        'Tecnico' => $nombre, // Columna de texto para visualización
        'Realiza' => $email   // Columna de texto para AUTOMATIZACIONES (Email real)
    ];

    $url = "https://graph.microsoft.com/v1.0/sites/" . SITE_ID . "/lists/" . LIST_ID . "/items";
    $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json; charset=utf-8'
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code == 201) {
        header("Location: index.php?success=1");
    } else {
        echo "<h2>Error $code</h2><pre>$res</pre>";
    }
}