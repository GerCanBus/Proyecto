<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

$token = getGraphToken();

// 1. Obtener opciones dinámicas de SharePoint
function getColumnChoices($token, $columnName)
{
    $url = "https://graph.microsoft.com/v1.0/sites/" . SITE_ID . "/lists/" . LIST_ID . "/columns/" . $columnName;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $res['choice']['choices'] ?? [];
}

$opciones_prioridad = getColumnChoices($token, 'Prioridad');
$opciones_impacto = getColumnChoices($token, 'Impacto');
$opciones_periodicidad = getColumnChoices($token, 'Periodicidad');

// 2. Obtener técnicos oficiales (Filtro "- 720tec")
$ch = curl_init("https://graph.microsoft.com/v1.0/users?\$select=displayName,userPrincipalName&\$top=999");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res_users = json_decode(curl_exec($ch), true);
curl_close($ch);

$usuarios_filtrados = array_filter($res_users['value'] ?? [], function ($u) {
    return str_ends_with($u['displayName'], '- 720tec');
});
usort($usuarios_filtrados, fn($a, $b) => strcmp($a['displayName'], $b['displayName']));

// 3. Fechas autorrellenadas
$fecha_hoy = date('Y-m-d');
$fecha_vencimiento = date('Y-m-d', strtotime('+7 days'));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>720tec | Mantenimiento Proactivo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --navy: #003366;
            --cyan: #00d4ff;
            --dark-bg: #001529;
            --input-border: #eaecf0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top right, #002347, var(--dark-bg));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            margin: 0;
        }

        .main-card {
            background: white;
            border-radius: 30px;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 850px;
            overflow: hidden;
            position: relative;
        }

        .header {
            background: var(--navy);
            padding: 35px;
            text-align: center;
            color: white;
            border-bottom: 6px solid var(--cyan);
        }

        .header img {
            max-height: 45px;
            margin-bottom: 12px;
        }

        .header h2 {
            font-weight: 700;
            font-size: 24px;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .header p {
            color: var(--cyan);
            font-size: 13px;
            font-weight: 600;
            margin: 5px 0 0;
            opacity: 0.9;
        }

        .form-section {
            padding: 45px;
        }

        .form-label {
            font-weight: 700;
            color: var(--navy);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid var(--input-border);
            padding: 12px 15px;
            font-size: 14px;
            color: #334155;
            transition: 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.1);
            outline: none;
        }

        /* Estilo del Botón solicitado */
        .btn-submit {
            background: var(--navy);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 16px;
            font-weight: 700;
            width: 100%;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: var(--cyan) !important;
            color: var(--navy) !important;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.3);
        }

        .alert-custom {
            border-radius: 15px;
            padding: 15px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            border: 2px solid;
        }

        .footer-text {
            position: absolute;
            right: 40px;
            bottom: 20px;
            color: rgba(255, 255, 255, 0.4);
            font-size: 11px;
            text-align: right;
        }
    </style>
</head>

<body>

    <div class="main-card">
        <div class="header">
            <img src="/images/logo-2.png" alt="720tec Logo">
            <h2>Mantenimiento Proactivo</h2>
            <p>Asignación de Tareas Recurrentes</p>
        </div>

        <div class="form-section">
            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'success'): ?>
                    <div class="alert-custom" style="background: #e7faf3; color: #008a52; border-color: #008a52;">✅ Tarea
                        registrada correctamente.</div>
                <?php else: ?>
                    <div class="alert-custom" style="background: #fff5f5; color: #d32f2f; border-color: #d32f2f;">❌ Error en el
                        registro (Cód: <?= htmlspecialchars($_GET['msg'] ?? 'Error') ?>).</div>
                <?php endif; ?>
            <?php endif; ?>

            <form action="guardar_nueva.php" method="POST">
                <div class="mb-4">
                    <label class="form-label">Título de la Actividad</label>
                    <input type="text" name="titulo" class="form-control" placeholder="Nombre descriptivo de la tarea"
                        required>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Prioridad</label>
                        <select name="prioridad" class="form-select">
                            <?php foreach ($opciones_prioridad as $opt): ?>
                                <option value="<?= $opt ?>"><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Impacto</label>
                        <select name="impacto" class="form-select">
                            <?php foreach ($opciones_impacto as $opt): ?>
                                <option value="<?= $opt ?>"><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Periodicidad</label>
                        <select name="periodicidad" class="form-select">
                            <?php foreach ($opciones_periodicidad as $opt): ?>
                                <option value="<?= $opt ?>"><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Fecha de Creación</label>
                        <input type="date" name="fecha_creacion" class="form-control" value="<?= $fecha_hoy ?>"
                            readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha Vencimiento (+7 días)</label>
                        <input type="date" name="fecha_vencimiento" class="form-control"
                            value="<?= $fecha_vencimiento ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Técnico Responsable</label>
                    <select name="tecnico_data" class="form-select" required>
                        <option value="" disabled selected>Seleccione Técnico...</option>
                        <?php foreach ($usuarios_filtrados as $u): ?>
                            <option value="<?= htmlspecialchars($u['displayName'] . '|' . $u['userPrincipalName']) ?>">
                                <?= htmlspecialchars($u['displayName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"
                        placeholder="Anotaciones importantes..."></textarea>
                </div>

                <div class="mb-5">
                    <label class="form-label">URL Documentación</label>
                    <input type="url" name="documentacion" class="form-control"
                        placeholder="Link a manual o procedimiento">
                </div>

                <button type="submit" class="btn btn-submit">
                    <i class="fa-solid fa-file-signature"></i> Registrar Tarea
                </button>
            </form>
        </div>
    </div>

    <div class="footer-text">
        Desarrollado por <strong>gcano@720tec.es</strong>
    </div>

</body>

</html>