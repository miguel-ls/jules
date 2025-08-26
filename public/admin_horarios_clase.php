<?php
require_once __DIR__ . '/../src/Core/auth_check.php';
if ($_SESSION['rol'] !== 'Administrador') {
    header("Location: dashboard.php");
    exit('Acceso denegado.');
}

$conn = getDBConnection();
$message = '';
$error = '';
$edit_mode = false;
$horario_data = [
    'id_horario_clase' => '', 'id_curso' => '', 'id_carril' => '', 'id_profesor' => '',
    'id_tipo_horario' => '', 'hora_inicio' => '', 'hora_fin' => '', 'estado' => 'Activo'
];

// --- LÓGICA DE CONTROLADOR ---

// Manejo de acciones POST (Crear/Actualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = !empty($_POST['id_horario_clase']) ? (int)$_POST['id_horario_clase'] : null;
    $id_curso = (int)$_POST['id_curso'];
    $id_carril = (int)$_POST['id_carril'];
    $id_profesor = (int)$_POST['id_profesor'];
    $id_tipo_horario = (int)$_POST['id_tipo_horario'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $estado = $_POST['estado'] ?? 'Activo';

    if (empty($id_curso) || empty($id_carril) || empty($id_profesor) || empty($id_tipo_horario) || empty($hora_inicio) || empty($hora_fin)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        if ($id) { // Actualizar
            $stmt = $conn->prepare("CALL sp_horario_clase_actualizar(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiisss", $id, $id_curso, $id_carril, $id_profesor, $id_tipo_horario, $hora_inicio, $hora_fin, $estado);
        } else { // Crear
            $stmt = $conn->prepare("CALL sp_horario_clase_crear(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiss", $id_curso, $id_carril, $id_profesor, $id_tipo_horario, $hora_inicio, $hora_fin);
        }
        if ($stmt->execute()) {
            $message = $id ? "Horario actualizado." : "Horario creado.";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
        while($conn->more_results() && $conn->next_result()) {}
    }
}

// Manejo de acciones GET (Eliminar/Editar)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($action == 'delete' && $id > 0) {
        $stmt = $conn->prepare("CALL sp_horario_clase_eliminar(?)");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "Horario eliminado.";
        } else {
            $error = "Error al eliminar: " . $stmt->error;
        }
        $stmt->close();
        while($conn->more_results() && $conn->next_result()) {}
    }

    if ($action == 'edit' && $id > 0) {
        $edit_mode = true;
        $stmt = $conn->prepare("CALL sp_horario_clase_leer_por_id(?)");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $horario_data = $result->fetch_assoc();
        }
        $stmt->close();
        while($conn->more_results() && $conn->next_result()) {}
    }
}

// --- LÓGICA DE VISTA ---
function fetchData($conn, $sp_name) {
    $data = [];
    if ($result = $conn->query("CALL $sp_name()")) {
        $data = $result->fetch_all(MYSQLI_ASSOC);
        while($conn->more_results() && $conn->next_result()) {}
    }
    return $data;
}

$cursos = fetchData($conn, 'sp_curso_leer_todos');
$profesores = fetchData($conn, 'sp_profesor_leer_todos');
$tipos_horario = fetchData($conn, 'sp_tipo_horario_leer_todos');
$piscinas = fetchData($conn, 'sp_piscina_leer_todas');
$horarios_clase = fetchData($conn, 'sp_horario_clase_leer_todos');

// Cargar todos los carriles y agruparlos por piscina para JS
$carriles_por_piscina = [];
$todos_carriles = $conn->query("SELECT id_carril, id_piscina, numero_carril FROM carriles ORDER BY numero_carril");
if($todos_carriles) {
    while($carril = $todos_carriles->fetch_assoc()){
        $carriles_por_piscina[$carril['id_piscina']][] = $carril;
    }
    $todos_carriles->close();
}
while($conn->more_results() && $conn->next_result()) {}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mantenimiento de Horarios de Clase</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../src/Views/partials/navbar.php'; ?>
<div class="container mt-4">
    <h2>Mantenimiento de Horarios de Clase</h2>
    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><?= $edit_mode ? 'Editar Horario' : 'Añadir Nuevo Horario' ?></div>
        <div class="card-body">
            <form action="admin_horarios_clase.php" method="post">
                <input type="hidden" name="id_horario_clase" value="<?= htmlspecialchars($horario_data['id_horario_clase']) ?>">
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Curso</label><select name="id_curso" class="form-control"><?php foreach($cursos as $c) echo "<option value='{$c['id_curso']}' ".($horario_data['id_curso']==$c['id_curso']?'selected':'').">{$c['nombre_curso']}</option>"; ?></select></div>
                    <div class="form-group col-md-6"><label>Profesor</label><select name="id_profesor" class="form-control"><?php foreach($profesores as $p) echo "<option value='{$p['id_profesor']}' ".($horario_data['id_profesor']==$p['id_profesor']?'selected':'').">{$p['nombres']} {$p['apellidos']}</option>"; ?></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Piscina</label><select id="piscina_select" class="form-control"><?php foreach($piscinas as $p) echo "<option value='{$p['id_piscina']}'>{$p['nombre']}</option>"; ?></select></div>
                    <div class="form-group col-md-4"><label>Carril</label><select id="carril_select" name="id_carril" class="form-control"></select></div>
                    <div class="form-group col-md-4"><label>Tipo de Horario</label><select name="id_tipo_horario" class="form-control"><?php foreach($tipos_horario as $th) echo "<option value='{$th['id_tipo_horario']}' ".($horario_data['id_tipo_horario']==$th['id_tipo_horario']?'selected':'').">{$th['nombre']}</option>"; ?></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Hora Inicio</label><input type="time" name="hora_inicio" class="form-control" value="<?= htmlspecialchars($horario_data['hora_inicio']) ?>"></div>
                    <div class="form-group col-md-4"><label>Hora Fin</label><input type="time" name="hora_fin" class="form-control" value="<?= htmlspecialchars($horario_data['hora_fin']) ?>"></div>
                    <?php if($edit_mode): ?><div class="form-group col-md-4"><label>Estado</label><select name="estado" class="form-control"><option value="Activo" <?= $horario_data['estado']=='Activo'?'selected':'' ?>>Activo</option><option value="Inactivo" <?= $horario_data['estado']=='Inactivo'?'selected':'' ?>>Inactivo</option><option value="Lleno" <?= $horario_data['estado']=='Lleno'?'selected':'' ?>>Lleno</option></select></div><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Actualizar' : 'Guardar' ?></button>
                <?php if ($edit_mode): ?><a href="admin_horarios_clase.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Listado de Horarios</div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead><tr><th>Curso</th><th>Piscina</th><th>Carril</th><th>Profesor</th><th>Horario</th><th>Horas</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php foreach($horarios_clase as $hc): ?>
                    <tr>
                        <td><?= htmlspecialchars($hc['nombre_curso']) ?></td>
                        <td><?= htmlspecialchars($hc['nombre_piscina']) ?></td>
                        <td><?= htmlspecialchars($hc['numero_carril']) ?></td>
                        <td><?= htmlspecialchars($hc['nombre_profesor']) ?></td>
                        <td><?= htmlspecialchars($hc['nombre_tipo_horario']) ?></td>
                        <td><?= date('g:i A', strtotime($hc['hora_inicio'])) ?> - <?= date('g:i A', strtotime($hc['hora_fin'])) ?></td>
                        <td><span class="badge badge-<?= $hc['estado']=='Activo'?'success':'secondary' ?>"><?= $hc['estado'] ?></span></td>
                        <td>
                            <a href="?action=edit&id=<?= $hc['id_horario_clase'] ?>" class="btn btn-sm btn-warning">E</a>
                            <a href="?action=delete&id=<?= $hc['id_horario_clase'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Seguro?')">X</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const carrilesData = <?= json_encode($carriles_por_piscina) ?>;
    const piscinaSelect = document.getElementById('piscina_select');
    const carrilSelect = document.getElementById('carril_select');
    const selectedCarril = '<?= $horario_data['id_carril'] ?>';

    function updateCarrilOptions() {
        const selectedPiscinaId = piscinaSelect.value;
        carrilSelect.innerHTML = '<option value="">Seleccione un carril</option>';
        if (carrilesData[selectedPiscinaId]) {
            carrilesData[selectedPiscinaId].forEach(carril => {
                const option = document.createElement('option');
                option.value = carril.id_carril;
                option.textContent = `Carril #${carril.numero_carril}`;
                if (carril.id_carril === selectedCarril) {
                    option.selected = true;
                }
                carrilSelect.appendChild(option);
            });
        }
    }

    piscinaSelect.addEventListener('change', updateCarrilOptions);

    // Si estamos en modo edición, pre-seleccionar la piscina correcta
    <?php if($edit_mode && !empty($horario_data['id_carril'])): ?>
    const carrilInfo = <?= json_encode($carriles_por_piscina) ?>;
    let piscinaIdForSelectedCarril = null;
    for(const piscinaId in carrilInfo) {
        if(carrilInfo[piscinaId].find(c => c.id_carril == selectedCarril)) {
            piscinaIdForSelectedCarril = piscinaId;
            break;
        }
    }
    if(piscinaIdForSelectedCarril) {
        piscinaSelect.value = piscinaIdForSelectedCarril;
    }
    <?php endif; ?>

    // Cargar carriles al inicio
    updateCarrilOptions();
});
</script>
</body>
</html>
