<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

// 1. Obtener usuarios oficiales de 720tec para el desplegable
$token = getGraphToken();
$ch = curl_init("https://graph.microsoft.com/v1.0/users?\$select=displayName,userPrincipalName");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res_users = json_decode(curl_exec($ch), true);
curl_close($ch);
$usuarios = $res_users['value'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>720tec | Registro de Tareas</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sp-blue: #003366;
            --sp-cyan: #00d4ff;
            --sp-bg: #f8f9fa;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at top right, #002347, #001529);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header {
            background: var(--sp-blue);
            padding: 35px;
            text-align: center;
            color: white;
        }

        .header h2 {
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .form-section {
            padding: 40px;
        }

        .form-label {
            font-weight: 700;
            color: var(--sp-blue);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid #eaecf0;
            padding: 12px;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: var(--sp-cyan);
            box-shadow: none;
        }

        .btn-submit {
            background: var(--sp-blue);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 14px;
            font-weight: 700;
            width: 100%;
            transition: all 0.3s ease;
            text-transform: uppercase;
            margin-top: 20px;
        }

        /* EL HOVER AZUL CLARITO (CYAN) */
        .btn-submit:hover {
            background: var(--sp-cyan) !important;
            color: var(--sp-blue) !important;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 212, 255, 0.3);
        }
    </style>
</head>

<body>
    <div class="glass-card">
        <div class="header">
            <h2>Proyecto ASIR | Registro</h2>
        </div>
        <div class="form-section">
                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 rounded-4 mb-4">✅ Tarea guardada con éxito.</div>
                <?php endif; ?>

            <form action="guardar_nueva.php" method="POST">
                <div class="mb-4">
                    <label class="form-label">Título de la Tarea</label>
                    <input type="text" name="titulo" class="form-control" placeholder="Ej: Mantenimiento Preventivo"
                        required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Prioridad</label>
                        <select name="prioridad" class="form-select">
                            <option value="Baja">Baja</option>
                            <option value="Media" selected>Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Periodicidad</label>
                        <select name="periodicidad" class="form-select">
                            <option value="Semanal">Semanal</option>
                            <option value="Mensual" selected>Mensual</option>
                            <option value="Trimestral">Trimestral</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Técnico Responsable (720tec)</label>
                    <select name="tecnico_data" class="form-select" required>
                        <option value="" disabled selected>Seleccione un técnico...</option>
                            <?php foreach ($usuarios as $u): ?>
                            <option value="<?= htmlspecialchars($u['displayName'] . '|' . $u['userPrincipalName']) ?>">
                                    <?= htmlspecialchars($u['displayName']) ?>
                                (
                                <?= htmlspecialchars($u['userPrincipalName']) ?>)
                                    </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-submit">💾 Registrar en SharePoint</button>
            </form>
        </div>
    </div>
</body>

</html>