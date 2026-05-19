<?php
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = getGraphToken();
    if (!$token) {
        header("Location: index.php?status=error&msg=AuthFail");
        exit();
    }

    // Separar nombre y email del técnico seleccionado
    $datos_tecnico = explode('|', $_POST['tecnico_data']);

    // Recoger fechas del formulario
    $creacion = $_POST['fecha_creacion'];
    $vencimiento = $_POST['fecha_vencimiento'];

    $fields = [
        'Title' => $_POST['titulo'],
        'Prioridad' => $_POST['prioridad'],
        'Impacto' => $_POST['impacto'],
        'Periodicidad' => $_POST['periodicidad'],
        'Completada' => $_POST['completada'], // SE AÑADE EL RECOJO DINÁMICO DE ESTA COLUMNA CON RESPECTO A SHAREPOINT
        'Documentacion' => $_POST['documentacion'] ?? '',
        'Observaciones' => $_POST['observaciones'] ?? '',
        'Tecnico' => $datos_tecnico[0],
        'Realiza' => $datos_tecnico[1],
        'FechaCreacion' => $creacion,
        'FechaVencimiento' => $vencimiento
    ];

    $url = "https://graph.microsoft.com/v1.0/sites/" . SITE_ID . "/lists/" . LIST_ID . "/items";
    $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json; charset=utf-8'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 201 || $http_code == 200) {
        header("Location: index.php?status=success");
    } else {
        $err_data = json_decode($response, true);
        $msg = $err_data['error']['message'] ?? 'GraphError';
        header("Location: index.php?status=error&msg=" . urlencode($msg));
    }
    exit();
}