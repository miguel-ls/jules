-- Procedimientos Almacenados para el Módulo de Usuarios

DELIMITER $$

-- *****************************************************************
-- ** USUARIOS
-- *****************************************************************

-- Crear un nuevo usuario
CREATE PROCEDURE `sp_usuario_crear`(
    IN p_nombre_usuario VARCHAR(50),
    IN p_password_hash VARCHAR(255),
    IN p_email VARCHAR(100),
    IN p_nombre_completo VARCHAR(150),
    IN p_rol ENUM('Administrador', 'Asistente')
)
BEGIN
    INSERT INTO usuarios (nombre_usuario, password_hash, email, nombre_completo, rol)
    VALUES (p_nombre_usuario, p_password_hash, p_email, p_nombre_completo, p_rol);
    SELECT LAST_INSERT_ID() AS id_usuario;
END$$

-- Leer todos los usuarios
CREATE PROCEDURE `sp_usuario_leer_todos`()
BEGIN
    SELECT id_usuario, nombre_usuario, email, nombre_completo, rol, created_at
    FROM usuarios;
END$$

-- Leer un usuario por su ID
CREATE PROCEDURE `sp_usuario_leer_por_id`(
    IN p_id_usuario INT
)
BEGIN
    SELECT id_usuario, nombre_usuario, email, nombre_completo, rol, created_at
    FROM usuarios
    WHERE id_usuario = p_id_usuario;
END$$

-- Leer un usuario por su nombre de usuario (para login)
CREATE PROCEDURE `sp_usuario_leer_por_nombre_usuario`(
    IN p_nombre_usuario VARCHAR(50)
)
BEGIN
    SELECT *
    FROM usuarios
    WHERE nombre_usuario = p_nombre_usuario;
END$$

-- Actualizar un usuario
CREATE PROCEDURE `sp_usuario_actualizar`(
    IN p_id_usuario INT,
    IN p_nombre_completo VARCHAR(150),
    IN p_email VARCHAR(100),
    IN p_rol ENUM('Administrador', 'Asistente')
)
BEGIN
    UPDATE usuarios
    SET
        nombre_completo = p_nombre_completo,
        email = p_email,
        rol = p_rol
    WHERE id_usuario = p_id_usuario;
END$$

-- Eliminar un usuario
CREATE PROCEDURE `sp_usuario_eliminar`(
    IN p_id_usuario INT
)
BEGIN
    DELETE FROM usuarios WHERE id_usuario = p_id_usuario;
END$$

-- Actualizar la contraseña de un usuario
CREATE PROCEDURE `sp_usuario_actualizar_password`(
    IN p_id_usuario INT,
    IN p_password_hash VARCHAR(255)
)
BEGIN
    UPDATE usuarios
    SET password_hash = p_password_hash
    WHERE id_usuario = p_id_usuario;
END$$

-- Guardar el código de autenticación de dos factores (2FA)
CREATE PROCEDURE `sp_usuario_guardar_codigo_2fa`(
    IN p_id_usuario INT,
    IN p_auth_code VARCHAR(10),
    IN p_expiry_datetime DATETIME
)
BEGIN
    UPDATE usuarios
    SET
        auth_code_2fa = p_auth_code,
        auth_code_2fa_expiry = p_expiry_datetime
    WHERE id_usuario = p_id_usuario;
END$$

-- *****************************************************************
-- ** TIPOS DE PISCINA
-- *****************************************************************

-- Crear un nuevo tipo de piscina
CREATE PROCEDURE `sp_tipo_piscina_crear`(
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT
)
BEGIN
    INSERT INTO tipos_piscina (nombre, descripcion)
    VALUES (p_nombre, p_descripcion);
    SELECT LAST_INSERT_ID() AS id_tipo_piscina;
END$$

-- Leer todos los tipos de piscina
CREATE PROCEDURE `sp_tipo_piscina_leer_todos`()
BEGIN
    SELECT * FROM tipos_piscina ORDER BY nombre;
END$$

-- Leer un tipo de piscina por ID
CREATE PROCEDURE `sp_tipo_piscina_leer_por_id`(
    IN p_id_tipo_piscina INT
)
BEGIN
    SELECT * FROM tipos_piscina WHERE id_tipo_piscina = p_id_tipo_piscina;
END$$

-- Actualizar un tipo de piscina
CREATE PROCEDURE `sp_tipo_piscina_actualizar`(
    IN p_id_tipo_piscina INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion TEXT
)
BEGIN
    UPDATE tipos_piscina
    SET
        nombre = p_nombre,
        descripcion = p_descripcion
    WHERE id_tipo_piscina = p_id_tipo_piscina;
END$$

-- Eliminar un tipo de piscina
CREATE PROCEDURE `sp_tipo_piscina_eliminar`(
    IN p_id_tipo_piscina INT
)
BEGIN
    -- Se podría añadir lógica para verificar si el tipo de piscina está en uso antes de eliminar.
    -- Por ahora, se elimina directamente.
    DELETE FROM tipos_piscina WHERE id_tipo_piscina = p_id_tipo_piscina;
END$$

-- *****************************************************************
-- ** FORMAS DE PAGO
-- *****************************************************************

-- Crear una nueva forma de pago
CREATE PROCEDURE `sp_forma_pago_crear`(
    IN p_nombre VARCHAR(100)
)
BEGIN
    INSERT INTO formas_pago (nombre) VALUES (p_nombre);
    SELECT LAST_INSERT_ID() AS id_forma_pago;
END$$

-- Leer todas las formas de pago
CREATE PROCEDURE `sp_forma_pago_leer_todas`()
BEGIN
    SELECT * FROM formas_pago ORDER BY nombre;
END$$

-- Leer una forma de pago por ID
CREATE PROCEDURE `sp_forma_pago_leer_por_id`(
    IN p_id_forma_pago INT
)
BEGIN
    SELECT * FROM formas_pago WHERE id_forma_pago = p_id_forma_pago;
END$$

-- Actualizar una forma de pago
CREATE PROCEDURE `sp_forma_pago_actualizar`(
    IN p_id_forma_pago INT,
    IN p_nombre VARCHAR(100)
)
BEGIN
    UPDATE formas_pago SET nombre = p_nombre WHERE id_forma_pago = p_id_forma_pago;
END$$

-- Eliminar una forma de pago
CREATE PROCEDURE `sp_forma_pago_eliminar`(
    IN p_id_forma_pago INT
)
BEGIN
    DELETE FROM formas_pago WHERE id_forma_pago = p_id_forma_pago;
END$$

-- *****************************************************************
-- ** PROFESORES
-- *****************************************************************

-- Crear un nuevo profesor
CREATE PROCEDURE `sp_profesor_crear`(
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_documento_identidad VARCHAR(20),
    IN p_fecha_nacimiento DATE,
    IN p_telefono VARCHAR(20),
    IN p_email VARCHAR(100),
    IN p_fecha_contratacion DATE
)
BEGIN
    INSERT INTO profesores (nombres, apellidos, documento_identidad, fecha_nacimiento, telefono, email, fecha_contratacion, estado)
    VALUES (p_nombres, p_apellidos, p_documento_identidad, p_fecha_nacimiento, p_telefono, p_email, p_fecha_contratacion, 'Activo');
END$$

-- Leer todos los profesores
CREATE PROCEDURE `sp_profesor_leer_todos`()
BEGIN
    SELECT * FROM profesores ORDER BY apellidos, nombres;
END$$

-- Leer un profesor por ID
CREATE PROCEDURE `sp_profesor_leer_por_id`(
    IN p_id_profesor INT
)
BEGIN
    SELECT * FROM profesores WHERE id_profesor = p_id_profesor;
END$$

-- Actualizar un profesor
CREATE PROCEDURE `sp_profesor_actualizar`(
    IN p_id_profesor INT,
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_documento_identidad VARCHAR(20),
    IN p_fecha_nacimiento DATE,
    IN p_telefono VARCHAR(20),
    IN p_email VARCHAR(100),
    IN p_fecha_contratacion DATE,
    IN p_estado ENUM('Activo', 'Inactivo')
)
BEGIN
    UPDATE profesores
    SET
        nombres = p_nombres,
        apellidos = p_apellidos,
        documento_identidad = p_documento_identidad,
        fecha_nacimiento = p_fecha_nacimiento,
        telefono = p_telefono,
        email = p_email,
        fecha_contratacion = p_fecha_contratacion,
        estado = p_estado
    WHERE id_profesor = p_id_profesor;
END$$

-- Eliminar un profesor (lógica de borrado suave podría ser mejor)
CREATE PROCEDURE `sp_profesor_eliminar`(
    IN p_id_profesor INT
)
BEGIN
    -- Idealmente, en lugar de borrar, se cambiaría el estado a 'Inactivo'.
    -- Por ahora, se elimina para seguir el patrón.
    DELETE FROM profesores WHERE id_profesor = p_id_profesor;
END$$

-- *****************************************************************
-- ** CURSOS
-- *****************************************************************

-- Crear un nuevo curso
CREATE PROCEDURE `sp_curso_crear`(
    IN p_nombre_curso VARCHAR(150),
    IN p_descripcion TEXT,
    IN p_precio_base DECIMAL(10, 2)
)
BEGIN
    INSERT INTO cursos (nombre_curso, descripcion, precio_base)
    VALUES (p_nombre_curso, p_descripcion, p_precio_base);
END$$

-- Leer todos los cursos
CREATE PROCEDURE `sp_curso_leer_todos`()
BEGIN
    SELECT * FROM cursos ORDER BY nombre_curso;
END$$

-- Leer un curso por ID
CREATE PROCEDURE `sp_curso_leer_por_id`(
    IN p_id_curso INT
)
BEGIN
    SELECT * FROM cursos WHERE id_curso = p_id_curso;
END$$

-- Actualizar un curso
CREATE PROCEDURE `sp_curso_actualizar`(
    IN p_id_curso INT,
    IN p_nombre_curso VARCHAR(150),
    IN p_descripcion TEXT,
    IN p_precio_base DECIMAL(10, 2)
)
BEGIN
    UPDATE cursos
    SET
        nombre_curso = p_nombre_curso,
        descripcion = p_descripcion,
        precio_base = p_precio_base
    WHERE id_curso = p_id_curso;
END$$

-- Eliminar un curso
CREATE PROCEDURE `sp_curso_eliminar`(
    IN p_id_curso INT
)
BEGIN
    DELETE FROM cursos WHERE id_curso = p_id_curso;
END$$

-- *****************************************************************
-- ** ALUMNOS
-- *****************************************************************

-- Crear un nuevo alumno
CREATE PROCEDURE `sp_alumno_crear`(
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_documento_identidad VARCHAR(20),
    IN p_fecha_nacimiento DATE,
    IN p_grupo_sanguineo VARCHAR(5),
    IN p_direccion VARCHAR(255),
    IN p_email VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_nombre_padre_madre VARCHAR(200),
    IN p_contacto_emergencia_nombre VARCHAR(200),
    IN p_contacto_emergencia_telefono VARCHAR(20)
)
BEGIN
    INSERT INTO alumnos (nombres, apellidos, documento_identidad, fecha_nacimiento, grupo_sanguineo, direccion, email, telefono, nombre_padre_madre, contacto_emergencia_nombre, contacto_emergencia_telefono)
    VALUES (p_nombres, p_apellidos, p_documento_identidad, p_fecha_nacimiento, p_grupo_sanguineo, p_direccion, p_email, p_telefono, p_nombre_padre_madre, p_contacto_emergencia_nombre, p_contacto_emergencia_telefono);
END$$

-- Leer todos los alumnos
CREATE PROCEDURE `sp_alumno_leer_todos`()
BEGIN
    SELECT * FROM alumnos ORDER BY apellidos, nombres;
END$$

-- Leer un alumno por ID
CREATE PROCEDURE `sp_alumno_leer_por_id`(
    IN p_id_alumno INT
)
BEGIN
    SELECT * FROM alumnos WHERE id_alumno = p_id_alumno;
END$$

-- Actualizar un alumno
CREATE PROCEDURE `sp_alumno_actualizar`(
    IN p_id_alumno INT,
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_documento_identidad VARCHAR(20),
    IN p_fecha_nacimiento DATE,
    IN p_grupo_sanguineo VARCHAR(5),
    IN p_direccion VARCHAR(255),
    IN p_email VARCHAR(100),
    IN p_telefono VARCHAR(20),
    IN p_nombre_padre_madre VARCHAR(200),
    IN p_contacto_emergencia_nombre VARCHAR(200),
    IN p_contacto_emergencia_telefono VARCHAR(20)
)
BEGIN
    UPDATE alumnos
    SET
        nombres = p_nombres,
        apellidos = p_apellidos,
        documento_identidad = p_documento_identidad,
        fecha_nacimiento = p_fecha_nacimiento,
        grupo_sanguineo = p_grupo_sanguineo,
        direccion = p_direccion,
        email = p_email,
        telefono = p_telefono,
        nombre_padre_madre = p_nombre_padre_madre,
        contacto_emergencia_nombre = p_contacto_emergencia_nombre,
        contacto_emergencia_telefono = p_contacto_emergencia_telefono
    WHERE id_alumno = p_id_alumno;
END$$

-- Eliminar un alumno
CREATE PROCEDURE `sp_alumno_eliminar`(
    IN p_id_alumno INT
)
BEGIN
    -- Considerar borrado lógico si hay dependencias de matrícula
    DELETE FROM alumnos WHERE id_alumno = p_id_alumno;
END$$

-- *****************************************************************
-- ** PISCINAS
-- *****************************************************************

-- Crear una nueva piscina
CREATE PROCEDURE `sp_piscina_crear`(
    IN p_nombre VARCHAR(150),
    IN p_id_tipo_piscina INT,
    IN p_ubicacion VARCHAR(255)
)
BEGIN
    INSERT INTO piscinas (nombre, id_tipo_piscina, ubicacion)
    VALUES (p_nombre, p_id_tipo_piscina, p_ubicacion);
END$$

-- Leer todas las piscinas con el nombre de su tipo
CREATE PROCEDURE `sp_piscina_leer_todas`()
BEGIN
    SELECT
        p.id_piscina,
        p.nombre,
        p.id_tipo_piscina,
        tp.nombre AS nombre_tipo_piscina,
        p.ubicacion,
        p.created_at
    FROM piscinas p
    JOIN tipos_piscina tp ON p.id_tipo_piscina = tp.id_tipo_piscina
    ORDER BY p.nombre;
END$$

-- Leer una piscina por ID
CREATE PROCEDURE `sp_piscina_leer_por_id`(
    IN p_id_piscina INT
)
BEGIN
    SELECT * FROM piscinas WHERE id_piscina = p_id_piscina;
END$$

-- Actualizar una piscina
CREATE PROCEDURE `sp_piscina_actualizar`(
    IN p_id_piscina INT,
    IN p_nombre VARCHAR(150),
    IN p_id_tipo_piscina INT,
    IN p_ubicacion VARCHAR(255)
)
BEGIN
    UPDATE piscinas
    SET
        nombre = p_nombre,
        id_tipo_piscina = p_id_tipo_piscina,
        ubicacion = p_ubicacion
    WHERE id_piscina = p_id_piscina;
END$$

-- Eliminar una piscina
CREATE PROCEDURE `sp_piscina_eliminar`(
    IN p_id_piscina INT
)
BEGIN
    DELETE FROM piscinas WHERE id_piscina = p_id_piscina;
END$$

-- *****************************************************************
-- ** CARRILES
-- *****************************************************************

-- Crear un nuevo carril para una piscina
CREATE PROCEDURE `sp_carril_crear`(
    IN p_id_piscina INT,
    IN p_numero_carril INT,
    IN p_capacidad_maxima INT
)
BEGIN
    INSERT INTO carriles (id_piscina, numero_carril, capacidad_maxima)
    VALUES (p_id_piscina, p_numero_carril, p_capacidad_maxima);
END$$

-- Leer todos los carriles de una piscina específica
CREATE PROCEDURE `sp_carril_leer_por_piscina`(
    IN p_id_piscina INT
)
BEGIN
    SELECT * FROM carriles WHERE id_piscina = p_id_piscina ORDER BY numero_carril;
END$$

-- Leer un carril específico por su ID
CREATE PROCEDURE `sp_carril_leer_por_id`(
    IN p_id_carril INT
)
BEGIN
    SELECT * FROM carriles WHERE id_carril = p_id_carril;
END$$

-- Actualizar un carril
CREATE PROCEDURE `sp_carril_actualizar`(
    IN p_id_carril INT,
    IN p_numero_carril INT,
    IN p_capacidad_maxima INT
)
BEGIN
    UPDATE carriles
    SET
        numero_carril = p_numero_carril,
        capacidad_maxima = p_capacidad_maxima
    WHERE id_carril = p_id_carril;
END$$

-- Eliminar un carril
CREATE PROCEDURE `sp_carril_eliminar`(
    IN p_id_carril INT
)
BEGIN
    DELETE FROM carriles WHERE id_carril = p_id_carril;
END$$

-- *****************************************************************
-- ** TIPOS DE HORARIO
-- *****************************************************************

-- Crear un nuevo tipo de horario
CREATE PROCEDURE `sp_tipo_horario_crear`(
    IN p_nombre VARCHAR(100),
    IN p_descripcion VARCHAR(255)
)
BEGIN
    INSERT INTO tipos_horario (nombre, descripcion)
    VALUES (p_nombre, p_descripcion);
END$$

-- Leer todos los tipos de horario
CREATE PROCEDURE `sp_tipo_horario_leer_todos`()
BEGIN
    SELECT * FROM tipos_horario ORDER BY nombre;
END$$

-- Leer un tipo de horario por ID
CREATE PROCEDURE `sp_tipo_horario_leer_por_id`(
    IN p_id_tipo_horario INT
)
BEGIN
    SELECT * FROM tipos_horario WHERE id_tipo_horario = p_id_tipo_horario;
END$$

-- Actualizar un tipo de horario
CREATE PROCEDURE `sp_tipo_horario_actualizar`(
    IN p_id_tipo_horario INT,
    IN p_nombre VARCHAR(100),
    IN p_descripcion VARCHAR(255)
)
BEGIN
    UPDATE tipos_horario
    SET
        nombre = p_nombre,
        descripcion = p_descripcion
    WHERE id_tipo_horario = p_id_tipo_horario;
END$$

-- Eliminar un tipo de horario
CREATE PROCEDURE `sp_tipo_horario_eliminar`(
    IN p_id_tipo_horario INT
)
BEGIN
    DELETE FROM tipos_horario WHERE id_tipo_horario = p_id_tipo_horario;
END$$

-- *****************************************************************
-- ** HORARIOS DE CLASE
-- *****************************************************************

-- Crear un nuevo horario de clase
CREATE PROCEDURE `sp_horario_clase_crear`(
    IN p_id_curso INT,
    IN p_id_carril INT,
    IN p_id_profesor INT,
    IN p_id_tipo_horario INT,
    IN p_hora_inicio TIME,
    IN p_hora_fin TIME
)
BEGIN
    INSERT INTO horarios_clase (id_curso, id_carril, id_profesor, id_tipo_horario, hora_inicio, hora_fin, estado)
    VALUES (p_id_curso, p_id_carril, p_id_profesor, p_id_tipo_horario, p_hora_inicio, p_hora_fin, 'Activo');
END$$

-- Leer todos los horarios de clase con información detallada
CREATE PROCEDURE `sp_horario_clase_leer_todos`()
BEGIN
    SELECT
        hc.id_horario_clase,
        c.nombre_curso,
        p.nombre AS nombre_piscina,
        cr.numero_carril,
        CONCAT(pr.nombres, ' ', pr.apellidos) AS nombre_profesor,
        th.nombre AS nombre_tipo_horario,
        hc.hora_inicio,
        hc.hora_fin,
        hc.estado
    FROM horarios_clase hc
    JOIN cursos c ON hc.id_curso = c.id_curso
    JOIN carriles cr ON hc.id_carril = cr.id_carril
    JOIN piscinas p ON cr.id_piscina = p.id_piscina
    JOIN profesores pr ON hc.id_profesor = pr.id_profesor
    JOIN tipos_horario th ON hc.id_tipo_horario = th.id_tipo_horario
    ORDER BY c.nombre_curso, hc.hora_inicio;
END$$

-- Leer un horario de clase por ID
CREATE PROCEDURE `sp_horario_clase_leer_por_id`(
    IN p_id_horario_clase INT
)
BEGIN
    SELECT * FROM horarios_clase WHERE id_horario_clase = p_id_horario_clase;
END$$

-- Actualizar un horario de clase
CREATE PROCEDURE `sp_horario_clase_actualizar`(
    IN p_id_horario_clase INT,
    IN p_id_curso INT,
    IN p_id_carril INT,
    IN p_id_profesor INT,
    IN p_id_tipo_horario INT,
    IN p_hora_inicio TIME,
    IN p_hora_fin TIME,
    IN p_estado ENUM('Activo', 'Inactivo', 'Lleno')
)
BEGIN
    UPDATE horarios_clase
    SET
        id_curso = p_id_curso,
        id_carril = p_id_carril,
        id_profesor = p_id_profesor,
        id_tipo_horario = p_id_tipo_horario,
        hora_inicio = p_hora_inicio,
        hora_fin = p_hora_fin,
        estado = p_estado
    WHERE id_horario_clase = p_id_horario_clase;
END$$

-- Eliminar un horario de clase
CREATE PROCEDURE `sp_horario_clase_eliminar`(
    IN p_id_horario_clase INT
)
BEGIN
    DELETE FROM horarios_clase WHERE id_horario_clase = p_id_horario_clase;
END$$

-- *****************************************************************
-- ** MATRICULAS Y PROCESOS RELACIONADOS
-- *****************************************************************

-- Contar los matriculados actuales en un horario de clase
CREATE PROCEDURE `sp_horario_clase_contar_matriculados`(
    IN p_id_horario_clase INT
)
BEGIN
    SELECT COUNT(id_matricula) AS matriculados
    FROM matriculas
    WHERE id_horario_clase = p_id_horario_clase AND estado = 'Vigente';
END$$

-- Crear una nueva matrícula
CREATE PROCEDURE `sp_matricula_crear`(
    IN p_id_alumno INT,
    IN p_id_horario_clase INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_monto_pagado DECIMAL(10, 2),
    IN p_id_forma_pago INT,
    IN p_id_usuario_registro INT,
    IN p_observaciones TEXT
)
BEGIN
    INSERT INTO matriculas (id_alumno, id_horario_clase, fecha_inicio, fecha_fin, monto_pagado, id_forma_pago, id_usuario_registro, observaciones, estado)
    VALUES (p_id_alumno, p_id_horario_clase, p_fecha_inicio, p_fecha_fin, p_monto_pagado, p_id_forma_pago, p_id_usuario_registro, p_observaciones, 'Vigente');
    SELECT LAST_INSERT_ID() AS id_matricula;
END$$

-- Insertar un día de clase para un alumno
CREATE PROCEDURE `sp_dia_clase_insertar`(
    IN p_id_matricula INT,
    IN p_fecha_clase DATE
)
BEGIN
    INSERT INTO dias_clase_alumno (id_matricula, fecha_clase, estado_asistencia)
    VALUES (p_id_matricula, p_fecha_clase, 'Programada');
END$$

-- Leer todas las matrículas (vista general)
CREATE PROCEDURE `sp_matricula_leer_todas`()
BEGIN
    SELECT
        m.id_matricula,
        CONCAT(a.nombres, ' ', a.apellidos) AS nombre_alumno,
        c.nombre_curso,
        m.fecha_inicio,
        m.fecha_fin,
        m.monto_pagado,
        fp.nombre as forma_pago,
        m.estado
    FROM matriculas m
    JOIN alumnos a ON m.id_alumno = a.id_alumno
    JOIN horarios_clase hc ON m.id_horario_clase = hc.id_horario_clase
    JOIN cursos c ON hc.id_curso = c.id_curso
    JOIN formas_pago fp ON m.id_forma_pago = fp.id_forma_pago
    ORDER BY m.fecha_matricula DESC;
END$$

-- Leer todas las matrículas de un alumno específico
CREATE PROCEDURE `sp_matricula_leer_por_alumno`(
    IN p_id_alumno INT
)
BEGIN
    SELECT
        m.id_matricula,
        c.nombre_curso,
        CONCAT(pr.nombres, ' ', pr.apellidos) AS nombre_profesor,
        m.fecha_inicio,
        m.fecha_fin,
        m.monto_pagado,
        m.estado,
        m.observaciones
    FROM matriculas m
    JOIN horarios_clase hc ON m.id_horario_clase = hc.id_horario_clase
    JOIN cursos c ON hc.id_curso = c.id_curso
    JOIN profesores pr ON hc.id_profesor = pr.id_profesor
    WHERE m.id_alumno = p_id_alumno
    ORDER BY m.fecha_inicio DESC;
END$$

-- Anular una matrícula
CREATE PROCEDURE `sp_matricula_anular`(
    IN p_id_matricula INT,
    IN p_observaciones TEXT
)
BEGIN
    UPDATE matriculas
    SET
        estado = 'Anulada',
        observaciones = CONCAT(IFNULL(observaciones, ''), '\nANULADO: ', p_observaciones)
    WHERE id_matricula = p_id_matricula;
END$$

-- Leer los días de clase de una matrícula específica
CREATE PROCEDURE `sp_dias_clase_leer_por_matricula`(
    IN p_id_matricula INT
)
BEGIN
    SELECT *
    FROM dias_clase_alumno
    WHERE id_matricula = p_id_matricula
    ORDER BY fecha_clase;
END$$

-- Leer una matrícula con todos los detalles por su ID
CREATE PROCEDURE `sp_matricula_leer_detalles_por_id`(
    IN p_id_matricula INT
)
BEGIN
    SELECT
        m.id_matricula,
        a.id_alumno,
        CONCAT(a.nombres, ' ', a.apellidos) AS nombre_alumno,
        a.email AS email_alumno,
        a.telefono AS telefono_alumno,
        c.nombre_curso,
        p.nombre AS nombre_piscina,
        cr.numero_carril,
        CONCAT(pr.nombres, ' ', pr.apellidos) AS nombre_profesor,
        th.nombre AS nombre_tipo_horario,
        hc.hora_inicio,
        hc.hora_fin,
        m.fecha_inicio,
        m.fecha_fin,
        m.monto_pagado,
        fp.nombre as forma_pago,
        m.estado,
        m.observaciones,
        m.fecha_matricula,
        u.nombre_completo AS usuario_registro
    FROM matriculas m
    JOIN alumnos a ON m.id_alumno = a.id_alumno
    JOIN horarios_clase hc ON m.id_horario_clase = hc.id_horario_clase
    JOIN cursos c ON hc.id_curso = c.id_curso
    JOIN carriles cr ON hc.id_carril = cr.id_carril
    JOIN piscinas p ON cr.id_piscina = p.id_piscina
    JOIN profesores pr ON hc.id_profesor = pr.id_profesor
    JOIN tipos_horario th ON hc.id_tipo_horario = th.id_tipo_horario
    JOIN formas_pago fp ON m.id_forma_pago = fp.id_forma_pago
    JOIN usuarios u ON m.id_usuario_registro = u.id_usuario
    WHERE m.id_matricula = p_id_matricula;
END$$

-- *****************************************************************
-- ** REPORTES
-- *****************************************************************

-- Reporte de ventas con filtros opcionales
CREATE PROCEDURE `sp_reporte_ventas`(
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE,
    IN p_id_alumno INT,
    IN p_id_curso INT,
    IN p_id_forma_pago INT
)
BEGIN
    SELECT
        m.id_matricula,
        m.fecha_matricula,
        CONCAT(a.nombres, ' ', a.apellidos) AS nombre_alumno,
        c.nombre_curso,
        m.monto_pagado,
        fp.nombre as forma_pago
    FROM matriculas m
    JOIN alumnos a ON m.id_alumno = a.id_alumno
    JOIN horarios_clase hc ON m.id_horario_clase = hc.id_horario_clase
    JOIN cursos c ON hc.id_curso = c.id_curso
    JOIN formas_pago fp ON m.id_forma_pago = fp.id_forma_pago
    WHERE
        -- Filtro de fecha
        (p_fecha_inicio IS NULL OR m.fecha_matricula >= p_fecha_inicio) AND
        (p_fecha_fin IS NULL OR m.fecha_matricula <= p_fecha_fin) AND
        -- Filtro de alumno
        (p_id_alumno IS NULL OR m.id_alumno = p_id_alumno) AND
        -- Filtro de curso
        (p_id_curso IS NULL OR hc.id_curso = p_id_curso) AND
        -- Filtro de forma de pago
        (p_id_forma_pago IS NULL OR m.id_forma_pago = p_id_forma_pago) AND
        -- Solo matrículas que no estén anuladas
        m.estado <> 'Anulada'
    ORDER BY m.fecha_matricula DESC;
END$$

-- *****************************************************************
-- ** ASISTENCIAS
-- *****************************************************************

-- Actualizar el estado de asistencia de un día de clase de un alumno
CREATE PROCEDURE `sp_dia_clase_actualizar_asistencia`(
    IN p_id_dia_clase BIGINT,
    IN p_estado_asistencia ENUM('Programada', 'Asistio', 'Inasistencia_Justificada', 'Inasistencia_Injustificada', 'Postergada')
)
BEGIN
    UPDATE dias_clase_alumno
    SET estado_asistencia = p_estado_asistencia
    WHERE id_dia_clase = p_id_dia_clase;
END$$

-- Añadir restricción UNIQUE para la asistencia de profesores para evitar duplicados
ALTER TABLE `asistencia_profesor` ADD UNIQUE `idx_profesor_clase_fecha`(`id_profesor`, `id_horario_clase`, `fecha`);
$$

-- Crear o actualizar un registro de asistencia de profesor
CREATE PROCEDURE `sp_asistencia_profesor_crear_o_actualizar`(
    IN p_id_profesor INT,
    IN p_id_horario_clase INT,
    IN p_fecha DATE,
    IN p_estado ENUM('Asistio', 'Permiso', 'Inasistencia_Justificada', 'Inasistencia_Injustificada'),
    IN p_observaciones TEXT
)
BEGIN
    INSERT INTO asistencia_profesor (id_profesor, id_horario_clase, fecha, estado, observaciones)
    VALUES (p_id_profesor, p_id_horario_clase, p_fecha, p_estado, p_observaciones)
    ON DUPLICATE KEY UPDATE
        estado = p_estado,
        observaciones = p_observaciones;
END$$

-- Leer el registro de asistencia de un profesor en un rango de fechas
CREATE PROCEDURE `sp_asistencia_profesor_leer`(
    IN p_id_profesor INT,
    IN p_fecha_inicio DATE,
    IN p_fecha_fin DATE
)
BEGIN
    SELECT
        ap.id_asistencia_profesor,
        ap.fecha,
        ap.estado,
        ap.observaciones,
        c.nombre_curso,
        hc.hora_inicio,
        hc.hora_fin
    FROM asistencia_profesor ap
    JOIN horarios_clase hc ON ap.id_horario_clase = hc.id_horario_clase
    JOIN cursos c ON hc.id_curso = c.id_curso
    WHERE
        ap.id_profesor = p_id_profesor AND
        ap.fecha >= p_fecha_inicio AND
        ap.fecha <= p_fecha_fin
    ORDER BY ap.fecha, hc.hora_inicio;
END$$

DELIMITER ;
