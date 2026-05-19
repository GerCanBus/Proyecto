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
// Nueva opción traída dinámicamente de SharePoint:
$opciones_completada = getColumnChoices($token, 'Completada');

// 2. Obtener técnicos oficiales (Filtro "- 720tec")
$ch = curl_init("https://graph.microsoft.com/v1.0/users?\$select=displayName,userPrincipalName&\$top=999");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res_users = json_decode(curl_exec($ch), true);
curl_close($ch);

$usuarios_filtrados = array_filter($res_users['value'] ?? [], function ($u) {
    return strpos($u['displayName'], '- 720tec') !== false;
});
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>720tec - Registro Mantenimiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --bg-dark: #0b0f19;
            --card-dark: #151c2c;
            --accent-cyan: #00f2fe;
            --accent-blue: #4facfe;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: #243049;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .form-card {
            background: var(--card-dark);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 650px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .brand-header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .brand-header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--accent-cyan);
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            background-color: #0b0f19 !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-main) !important;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent-cyan) !important;
            box-shadow: 0 0 0 3px rgba(0, 242, 254, 0.15) !important;
        }

        .form-select option {
            background-color: var(--card-dark);
            color: var(--text-main);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-blue));
            border: none;
            color: #0b0f19;
            font-weight: 700;
            font-size: 16px;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 242, 254, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 242, 254, 0.35);
            color: #0b0f19;
        }

        .footer-text {
            margin-top: 30px;
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
        }

        .alert-custom {
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="container d-flex justify-content-center">
        <div class="form-card">
            <div class="brand-header">
                <h1><i class="fa-solid fa-layer-group"></i> 720tec Portal</h1>
                <p>Sistema Híbrido de Mantenimiento Preventivo</p>
            </div>

            <?php if (isset($_GET['status'])): ?>
                <?php if ($_GET['status'] == 'success'): ?>
                    <div class="alert alert-success alert-custom bg-success-subtle text-success border-success-subtle"
                        role="alert">
                        <i class="fa-solid fa-circle-check"></i> ¡Tarea registrada exitosamente en SharePoint Online!
                    </div>
                <?php elseif ($_GET['status'] == 'error'): ?>
                    <div class="alert alert-danger alert-custom bg-danger-subtle text-danger border-danger-subtle" role="alert">
                        <i class="fa-solid fa-circle-xmark"></i> Error en la operación:
                        <?= htmlspecialchars($_GET['msg'] ?? 'Unknown') ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form action="guardar_nueva.php" method="POST">
                <div class="mb-4">
                    <label class="form-label">Título de la Tarea / Incidencia</label>
                    <input type="text" name="titulo" class="form-control"
                        placeholder="Ej: Revisión Mensual de Servidores NAS" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Prioridad</label>
                        <select name="prioridad" class="form-select" required>
                            <option value="" disabled selected>Seleccione...</option>
                            <?php foreach ($opciones_prioridad as $op): ?>
                                <option value="<?= htmlspecialchars($op) ?>"><?= htmlspecialchars($op) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Impacto del Sistema</label>
                        <select name="impacto" class="form-select" required>
                            <option value="" disabled selected>Seleccione...</option>
                            <?php foreach ($opciones_impacto as $op): ?>
                                <option value="<?= htmlspecialchars($op) ?>"><?= htmlspecialchars($op) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Periodicidad</label>
                        <select name="periodicidad" class="form-select" required>
                            <option value="" disabled selected>Seleccione...</option>
                            <?php foreach ($opciones_periodicidad as $op): ?>
                                <option value="<?= htmlspecialchars($op) ?>"><?= htmlspecialchars($op) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">¿Tarea Completada?</label>
                        <select name="completada" class="form-select" required>
                            <option value="" disabled selected>Seleccione...</option>
                            <?php foreach ($opciones_completada as $op): ?>
                                <option value="<?= htmlspecialchars($op) ?>"><?= htmlspecialchars($op) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Fecha de Creación</label>
                        <input type="datetime-local" name="fecha_creacion" class="form-control"
                            value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Fecha de Vencimiento</label>
                        <input type="datetime-local" name="fecha_vencimiento" class="form-control" required>
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