<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_matriculas.php");
    exit('ID de matrícula no válido.');
}
$id_matricula = (int)$_GET['id'];

$conn = getDBConnection();
$message = '';
$error = '';
$matricula = null;
$dias_clase = [];

// Acción de Anular o Actualizar Asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['anular_matricula'])) {
        $observaciones_anulacion = trim($_POST['observaciones_anulacion']);
        if (empty($observaciones_anulacion)) {
            $error = "Debe proporcionar un motivo para la anulación.";
        } else {
            $stmt = $conn->prepare("CALL sp_matricula_anular(?, ?)");
            $stmt->bind_param("is", $id_matricula, $observaciones_anulacion);
            if ($stmt->execute()) {
                $message = "Matrícula anulada correctamente.";
            } else {
                $error = "Error al anular la matrícula: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    if (isset($_POST['actualizar_asistencia'])) {
        $id_dia_clase = (int)$_POST['id_dia_clase'];
        $estado_asistencia = $_POST['estado_asistencia'];

        $stmt = $conn->prepare("CALL sp_dia_clase_actualizar_asistencia(?, ?)");
        $stmt->bind_param("is", $id_dia_clase, $estado_asistencia);
        if ($stmt->execute()) {
            $message = "Asistencia actualizada correctamente.";
        } else {
            $error = "Error al actualizar la asistencia: " . $stmt->error;
        }
        $stmt->close();
    }
}


// Cargar datos de la matrícula
$stmt_details = $conn->prepare("CALL sp_matricula_leer_detalles_por_id(?)");
$stmt_details->bind_param("i", $id_matricula);
if ($stmt_details->execute()) {
    $result = $stmt_details->get_result();
    if ($result->num_rows > 0) {
        $matricula = $result->fetch_assoc();
    } else {
        header("Location: admin_matriculas.php");
        exit('Matrícula no encontrada.');
    }
}
$stmt_details->close();
while($conn->more_results() && $conn->next_result()) {}

// Cargar días de clase
$stmt_dias = $conn->prepare("CALL sp_dias_clase_leer_por_matricula(?)");
$stmt_dias->bind_param("i", $id_matricula);
if ($stmt_dias->execute()) {
    $result_dias = $stmt_dias->get_result();
    $dias_clase = $result_dias->fetch_all(MYSQLI_ASSOC);
}
$stmt_dias->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Matrícula</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Detalle de Matrícula #<?= htmlspecialchars($id_matricula) ?></h2>
        <a href="admin_matriculas.php" class="btn btn-secondary">Volver al Listado</a>
    </div>

    <?php if ($message): ?><div class="alert alert-success mt-3"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($matricula): ?>
    <div class="row mt-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Información General</div>
                <div class="card-body">
                    <p><strong>Alumno:</strong> <?= htmlspecialchars($matricula['nombre_alumno']) ?></p>
                    <p><strong>Curso:</strong> <?= htmlspecialchars($matricula['nombre_curso']) ?></p>
                    <p><strong>Profesor:</strong> <?= htmlspecialchars($matricula['nombre_profesor']) ?></p>
                    <p><strong>Ubicación:</strong> <?= htmlspecialchars($matricula['nombre_piscina']) ?> (Carril <?= htmlspecialchars($matricula['numero_carril']) ?>)</p>
                    <p><strong>Horario:</strong> <?= htmlspecialchars($matricula['nombre_tipo_horario']) ?> de <?= date('g:i A', strtotime($matricula['hora_inicio'])) ?> a <?= date('g:i A', strtotime($matricula['hora_fin'])) ?></p>
                    <p><strong>Periodo:</strong> Del <?= date('d/m/Y', strtotime($matricula['fecha_inicio'])) ?> al <?= date('d/m/Y', strtotime($matricula['fecha_fin'])) ?></p>
                    <p><strong>Pago:</strong> S/. <?= number_format($matricula['monto_pagado'], 2) ?> vía <?= htmlspecialchars($matricula['forma_pago']) ?></p>
                    <p><strong>Estado:</strong> <span class="badge badge-<?= $matricula['estado']=='Vigente'?'success':'danger' ?>"><?= htmlspecialchars($matricula['estado']) ?></span></p>
                    <p><strong>Observaciones:</strong><br><?= nl2br(htmlspecialchars($matricula['observaciones'])) ?></p>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">Días de Clase Programados</div>
                <div class="card-body table-responsive" style="max-height: 400px;">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Fecha</th><th>Estado Asistencia</th><th style="width: 150px;">Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach($dias_clase as $dia): ?>
                            <tr>
                                <form method="POST" action="admin_matricula_detalle.php?id=<?= $id_matricula ?>">
                                    <input type="hidden" name="id_dia_clase" value="<?= $dia['id_dia_clase'] ?>">
                                    <td><?= date('d/m/Y', strtotime($dia['fecha_clase'])) ?></td>
                                    <td>
                                        <select name="estado_asistencia" class="form-control form-control-sm">
                                            <option value="Programada" <?= $dia['estado_asistencia'] == 'Programada' ? 'selected' : '' ?>>Programada</option>
                                            <option value="Asistio" <?= $dia['estado_asistencia'] == 'Asistio' ? 'selected' : '' ?>>Asistió</option>
                                            <option value="Inasistencia_Justificada" <?= $dia['estado_asistencia'] == 'Inasistencia_Justificada' ? 'selected' : '' ?>>Falta Justificada</option>
                                            <option value="Inasistencia_Injustificada" <?= $dia['estado_asistencia'] == 'Inasistencia_Injustificada' ? 'selected' : '' ?>>Falta Injustificada</option>
                                            <option value="Postergada" <?= $dia['estado_asistencia'] == 'Postergada' ? 'selected' : '' ?>>Postergada</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button type="submit" name="actualizar_asistencia" class="btn btn-sm btn-success">Guardar</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">Acciones</div>
                <div class="card-body">
                    <?php if ($matricula['estado'] === 'Vigente'): ?>
                    <form method="POST" onsubmit="return confirm('¿Está seguro de que desea ANULAR esta matrícula? Esta acción no se puede deshacer.');">
                        <div class="form-group">
                            <label for="observaciones_anulacion">Motivo de Anulación</label>
                            <textarea name="observaciones_anulacion" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="anular_matricula" class="btn btn-danger btn-block">Anular Matrícula</button>
                    </form>
                    <hr>
                    <button class="btn btn-warning btn-block disabled">Cambiar Horario (Próximamente)</button>
                    <?php else: ?>
                    <p class="text-muted">No hay acciones disponibles para esta matrícula.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
