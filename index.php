<?php
header('Content-Type: text/html; charset=utf-8');
include('config.php');

$token = getGraphToken();

// 1. Obtener opciones dinámicas de SharePoint
function getColumnChoices($token, $columnName) {
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
$opciones_completada = getColumnChoices($token, 'Completada');

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

// 3. Obtener todas las tareas de SharePoint filtrando solo las que están en "No"
$url_tareas = "https://graph.microsoft.com/v1.0/sites/" . SITE_ID . "/lists/" . LIST_ID . "/items?\$expand=fields&\$top=999";
$ch = curl_init($url_tareas);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
$res_tareas = json_decode(curl_exec($ch), true);
curl_close($ch);

$tareas_pendientes = array_filter($res_tareas['value'] ?? [], function($item) {
    return ($item['fields']['Completada'] ?? 'No') === 'No';
});

$fecha_hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>720tec | Mantenimiento Proactivo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --navy: #003366; --cyan: #00d4ff; --dark-bg: #001529; --input-border: #eaecf0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: radial-gradient(circle at top right, #002347, var(--dark-bg)); min-height: 100vh; padding: 40px 20px; color: #334155; margin: 0; }
        .main-card { background: white; border-radius: 30px; box-shadow: 0 40px 80px rgba(0,0,0,0.5); width: 100%; max-width: 850px; overflow: hidden; margin: 0 auto 30px; position: relative; }
        .header { background: var(--navy); padding: 35px; text-align: center; color: white; border-bottom: 6px solid var(--cyan); }
        .header img { max-height: 45px; margin-bottom: 12px; }
        .header h2 { font-weight: 700; font-size: 24px; margin: 0; letter-spacing: -0.5px; }
        .form-section { padding: 45px; }
        .form-label { font-weight: 700; color: var(--navy); font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 12px; border: 2px solid var(--input-border); padding: 12px 15px; font-size: 14px; color: #334155; }
        .form-control:disabled, .form-select:disabled, .form-control[readonly] { background-color: #f8f9fa; color: #64748b; cursor: not-allowed; }
        .btn-submit { background: var(--navy); color: white; border: none; padding: 18px; border-radius: 16px; font-weight: 700; width: 100%; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 10px; transition: 0.3s; }
        .btn-submit:hover { background: var(--cyan) !important; color: var(--navy) !important; transform: translateY(-3px); }
        .tabla-card { background: white; border-radius: 20px; padding: 25px; max-width: 850px; margin: 0 auto 40px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
        .tabla-card h4 { color: var(--navy); font-weight: 700; margin-bottom: 20px; }
        .item-pendiente { cursor: pointer; transition: 0.2s; }
        .item-pendiente:hover { background-color: #f1f5f9; }
        .footer-text { text-align: center; color: rgba(255,255,255,0.4); font-size: 11px; margin-top: 20px; }
        .badge-modo { display: none; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>

    <div class="main-card">
        <div class="header">
            <img src="/images/logo-2.png" alt="720tec Logo">
            <h2>Mantenimiento Proactivo</h2>
        </div>

        <div class="form-section">
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div class="alert alert-success border-0 rounded-3 fw-bold text-center mb-4">✅ Acción procesada correctamente en SharePoint.</div>
            <?php endif; ?>

            <div id="badge_edicion" class="alert alert-warning border-0 rounded-3 text-center badge-modo">
                <i class="fa-solid fa-lock"></i> MODO ACTUALIZACIÓN: Los datos originales están protegidos. Solo puedes cambiar el estado y añadir observaciones.
            </div>

            <form action="guardar_nueva.php" method="POST" id="form_mantenimiento">
                <input type="hidden" name="id_tarea" id="id_tarea" value="">
                <input type="hidden" name="periodicidad_hidden" id="periodicidad_hidden" value="">

                <div class="mb-4">
                    <label class="form-label">Título de la Actividad</label>
                    <input type="text" name="titulo" id="titulo" class="form-control" placeholder="Nombre descriptivo de la tarea" required>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Prioridad</label>
                        <select name="prioridad" id="prioridad" class="form-select">
                            <?php foreach ($opciones_prioridad as $opt): ?>
                                <option value="<?= $opt ?>"><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Impacto</label>
                        <select name="impacto" id="impacto" class="form-select">
                            <?php foreach ($opciones_impacto as $opt): ?>
                                <option value="<?= $opt ?>"><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Periodicidad</label>
                        <select name="periodicidad" id="periodicidad" class="form-select">
                            <?php foreach ($opciones_periodicidad as $opt): ?>
                                <option value="<?= $opt ?>"><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="color: #008a52; font-weight: 800;">¿Tarea Completada?</label>
                        <select name="completada" id="completada" class="form-select" style="border: 2px solid #008a52;">
                            <?php foreach ($opciones_completada as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($opt === 'No') ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Técnico Responsable</label>
                    <select name="tecnico_data" id="tecnico_data" class="form-select" required>
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
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="2" placeholder="Anotaciones importantes de la revisión..."></textarea>
                </div>

                <div class="mb-5">
                    <label class="form-label">URL Documentación (Ayuda)</label>
                    <input type="url" name="documentacion" id="documentacion" class="form-control" placeholder="Link a manual o procedimiento">
                </div>

                <input type="hidden" name="fecha_creacion" value="<?= $fecha_hoy ?>">

                <button type="submit" class="btn btn-submit" id="btn_accion">
                    <i class="fa-solid fa-file-signature"></i> Registrar Nueva Tarea
                </button>
            </form>
        </div>
    </div>

    <div class="tabla-card">
        <h4>📋 Tareas Pendientes Existentes (Haz clic para Gestionar Estado)</h4>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr class="table-light">
                        <th>Actividad</th>
                        <th>Periodicidad</th>
                        <th>Técnico Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tareas_pendientes) === 0): ?>
                        <tr><td colspan="3" class="text-center text-muted">No hay tareas pendientes en este momento.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tareas_pendientes as $t): ?>
                            <tr class="item-pendiente" onclick="cargarTarea('<?= $t['id'] ?>', '<?= addslashes($t['fields']['Title'] ?? '') ?>', '<?= $t['fields']['Prioridad'] ?? '' ?>', '<?= $t['fields']['Impacto'] ?? '' ?>', '<?= $t['fields']['Periodicidad'] ?? '' ?>', '<?= htmlspecialchars(($t['fields']['Tecnico'] ?? '').'|'.($t['fields']['Realiza'] ?? '')) ?>', '<?= addslashes($t['fields']['Observaciones'] ?? '') ?>', '<?= addslashes($t['fields']['Documentacion'] ?? '') ?>')">
                                <td><strong><?= htmlspecialchars($t['fields']['Title'] ?? '') ?></strong></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($t['fields']['Periodicidad'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars($t['fields']['Tecnico'] ?? 'Sin asignar') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-text">Desarrollado por <strong>gcano@720tec.es</strong></div>

    <script>
        function cargarTarea(id, titulo, prioridad, impacto, periodicidad, tecnico, observaciones, documentacion) {
            // Asignar ID de la tarea a modificar
            document.getElementById('id_tarea').value = id;
            
            // Rellenar datos originales
            document.getElementById('titulo').value = titulo;
            document.getElementById('prioridad').value = prioridad;
            document.getElementById('impacto').value = impacto;
            document.getElementById('periodicidad').value = periodicidad;
            document.getElementById('periodicidad_hidden').value = periodicidad; // Respaldo para procesar la fecha en PHP
            document.getElementById('completada').value = 'No'; 
            document.getElementById('observaciones').value = observaciones;
            document.getElementById('documentacion').value = documentacion;
            document.getElementById('tecnico_data').value = tecnico;

            // CONVERTIR CAMPOS EN INMUTABLES (Hacerlos inmodificables)
            document.getElementById('titulo').readOnly = true;
            document.getElementById('documentacion').readOnly = true;
            document.getElementById('prioridad').disabled = true;
            document.getElementById('impacto').disabled = true;
            document.getElementById('periodicidad').disabled = true;
            document.getElementById('tecnico_data').disabled = true;

            // Mostrar el aviso de modo bloqueo y actualizar diseño del botón
            document.getElementById('badge_edicion').style.display = 'block';
            const btn = document.getElementById('btn_accion');
            btn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Imputar Cambios de Revisión';
            btn.style.backgroundColor = '#008a52';
            
            // Hacemos scroll suave arriba al formulario
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>