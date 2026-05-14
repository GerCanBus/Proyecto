<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = getGraphToken();
    if (!$token)
        die("Error crítico: No se pudo obtener el token de Azure.");

    // Separamos el nombre para mostrar y el email para automatizaciones
    $partes = explode('|', $_POST['tecnico_data']);
    $nombre_tecnico = $partes[0];
    $email_tecnico = $partes[1];

    $fields = [
        'Title' => $_POST['titulo'],
        'Prioridad' => $_POST['prioridad'],
        'Impacto' => $_POST['impacto'],
        'Periodicidad' => $_POST['periodicidad'],
        'Documentacion' => $_POST['documentacion'] ?? '',
        'Tecnico' => $nombre_tecnico, // Nombre legible
        'Realiza' => $email_tecnico   // Email oficial para ALARMAS
    ];

    $url = "https://graph.microsoft.com/v1.0/sites/" . SITE_ID . "/lists/" . LIST_ID . "/items";

    // JSON_UNESCAPED_UNICODE garantiza que la @ y los acentos se envíen bien
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
        echo "<div style='font-family:sans-serif; padding:40px; color:#d32f2f; background:#fff5f5;'>";
        echo "<h2>❌ Error de SharePoint ($code)</h2>";
        echo "<p>Verifica que los nombres de las columnas en SharePoint coinciden exactamente:</p>";
        echo "<pre style='background:#eee; padding:20px;'>" . htmlspecialchars($res) . "</pre>";
        echo "</div>";
    }
}