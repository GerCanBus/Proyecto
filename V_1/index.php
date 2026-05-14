<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

// 1. Obtener usuarios de Microsoft 365
$token = getGraphToken();
$ch = curl_init("https://graph.microsoft.com/v1.0/users?\$select=displayName,userPrincipalName&\$top=999");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res_users = json_decode(curl_exec($ch), true);
curl_close($ch);

$usuarios_brutos = $res_users['value'] ?? [];

// 2. Filtrar solo técnicos del dominio 720tec (evita invitados y externos)
$usuarios_filtrados = array_filter($usuarios_brutos, function ($u) {
    $email = strtolower($u['userPrincipalName']);
    return str_contains($email, '@720tec.es') || str_contains($email, '@720tec.com');
});

// Ordenar alfabéticamente por nombre
usort($usuarios_filtrados, fn($a, $b) => strcmp($a['displayName'], $b['displayName']));
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
        }

        .header {
            background: var(--sp-blue);
            padding: 35px;
            text-align: center;
            color: white;
        }

        .form-section {
            padding: 40px;
        }

        .form-label {
            font-weight: 700;
            color: var(--sp-blue);
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            border: 2px solid #eaecf0;
            padding: 12px;
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
        }

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
            <h2 class="fw-bold m-0">Proyecto ASIR | Registro</h2>
        </div>
        <div class="form-section">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success border-0 rounded-4 mb-4">✅ Tarea guardada con éxito para automatización.
                </div>
            <?php endif; ?>

            <form action="guardar_nueva.php" method="POST">
                <div class="mb-4">
                    <label class="form-label">Título de la Tarea</label>
                    <input type="text" name="titulo" class="form-control" placeholder="Ej: Mantenimiento Servidores"
                        required>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Prioridad</label>
                        <select name="prioridad" class="form-select">
                            <option value="Baja">Baja</option>
                            <option value="Media" selected>Media</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Periodicidad</label>
                        <select name="periodicidad" class="form-select">
                            <option value="Diario">Diario</option>
                            <option value="Semanal">Semanal</option>
                            <option value="Mensual" selected>Mensual</option>
                        </select>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="form-label">Técnico Responsable (Dominio 720tec)</label>
                    <select name="tecnico_data" class="form-select" required>
                        <option value="" disabled selected>Seleccione un técnico oficial...</option>
                        <?php foreach ($usuarios_filtrados as $u): ?>
                            <option value="<?= htmlspecialchars($u['displayName'] . '|' . $u['userPrincipalName']) ?>">
                                <?= htmlspecialchars($u['displayName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted mt-2 d-block">Solo se muestran correos oficiales de la
                        organización.</small>
                </div>

                <button type="submit" class="btn btn-submit">💾 Guardar en SharePoint</button>
            </form>
        </div>
    </div>
</body>

</html>