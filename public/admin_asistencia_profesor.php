<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

$conn = getDBConnection();
$message = '';
$error = '';

// --- LÓGICA DE CONTROLADOR (POST para guardar asistencia) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_asistencia'])) {
    $id_profesor = (int)$_POST['id_profesor'];
    $id_horario_clase = (int)$_POST['id_horario_clase'];
    $fecha = $_POST['fecha'];
    $estado = $_POST['estado'];
    $observaciones = trim($_POST['observaciones']);

    $stmt = $conn->prepare("CALL sp_asistencia_profesor_crear_o_actualizar(?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $id_profesor, $id_horario_clase, $fecha, $estado, $observaciones);
    if ($stmt->execute()) {
        $message = "Asistencia para el " . date('d/m/Y', strtotime($fecha)) . " guardada.";
    } else {
        $error = "Error al guardar asistencia: " . $stmt->error;
    }
    $stmt->close();
}


// --- LÓGICA DE VISTA (GET para mostrar el horario) ---
$profesores = [];
if ($result = $conn->query("CALL sp_profesor_leer_todos()")) {
    $profesores = $result->fetch_all(MYSQLI_ASSOC);
}
while($conn->more_results() && $conn->next_result()) {}

$horario_generado = [];
$profesor_seleccionado = null;
if (isset($_GET['generar_horario'])) {
    $id_profesor_filtro = (int)$_GET['id_profesor'];
    $fecha_inicio = $_GET['fecha_inicio'];
    $fecha_fin = $_GET['fecha_fin'];

    if (empty($id_profesor_filtro) || empty($fecha_inicio) || empty($fecha_fin)) {
        $error = "Debe seleccionar un profesor y un rango de fechas.";
    } else {
        // Obtener el profesor seleccionado
        $stmt_prof = $conn->prepare("CALL sp_profesor_leer_por_id(?)");
        $stmt_prof->bind_param("i", $id_profesor_filtro);
        $stmt_prof->execute();
        $profesor_seleccionado = $stmt_prof->get_result()->fetch_assoc();
        $stmt_prof->close();
        while($conn->more_results() && $conn->next_result()) {}

        // Obtener los horarios asignados al profesor
        $stmt_horarios = $conn->prepare("SELECT hc.id_horario_clase, c.nombre_curso, th.descripcion as tipo_horario_desc, hc.hora_inicio
                                         FROM horarios_clase hc
                                         JOIN cursos c ON hc.id_curso = c.id_curso
                                         JOIN tipos_horario th ON hc.id_tipo_horario = th.id_tipo_horario
                                         WHERE hc.id_profesor = ? AND hc.estado = 'Activo'");
        $stmt_horarios->bind_param("i", $id_profesor_filtro);
        $stmt_horarios->execute();
        $horarios_asignados = $stmt_horarios->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_horarios->close();
        while($conn->more_results() && $conn->next_result()) {}

        // Generar las fechas de clase
        $periodo = new DatePeriod(new DateTime($fecha_inicio), new DateInterval('P1D'), (new DateTime($fecha_fin))->modify('+1 day'));
        $dias_map = ['L' => 1, 'M' => 2, 'X' => 3, 'J' => 4, 'V' => 5, 'S' => 6, 'D' => 7];

        foreach ($periodo as $fecha) {
            $num_dia_semana = (int)$fecha->format('N');
            foreach ($horarios_asignados as $ha) {
                $dias_activos = [];
                 foreach ($dias_map as $char => $num_dia) {
                    if (stripos($ha['tipo_horario_desc'], $char) !== false) $dias_activos[] = $num_dia;
                }
                if (in_array($num_dia_semana, $dias_activos)) {
                    $horario_generado[$fecha->format('Y-m-d')][] = $ha;
                }
            }
        }
        ksort($horario_generado);
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencia de Profesores</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>
<div class="container mt-4">
    <h2>Asistencia de Profesores</h2>

    <div class="card mb-4">
        <div class="card-header">Seleccionar Profesor y Fechas</div>
        <div class="card-body">
            <form method="get">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5">
                        <label>Profesor</label>
                        <select name="id_profesor" class="form-control">
                            <?php foreach($profesores as $p) echo "<option value='{$p['id_profesor']}' ".((isset($_GET['id_profesor']) && $_GET['id_profesor']==$p['id_profesor'])?'selected':'').">{$p['nombres']} {$p['apellidos']}</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-3"><label>Fecha Inicio</label><input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>"></div>
                    <div class="form-group col-md-3"><label>Fecha Fin</label><input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>"></div>
                    <div class="form-group col-md-1"><button type="submit" name="generar_horario" class="btn btn-primary">Ver</button></div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if (!empty($horario_generado)): ?>
    <div class="card">
        <div class="card-header">Horario de <?= htmlspecialchars($profesor_seleccionado['nombres'] . ' ' . $profesor_seleccionado['apellidos']) ?></div>
        <div class="card-body">
            <?php foreach($horario_generado as $fecha => $clases): ?>
                <h5><?= date('d/m/Y', strtotime($fecha)) ?></h5>
                <table class="table table-sm table-bordered">
                <?php foreach($clases as $clase): ?>
                    <form method="post">
                    <input type="hidden" name="id_profesor" value="<?= $profesor_seleccionado['id_profesor'] ?>">
                    <input type="hidden" name="id_horario_clase" value="<?= $clase['id_horario_clase'] ?>">
                    <input type="hidden" name="fecha" value="<?= $fecha ?>">
                    <tr>
                        <td><?= htmlspecialchars($clase['nombre_curso']) ?><br><small><?= date('g:i A', strtotime($clase['hora_inicio'])) ?></small></td>
                        <td>
                            <select name="estado" class="form-control form-control-sm">
                                <option value="Asistio">Asistió</option>
                                <option value="Permiso">Permiso</option>
                                <option value="Inasistencia_Justificada">Falta Justificada</option>
                                <option value="Inasistencia_Injustificada">Falta Injustificada</option>
                            </select>
                        </td>
                        <td><input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Observaciones"></td>
                        <td><button type="submit" name="guardar_asistencia" class="btn btn-sm btn-success">Guardar</button></td>
                    </tr>
                    </form>
                <?php endforeach; ?>
                </table>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif(isset($_GET['generar_horario'])): ?>
        <div class="alert alert-info">No se encontraron clases programadas para el profesor y el rango de fechas seleccionado.</div>
    <?php endif; ?>
</div>
</body>
</html>
