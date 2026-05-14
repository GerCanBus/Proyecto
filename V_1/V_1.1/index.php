<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

// 1. Obtener usuarios y filtrar solo los de @720tec (evita invitados y externos)
$token = getGraphToken();
$ch = curl_init("https://graph.microsoft.com/v1.0/users?\$select=displayName,userPrincipalName&\$top=999");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res_users = json_decode(curl_exec($ch), true);
curl_close($ch);

$usuarios_filtrados = array_filter($res_users['value'] ?? [], function ($u) {
    $email = strtolower($u['userPrincipalName']);
    return (str_contains($email, '@720tec.es') || str_contains($email, '@720tec.com'))
        && !str_contains($email, '#ext#');
});
usort($usuarios_filtrados, fn($a, $b) => strcmp($a['displayName'], $b['displayName']));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>720tec | Portal de Registro ASIR</title>
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
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
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
            padding: 40px;
            text-align: center;
            color: white;
            position: relative;
        }

        .header h2 {
            font-weight: 800;
            letter-spacing: -1px;
            margin: 0;
            text-transform: uppercase;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--720-cyan);
        }

        .form-section {
            padding: 45px;
        }

        .form-label {
            font-weight: 700;
            color: var(--720-navy);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 15px;
            border: 2px solid #eaecf0;
            padding: 14px;
            font-size: 0.95rem;
            transition: 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--720-cyan);
            box-shadow: 0 0 0 4px rgba(0, 212, 255, 0.1);
            outline: none;
        }

        .btn-submit {
            background: var(--720-navy);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 18px;
            font-weight: 700;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background: var(--720-cyan) !important;
            color: var(--720-navy) !important;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 212, 255, 0.4);
        }

        .alert-success {
            background: #e7faf3;
            color: #008a52;
            border: none;
            border-radius: 15px;
            padding: 20px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="main-card">
        <div class="header">
            <h2>Proyecto ASIR | Registro</h2>
        </div>
        <div class="form-section">
                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success d-flex align-items-center mb-5" role="alert">
                    <span class="fs-4 me-3">✅</span> Tarea registrada correctamente en la lista Tareas720.
                </div>
                <?php endif; ?>

            <form action="guardar_nueva.php" method="POST">
                <div class="mb-4">
                    <label class="form-label">Título de la Tarea</label>
                    <input type="text" name="titulo" class="form-control"
                        placeholder="Nombre descriptivo de la actividad" required>
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
                    <label class="form-label">Técnico Responsable (Dominio 720tec)</label>
                    <select name="tecnico_data" class="form-select" required>
                        <option value="" disabled selected>Seleccione un técnico oficial...</option>
                            <?php foreach ($usuarios_filtrados as $u): ?>
                            <option value="<?= htmlspecialchars($u['displayName'] . '|' . $u['userPrincipalName']) ?>">
                                    <?= htmlspecialchars($u['displayName']) ?>
                            </option>
                            <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-5">
                    <label class="form-label">Link Documentación (Opcional)</label>
                    <input type="url" name="documentacion" class="form-control"
                        placeholder="https://docs.microsoft.com/...">
                </div>

                <button type="submit" class="btn btn-submit">💾 GUARDAR EN SHAREPOINT</button>
            </form>
        </div>
    </div>
</body>

</html>