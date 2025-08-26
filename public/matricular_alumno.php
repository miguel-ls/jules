<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

$conn = getDBConnection();
$message = '';
$error = '';

// --- LÓGICA DE CONTROLADOR (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recopilar datos del formulario
    $id_alumno = (int)$_POST['id_alumno'];
    $id_horario_clase = (int)$_POST['id_horario_clase'];
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $monto_pagado = (float)$_POST['monto_pagado'];
    $id_forma_pago = (int)$_POST['id_forma_pago'];
    $observaciones = trim($_POST['observaciones']);
    $id_usuario_registro = $_SESSION['id_usuario'];

    // Validación básica
    if (empty($id_alumno) || empty($id_horario_clase) || empty($fecha_inicio) || empty($fecha_fin) || empty($id_forma_pago)) {
        $error = "Faltan datos clave. Por favor, complete todos los pasos.";
    } else {
        // --- Lógica para generar días de clase ---
        // 1. Obtener el tipo de horario (L-M-V, M-J-S, etc.)
        $stmt_th = $conn->prepare("SELECT th.descripcion FROM horarios_clase hc JOIN tipos_horario th ON hc.id_tipo_horario = th.id_tipo_horario WHERE hc.id_horario_clase = ?");
        $stmt_th->bind_param("i", $id_horario_clase);
        $stmt_th->execute();
        $tipo_horario_desc = $stmt_th->get_result()->fetch_assoc()['descripcion'] ?? '';
        $stmt_th->close();
        while($conn->more_results() && $conn->next_result()) {}

        // 2. Mapear descripción a días de la semana (1=Lunes, 7=Domingo)
        $dias_map = ['L' => 1, 'M' => 2, 'X' => 3, 'J' => 4, 'V' => 5, 'S' => 6, 'D' => 7];
        $dias_activos = [];
        foreach ($dias_map as $char => $num_dia) {
            if (stripos($tipo_horario_desc, $char) !== false) {
                $dias_activos[] = $num_dia;
            }
        }

        // 3. Iterar en el rango de fechas y generar las clases
        $fechas_clase = [];
        if (!empty($dias_activos)) {
            $periodo = new DatePeriod(
                 new DateTime($fecha_inicio),
                 new DateInterval('P1D'),
                 (new DateTime($fecha_fin))->modify('+1 day') // Incluir el último día
            );
            foreach ($periodo as $fecha) {
                if (in_array((int)$fecha->format('N'), $dias_activos)) {
                    $fechas_clase[] = $fecha->format('Y-m-d');
                }
            }
        }

        // --- Transacción de Base de Datos ---
        $conn->begin_transaction();
        try {
            // 1. Crear la matrícula
            $stmt_mat = $conn->prepare("CALL sp_matricula_crear(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_mat->bind_param("iissdisi", $id_alumno, $id_horario_clase, $fecha_inicio, $fecha_fin, $monto_pagado, $id_forma_pago, $id_usuario_registro, $observaciones);
            $stmt_mat->execute();
            $result_mat = $stmt_mat->get_result();
            $id_matricula_nueva = $result_mat->fetch_assoc()['id_matricula'];
            $stmt_mat->close();
            while($conn->more_results() && $conn->next_result()) {}

            if (!$id_matricula_nueva) {
                throw new Exception("No se pudo crear la matrícula.");
            }

            // 2. Insertar los días de clase
            foreach ($fechas_clase as $fecha) {
                $stmt_dias = $conn->prepare("CALL sp_dia_clase_insertar(?, ?)");
                $stmt_dias->bind_param("is", $id_matricula_nueva, $fecha);
                if(!$stmt_dias->execute()){
                    throw new Exception("Error al insertar día de clase: " . $stmt_dias->error);
                }
                $stmt_dias->close();
                while($conn->more_results() && $conn->next_result()) {}
            }

            // 3. Si todo fue bien, confirmar la transacción
            $conn->commit();
            $message = "¡Matrícula realizada con éxito! Se generaron " . count($fechas_clase) . " días de clase.";

        } catch (Exception $e) {
            // 4. Si algo falló, revertir
            $conn->rollback();
            $error = "Error en la transacción: " . $e->getMessage();
        }
    }
}


// --- LÓGICA DE VISTA: Cargar todos los datos necesarios para los dropdowns y la tabla ---

function fetchData($conn, $sp_name) {
    $data = [];
    // Limpiar resultados anteriores antes de una nueva llamada a SP
    while($conn->more_results() && $conn->next_result()) {}
    if ($result = $conn->query("CALL $sp_name()")) {
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
    }
    return $data;
}

$alumnos = fetchData($conn, 'sp_alumno_leer_todos');
$horarios_clase_raw = fetchData($conn, 'sp_horario_clase_leer_todos');
$formas_pago = fetchData($conn, 'sp_forma_pago_leer_todas');

// Procesar horarios para incluir vacantes
$horarios_clase = [];
foreach ($horarios_clase_raw as $horario) {
    // Necesitamos obtener la capacidad del carril
    $stmt_carril = $conn->prepare("
        SELECT cr.capacidad_maxima
        FROM horarios_clase hc
        JOIN carriles cr ON hc.id_carril = cr.id_carril
        WHERE hc.id_horario_clase = ?
    ");
    $stmt_carril->bind_param("i", $horario['id_horario_clase']);
    $stmt_carril->execute();
    $result_carril = $stmt_carril->get_result();
    $capacidad = $result_carril->fetch_assoc()['capacidad_maxima'] ?? 0;
    $stmt_carril->close();
    while($conn->more_results() && $conn->next_result()) {}

    // Contar matriculados
    $stmt_matriculados = $conn->prepare("CALL sp_horario_clase_contar_matriculados(?)");
    $stmt_matriculados->bind_param("i", $horario['id_horario_clase']);
    $stmt_matriculados->execute();
    $result_matriculados = $stmt_matriculados->get_result();
    $matriculados = $result_matriculados->fetch_assoc()['matriculados'] ?? 0;
    $stmt_matriculados->close();
    while($conn->more_results() && $conn->next_result()) {}

    $horario['capacidad'] = $capacidad;
    $horario['matriculados'] = $matriculados;
    $horario['vacantes'] = $capacidad - $matriculados;

    $horarios_clase[] = $horario;
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Matrícula</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .table-responsive { max-height: 400px; }
        .table thead { position: sticky; top: 0; background: white; }
        .vacantes-disponibles { color: green; font-weight: bold; }
        .vacantes-pocas { color: orange; font-weight: bold; }
        .vacantes-ninguna { color: red; font-weight: bold; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>

<div class="container mt-4">
    <h2>Nueva Matrícula</h2>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form action="matricular_alumno.php" method="post" id="matriculaForm">
        <div class="card mb-4">
            <div class="card-header">Paso 1: Seleccionar Alumno</div>
            <div class="card-body">
                 <div class="form-group">
                    <label for="id_alumno">Alumno</label>
                    <select name="id_alumno" id="id_alumno" class="form-control" required>
                        <option value="">-- Seleccione un alumno --</option>
                        <?php foreach ($alumnos as $alumno): ?>
                            <option value="<?= $alumno['id_alumno'] ?>">
                                <?= htmlspecialchars($alumno['nombres'] . ' ' . $alumno['apellidos']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Paso 2: Seleccionar Clase</div>
            <div class="card-body">
                <p>Seleccione una de las clases disponibles haciendo clic en la fila correspondiente.</p>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Profesor</th>
                                <th>Piscina (Carril)</th>
                                <th>Días</th>
                                <th>Horario</th>
                                <th>Vacantes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($horarios_clase as $hc): ?>
                                <?php
                                    $vacantes = $hc['vacantes'];
                                    $class = 'vacantes-disponibles';
                                    if ($vacantes <= 0) $class = 'vacantes-ninguna';
                                    elseif ($vacantes <= 3) $class = 'vacantes-pocas';
                                ?>
                                <tr data-id-horario="<?= $hc['id_horario_clase'] ?>" class="<?= $vacantes > 0 ? 'selectable-row' : 'disabled' ?>">
                                    <td>
                                        <input type="radio" name="id_horario_clase" value="<?= $hc['id_horario_clase'] ?>" required <?= $vacantes <= 0 ? 'disabled' : '' ?>>
                                        <?= htmlspecialchars($hc['nombre_curso']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($hc['nombre_profesor']) ?></td>
                                    <td><?= htmlspecialchars($hc['nombre_piscina']) ?> (<?= htmlspecialchars($hc['numero_carril']) ?>)</td>
                                    <td><?= htmlspecialchars($hc['nombre_tipo_horario']) ?></td>
                                    <td><?= date('g:i A', strtotime($hc['hora_inicio'])) ?> - <?= date('g:i A', strtotime($hc['hora_fin'])) ?></td>
                                    <td class="<?= $class ?>"><?= $vacantes ?> de <?= $hc['capacidad'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Paso 3: Periodo y Pago</div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="fecha_inicio">Fecha de Inicio</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="fecha_fin">Fecha de Fin</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required>
                    </div>
                </div>
                 <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="monto_pagado">Monto Pagado (S/.)</label>
                        <input type="number" step="0.01" name="monto_pagado" id="monto_pagado" class="form-control" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="id_forma_pago">Forma de Pago</label>
                        <select name="id_forma_pago" id="id_forma_pago" class="form-control" required>
                             <?php foreach ($formas_pago as $fp): ?>
                                <option value="<?= $fp['id_forma_pago'] ?>"><?= htmlspecialchars($fp['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                 <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">Realizar Matrícula</button>
        </div>
    </form>
</div>

<script>
// Script para hacer que toda la fila de la tabla sea clickeable para seleccionar el radio button.
document.querySelectorAll('.selectable-row').forEach(row => {
    row.addEventListener('click', function() {
        this.querySelector('input[type="radio"]').checked = true;
    });
});
</script>

</body>
</html>
