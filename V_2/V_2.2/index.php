<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

// 1. Obtener técnicos de Microsoft 365
$token = getGraphToken();
$ch = curl_init("https://graph.microsoft.com/v1.0/users?\$select=displayName,userPrincipalName&\$top=999");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res_users = json_decode(curl_exec($ch), true);
curl_close($ch);

$usuarios_brutos = $res_users['value'] ?? [];

// 2. Filtrar técnicos que terminan en "- 720tec"
$usuarios_filtrados = array_filter($usuarios_brutos, function ($u) {
    return str_ends_with($u['displayName'], '- 720tec');
});
usort($usuarios_filtrados, fn($a, $b) => strcmp($a['displayName'], $b['displayName']));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>720tec | Mantenimiento Proactivo</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --720-navy: #003366;
            --720-cyan: #00d4ff;
            --720-dark: #001529;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top right, #002347, var(--720-dark));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .main-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 30px;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }

        .header {
            background: var(--720-navy);
            padding: 35px;
            text-align: center;
            color: white;
            border-bottom: 6px solid var(--720-cyan);
        }

        .header img {
            max-height: 50px;
            margin-bottom: 15px;
        }

        .form-section {
            padding: 40px;
        }

        .form-label {
            font-weight: 700;
            color: var(--720-navy);
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid #eaecf0;
            padding: 12px;
        }

        /* BOTÓN CON HOVER CYAN */
        .btn-submit {
            background: var(--720-navy);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 15px;
            font-weight: 700;
            width: 100%;
            transition: 0.3s;
            text-transform: uppercase;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: var(--720-cyan) !important;
            color: var(--720-navy) !important;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.3);
        }

        /* AVISOS DE ÉXITO Y ERROR */
        .alert-custom {
            border-radius: 15px;
            padding: 20px;
            font-weight: 600;
            border: none;
            margin-bottom: 30px;
            text-align: center;
        }

        .alert-success {
            background: #e7faf3;
            color: #008a52;
        }

        .alert-danger {
            background: #fff5f5;
            color: #d32f2f;
        }

        .footer {
            margin-top: 25px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.8rem;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="main-card">
        <div class="header">
            <img src="/images/logo-2.png" alt="720tec">
            <h2 class="m-0">Mantenimiento Proactivo</h2>
            <small style="color: var(--720-cyan)">Gestión de Tareas Recurrentes | Proyecto ASIR</small>
        </div>

        <div class="form-section">
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="alert-custom alert-success">✅ Tarea registrada correctamente en SharePoint.</div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
                <div class="alert-custom alert-danger">❌ Error: No se pudo guardar. (Código:
                    <?= htmlspecialchars($_GET['msg']) ?>)
                </div>
            <?php endif; ?>

            <form action="guardar_nueva.php" method="POST">
                <div class="mb-4">
                    <label class="form-label">Título de la Actividad</label>
                    <input type="text" name="titulo" class="form-control" required>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Prioridad</label>
                        <select name="prioridad" class="form-select">
                            <option value="Baja">Baja</option>
                            <option value="Media" selected>Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Impacto</label>
                        <select name="impacto" class="form-select">
                            <option value="Bajo">Bajo</option>
                            <option value="Medio" selected>Medio</option>
                            <option value="Alto">Alto</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Periodicidad</label>
                        <select name="periodicidad" class="form-select">
                            <option value="Diario">Diario</option>
                            <option value="Semanal">Semanal</option>
                            <option value="Mensual" selected>Mensual</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Técnico Responsable</label>
                    <select name="tecnico_data" class="form-select" required>
                        <option value="" disabled selected>Seleccione técnico oficial...</option>
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
                        placeholder="Detalles adicionales de la tarea..."></textarea>
                </div>

                <div class="mb-5">
                    <label class="form-label">URL Documentación (Opcional)</label>
                    <input type="url" name="documentacion" class="form-control" placeholder="URL Confluence">
                </div>

                <button type="submit" class="btn btn-submit">💾 Registrar tarea</button>
            </form>
        </div>
    </div>
    <div class="footer">© 2024 720tec | Desarrollado por <strong>gcano@720tec.es</strong></div>
</body>

</html>