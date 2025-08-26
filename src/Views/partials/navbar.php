<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="dashboard.php">Admin Natación</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">Inicio</a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Configuración
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <a class="dropdown-item" href="admin_usuarios.php">Usuarios</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="admin_tipos_piscina.php">Tipos de Piscina</a>
                    <a class="dropdown-item" href="admin_formas_pago.php">Formas de Pago</a>
                    <a class="dropdown-item" href="admin_profesores.php">Profesores</a>
                    <a class="dropdown-item" href="admin_cursos.php">Cursos</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="admin_alumnos.php">Alumnos</a>
                    <a class="dropdown-item" href="admin_piscinas.php">Piscinas y Carriles</a>
                    <a class="dropdown-item" href="admin_tipos_horario.php">Tipos de Horario</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="admin_horarios_clase.php"><strong>Programar Clases</strong></a>
                    <a class="dropdown-item" href="admin_asistencia_profesor.php">Asistencia de Profesores</a>
                </div>
            </li>
             <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="matriculaDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <strong>Matrículas</strong>
                </a>
                <div class="dropdown-menu" aria-labelledby="matriculaDropdown">
                    <a class="dropdown-item" href="matricular_alumno.php">Nueva Matrícula</a>
                    <a class="dropdown-item" href="admin_matriculas.php">Gestionar Matrículas</a>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reportes.php">Reportes</a>
            </li>
        </ul>
        <ul class="navbar-nav">
            <li class="nav-item">
                <span class="navbar-text mr-3">
                    Usuario: <?= htmlspecialchars($_SESSION['nombre_usuario']); ?>
                </span>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="btn btn-danger btn-sm">Cerrar Sesión</a>
            </li>
        </ul>
    </div>
</nav>

<!-- Requerir scripts de Bootstrap JS para el dropdown -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<style>
    .navbar { background-color: #333 !important; }
</style>
