<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

$conn = getDBConnection();
$matriculas = [];

// Lógica de filtrado (a implementar en un SP más avanzado o en PHP)
// Por ahora, simplemente leemos todas.
$query = "CALL sp_matricula_leer_todas()";

if ($result = $conn->query($query)) {
    if ($result->num_rows > 0) {
        $matriculas = $result->fetch_all(MYSQLI_ASSOC);
    }
    while($conn->more_results() && $conn->next_result()) {}
} else {
    $error = "Error al cargar la lista de matrículas: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Matrículas</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Gestión de Matrículas</h2>
        <a href="matricular_alumno.php" class="btn btn-primary">Nueva Matrícula</a>
    </div>

    <?php if (isset($error)): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mt-3">
        <div class="card-header">Filtros (Próximamente)</div>
        <div class="card-body">
            <!-- Aquí irían los campos de filtro -->
            <p class="text-muted">Filtros por alumno, curso o estado se añadirán aquí.</p>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Listado de Matrículas</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Alumno</th>
                            <th>Curso</th>
                            <th>Periodo</th>
                            <th>Monto Pagado</th>
                            <th>Forma de Pago</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($matriculas)): ?>
                            <?php foreach ($matriculas as $matricula): ?>
                                <?php
                                    $estado_class = '';
                                    switch ($matricula['estado']) {
                                        case 'Vigente': $estado_class = 'badge-success'; break;
                                        case 'Finalizada': $estado_class = 'badge-secondary'; break;
                                        case 'Anulada': $estado_class = 'badge-danger'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($matricula['id_matricula']) ?></td>
                                    <td><?= htmlspecialchars($matricula['nombre_alumno']) ?></td>
                                    <td><?= htmlspecialchars($matricula['nombre_curso']) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($matricula['fecha_inicio']))) ?> - <?= htmlspecialchars(date('d/m/Y', strtotime($matricula['fecha_fin']))) ?></td>
                                    <td>S/. <?= htmlspecialchars(number_format($matricula['monto_pagado'], 2)) ?></td>
                                    <td><?= htmlspecialchars($matricula['forma_pago']) ?></td>
                                    <td><span class="badge <?= $estado_class ?>"><?= htmlspecialchars($matricula['estado']) ?></span></td>
                                    <td>
                                        <a href="admin_matricula_detalle.php?id=<?= $matricula['id_matricula'] ?>" class="btn btn-sm btn-info">Ver Detalles</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No hay matrículas registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
