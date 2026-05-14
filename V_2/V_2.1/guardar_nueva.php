<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = getGraphToken();

    // Desglosamos el técnico para tener el email limpio para alarmas
    $partes = explode('|', $_POST['tecnico_data']);
    $nombre = $partes[0];
    $email = $partes[1];

    $fields = [
        'Title' => $_POST['titulo'],
        'Prioridad' => $_POST['prioridad'],
        'Impacto' => $_POST['impacto'],
        'Periodicidad' => $_POST['periodicidad'],
        'Documentacion' => $_POST['documentacion'] ?? '',
        'Tecnico' => $nombre, // Para la visualización humana en la lista
        'Realiza' => $email   // El email oficial para tus alarmas automatizadas
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

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code == 201) {
        header("Location: index.php?success=1");
    } else {
        echo "<div style='background:#fee; padding:30px; font-family:sans-serif;'>";
        echo "<h2 style='color:red;'>Error $code</h2>";
        echo "<p>No se pudo guardar la tarea. Revisa que los nombres de las columnas en SharePoint coincidan.</p>";
        echo "<pre>" . htmlspecialchars($res) . "</pre></div>";
    }
}