<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

$conn = getDBConnection();

// --- LÓGICA DE VISTA: Cargar datos para los filtros ---
function fetchData($conn, $sp_name) {
    $data = [];
    while($conn->more_results() && $conn->next_result()) {}
    if ($result = $conn->query("CALL $sp_name()")) {
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
    }
    return $data;
}

$alumnos = fetchData($conn, 'sp_alumno_leer_todos');
$cursos = fetchData($conn, 'sp_curso_leer_todos');
$formas_pago = fetchData($conn, 'sp_forma_pago_leer_todas');

// --- LÓGICA DE CONTROLADOR: Procesar filtros y obtener reporte ---
$reporte_data = [];
$total_ventas = 0;
$is_report_generated = false;

if (isset($_GET['generate_report'])) {
    $is_report_generated = true;

    $fecha_inicio = !empty($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : null;
    $fecha_fin = !empty($_GET['fecha_fin']) ? $_GET['fecha_fin'] : null;
    $id_alumno = !empty($_GET['id_alumno']) ? (int)$_GET['id_alumno'] : null;
    $id_curso = !empty($_GET['id_curso']) ? (int)$_GET['id_curso'] : null;
    $id_forma_pago = !empty($_GET['id_forma_pago']) ? (int)$_GET['id_forma_pago'] : null;

    $stmt = $conn->prepare("CALL sp_reporte_ventas(?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiii", $fecha_inicio, $fecha_fin, $id_alumno, $id_curso, $id_forma_pago);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $reporte_data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Calcular total
        foreach ($reporte_data as $row) {
            $total_ventas += $row['monto_pagado'];
        }
    } else {
        $error = "Error al generar el reporte: " . $stmt->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Reporte de Ventas</h2>

    <div class="card mb-4">
        <div class="card-header">Filtros del Reporte</div>
        <div class="card-body">
            <form action="reportes.php" method="get">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="fecha_inicio">Fecha Desde</label>
                        <input type="date" class="form-control" name="fecha_inicio" value="<?= htmlspecialchars($_GET['fecha_inicio'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="fecha_fin">Fecha Hasta</label>
                        <input type="date" class="form-control" name="fecha_fin" value="<?= htmlspecialchars($_GET['fecha_fin'] ?? '') ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="id_alumno">Alumno (Opcional)</label>
                        <select name="id_alumno" class="form-control">
                            <option value="">Todos los Alumnos</option>
                            <?php foreach ($alumnos as $a): ?>
                                <option value="<?= $a['id_alumno'] ?>" <?= (($_GET['id_alumno'] ?? '') == $a['id_alumno']) ? 'selected' : '' ?>><?= htmlspecialchars($a['nombres'] . ' ' . $a['apellidos']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="id_curso">Curso (Opcional)</label>
                        <select name="id_curso" class="form-control">
                            <option value="">Todos los Cursos</option>
                             <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id_curso'] ?>" <?= (($_GET['id_curso'] ?? '') == $c['id_curso']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre_curso']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="id_forma_pago">Forma de Pago (Opcional)</label>
                        <select name="id_forma_pago" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($formas_pago as $fp): ?>
                                <option value="<?= $fp['id_forma_pago'] ?>" <?= (($_GET['id_forma_pago'] ?? '') == $fp['id_forma_pago']) ? 'selected' : '' ?>><?= htmlspecialchars($fp['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="generate_report" class="btn btn-primary">Generar Reporte</button>
                 <a href="reportes.php" class="btn btn-secondary">Limpiar Filtros</a>
            </form>
        </div>
    </div>

    <?php if (isset($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <?php if ($is_report_generated): ?>
    <div class="card">
        <div class="card-header">Resultados del Reporte</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID Matrícula</th>
                            <th>Fecha</th>
                            <th>Alumno</th>
                            <th>Curso</th>
                            <th>Forma de Pago</th>
                            <th class="text-right">Monto (S/.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reporte_data)): ?>
                            <?php foreach ($reporte_data as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id_matricula']) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['fecha_matricula']))) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_alumno']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_curso']) ?></td>
                                    <td><?= htmlspecialchars($row['forma_pago']) ?></td>
                                    <td class="text-right"><?= htmlspecialchars(number_format($row['monto_pagado'], 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No se encontraron resultados para los filtros seleccionados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="5" class="text-right">Total:</td>
                            <td class="text-right">S/. <?= htmlspecialchars(number_format($total_ventas, 2)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
