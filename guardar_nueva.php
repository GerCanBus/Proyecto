<?php
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = getGraphToken();
    if (!$token) {
        header("Location: index.php?status=error&msg=AuthFail");
        exit();
    }

    $id_tarea = $_POST['id_tarea'] ?? '';
    $completada = $_POST['completada'];
    $observaciones = $_POST['observaciones'] ?? '';
    $creacion = $_POST['fecha_creacion'];

    if (!empty($id_tarea)) {

        // --- MODO TÉCNICO: ACTUALIZACIÓN INMUTABLE ---
        $periodicidad = $_POST['periodicidad_hidden'] ?? '';
        $proxima_revision = null;

        if ($completada === 'Sí') {
            $fecha_base = new DateTime(); // Hoy
            $p_lower = mb_strtolower($periodicidad, 'UTF-8');

            if (str_contains($p_lower, 'diaria')) {
                $fecha_base->modify('+1 day');
            } elseif (str_contains($p_lower, 'semanal')) {
                $fecha_base->modify('+7 days');
            } elseif (str_contains($p_lower, 'mensual')) {
                $fecha_base->modify('+1 month');
            } elseif (str_contains($p_lower, 'trimestral')) {
                $fecha_base->modify('+3 months');
            } elseif (str_contains($p_lower, 'semestral')) {
                $fecha_base->modify('+6 months');
            } elseif (str_contains($p_lower, 'anual')) {
                $fecha_base->modify('+1 year');
            }

            $proxima_revision = $fecha_base->format('Y-m-d');
        }

        $fields = [
            'Completada' => $completada,
            'Observaciones' => $observaciones
        ];

        if ($proxima_revision !== null) {
            $fields['ProximaRevision'] = $proxima_revision;
        }

        $url = "https://graph.microsoft.com/v1.0/sites/" . SITE_ID . "/lists/" . LIST_ID . "/items/" . $id_tarea . "/fields";
        $payload = json_encode($fields, JSON_UNESCAPED_UNICODE);
        $method = 'PATCH';

    } else {

        // --- MODO ADMINISTRADOR: CREAR NUEVA ACTIVIDAD ---
        $datos_tecnico = explode('|', $_POST['tecnico_data']);
        $periodicidad = $_POST['periodicidad'];

        $fields = [
            'Title' => $_POST['titulo'],
            'Prioridad' => $_POST['prioridad'],
            'Impacto' => $_POST['impacto'],
            'Periodicidad' => $periodicidad,
            'Completada' => $completada,
            'Observaciones' => $observaciones,
            'Documentacion' => $_POST['documentacion'] ?? '',
            'Tecnico' => $datos_tecnico[0],
            'Realiza' => $datos_tecnico[1],
            'FechaCreacion' => $creacion
        ];

        $url = "https://graph.microsoft.com/v1.0/sites/" . SITE_ID . "/lists/" . LIST_ID . "/items";
        $payload = json_encode(['fields' => $fields], JSON_UNESCAPED_UNICODE);
        $method = 'POST';
    }

    $ch = curl_init($url);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json; charset=utf-8'
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code == 201 || $code == 200 || $code == 204) {
        header("Location: index.php?status=success");
    } else {
        header("Location: index.php?status=error&msg=" . $code);
    }
    exit();
}