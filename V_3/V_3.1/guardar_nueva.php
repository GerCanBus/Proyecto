<?php
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = getGraphToken();
    if (!$token) {
        header("Location: index.php?status=error&msg=AuthFail");
        exit();
    }

    // Separar nombre y email del técnico
    $datos_tecnico = explode('|', $_POST['tecnico_data']);
    $creacion = $_POST['fecha_creacion'];

    $fields = [
        'Title' => $_POST['titulo'],
        'Prioridad' => $_POST['prioridad'],
        'Impacto' => $_POST['impacto'],
        'Periodicidad' => $_POST['periodicidad'],
        'Documentacion' => $_POST['documentacion'] ?? '',
        'Observaciones' => $_POST['observaciones'] ?? '',
        'Tecnico' => $datos_tecnico[0],
        'Realiza' => $datos_tecnico[1],
        'FechaCreacion' => $creacion
        // SE ELIMINA FechaVencimiento POR SER COLUMNA CALCULADA (Error 403)
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
        header("Location: index.php?status=success");
    } else {
        header("Location: index.php?status=error&msg=" . $code);
    }
    exit();
}