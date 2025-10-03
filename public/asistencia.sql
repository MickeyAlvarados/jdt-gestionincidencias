CREATE TYPE nivel_educativo_tipo AS ENUM ('inicial', 'primaria', 'secundaria');
CREATE TYPE estado_aula AS ENUM ('activo', 'inactivo', 'mantenimiento');
CREATE TYPE dia_semana_tipo AS ENUM ('lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado');
CREATE TYPE sexo_tipo AS ENUM ('M', 'F');
CREATE TYPE tipo_contrato AS ENUM ('nombrado', 'contratado', 'practicante');
CREATE TYPE jornada_tipo AS ENUM ('completa', 'parcial');
CREATE TYPE estado_docente AS ENUM ('activo', 'inactivo', 'licencia', 'vacaciones');
CREATE TYPE tipo_aula_enum AS ENUM ('regular', 'laboratorio', 'biblioteca', 'auditorio');
CREATE TYPE tipo_curso_enum AS ENUM ('obligatorio', 'electivo');
CREATE TYPE tipo_licencia_enum AS ENUM ('licencia_medica', 'licencia_maternidad', 'permiso_personal', 'capacitacion', 'otros');
CREATE TYPE estado_licencia AS ENUM ('solicitado', 'aprobado', 'rechazado', 'en_uso', 'finalizado');
CREATE TYPE tipo_actividad_enum AS ENUM ('recuperacion', 'evento', 'reunion', 'capacitacion', 'otros');
CREATE TYPE estado_actividad AS ENUM ('programado', 'ejecutado', 'cancelado');
CREATE TYPE rol_usuario AS ENUM ('admin', 'director', 'coordinador', 'secretaria');
CREATE TYPE accion_auditoria AS ENUM ('INSERT', 'UPDATE', 'DELETE');

-- Tabla empresa/institución
CREATE TABLE empresa (
    id_empresa SERIAL PRIMARY KEY,
    nombre_empresa VARCHAR(200) NOT NULL,
    ruc VARCHAR(11) UNIQUE,
    direccion TEXT,
    telefono VARCHAR(15),
    email VARCHAR(100),
    logo VARCHAR(255),
    estado BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla área (departamentos académicos)
CREATE TABLE area (
    id_area SERIAL PRIMARY KEY,
    nombre_area VARCHAR(100) NOT NULL,
    descripcion TEXT,
    coordinador VARCHAR(150),
    estado BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla grado (niveles educativos)
CREATE TABLE grado (
    id_grado SERIAL PRIMARY KEY,
    nombre_grado VARCHAR(50) NOT NULL, -- Ej: 1ro Primaria, 3ro Secundaria
    nivel_educativo nivel_educativo_tipo NOT NULL,
    orden_grado INTEGER, -- Para ordenamiento
    estado BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla sección
CREATE TABLE seccion (
    id_seccion SERIAL PRIMARY KEY,
    id_grado INTEGER NOT NULL,
    nombre_seccion VARCHAR(10) NOT NULL, -- A, B, C, etc.
    capacidad_maxima INTEGER DEFAULT 30,
    tutor VARCHAR(150),
    estado BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_seccion_grado FOREIGN KEY (id_grado) REFERENCES grado(id_grado)
);

-- Tabla aula (espacios físicos)
CREATE TABLE aula (
    id_aula SERIAL PRIMARY KEY,
    codigo_aula VARCHAR(20) UNIQUE NOT NULL, -- Ej: A101, B205
    nombre_aula VARCHAR(100),
    piso INTEGER,
    capacidad INTEGER DEFAULT 30,
    tipo_aula tipo_aula_enum DEFAULT 'regular',
    tiene_proyector BOOLEAN DEFAULT FALSE,
    tiene_aire_acondicionado BOOLEAN DEFAULT FALSE,
    estado BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla cursos/materias
CREATE TABLE curso (
    id_curso SERIAL PRIMARY KEY,
    id_area INTEGER NOT NULL,
    nombre_curso VARCHAR(100) NOT NULL,
    codigo_curso VARCHAR(20) UNIQUE,
    descripcion TEXT,
    horas_semanales INTEGER DEFAULT 2,
    creditos INTEGER DEFAULT 1,
    tipo_curso tipo_curso_enum DEFAULT 'obligatorio',
    estado BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_curso_area FOREIGN KEY (id_area) REFERENCES area(id_area)
);

-- MÓDULO DOCENTE
-- ================================

-- Tabla docente
CREATE TABLE docente (
    id_docente SERIAL PRIMARY KEY,
    codigo_docente VARCHAR(20) UNIQUE NOT NULL,
    dni VARCHAR(8) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefono VARCHAR(15),
    direccion TEXT,
    fecha_nacimiento DATE,
    sexo sexo_tipo,
    especialidad VARCHAR(150),
    titulo_profesional VARCHAR(200),
    fecha_ingreso DATE NOT NULL,
    tipo_contrato tipo_contrato DEFAULT 'contratado',
    jornada_laboral jornada_tipo DEFAULT 'completa',
    horas_semanales INTEGER DEFAULT 40,
    sueldo_base DECIMAL(10,2),
    foto VARCHAR(255),
    estado estado_docente DEFAULT 'activo',
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla horario
CREATE TABLE horario (
    id_horario SERIAL PRIMARY KEY,
    id_docente INTEGER NOT NULL,
    id_curso INTEGER NOT NULL,
    id_seccion INTEGER NOT NULL,
    id_aula INTEGER NOT NULL,
    dia_semana dia_semana_tipo NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    periodo_academico VARCHAR(20) NOT NULL, -- 2024-I, 2024-II, etc.
    observaciones TEXT,
    estado BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_horario_docente FOREIGN KEY (id_docente) REFERENCES docente(id_docente),
    CONSTRAINT fk_horario_curso FOREIGN KEY (id_curso) REFERENCES curso(id_curso),
    CONSTRAINT fk_horario_seccion FOREIGN KEY (id_seccion) REFERENCES seccion(id_seccion),
    CONSTRAINT fk_horario_aula FOREIGN KEY (id_aula) REFERENCES aula(id_aula),
    CONSTRAINT unique_horario UNIQUE (id_docente, dia_semana, hora_inicio, periodo_academico)
);

-- MÓDULO ASISTENCIA
-- ================================

-- Tabla tipos de asistencia
CREATE TABLE tipo_asistencia (
    id_tipo_asistencia SERIAL PRIMARY KEY,
    nombre_tipo VARCHAR(50) NOT NULL, -- Presente, Tardanza, Falta, Falta Justificada
    codigo_tipo VARCHAR(10) UNIQUE NOT NULL, -- P, T, F, FJ
    descripcion TEXT,
    descuenta_horas BOOLEAN DEFAULT TRUE,
    color_codigo VARCHAR(7) DEFAULT '#000000', -- Para interfaz visual
    estado BOOLEAN DEFAULT true
);


CREATE TABLE motivo_inasistencia (
    id_motivo SERIAL PRIMARY KEY,
    nombre_motivo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    requiere_justificacion BOOLEAN DEFAULT FALSE,
    estado BOOLEAN DEFAULT true
);

-- Tabla principal de asistencias
CREATE TABLE asistencia (
    id_asistencia SERIAL PRIMARY KEY,
    id_docente INTEGER NOT NULL,
    id_horario INTEGER NOT NULL,
    fecha_asistencia DATE NOT NULL,
    id_tipo_asistencia INTEGER NOT NULL,
    hora_llegada TIME,
    hora_salida TIME,
    id_motivo INTEGER,
    observaciones TEXT,
    documento_justificacion VARCHAR(255), -- Ruta del archivo
    registrado_por INTEGER, -- ID del usuario que registró
    fecha_registro TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asistencia_docente FOREIGN KEY (id_docente) REFERENCES docente(id_docente),
    CONSTRAINT fk_asistencia_horario FOREIGN KEY (id_horario) REFERENCES horario(id_horario),
    CONSTRAINT fk_asistencia_tipo FOREIGN KEY (id_tipo_asistencia) REFERENCES tipo_asistencia(id_tipo_asistencia),
    CONSTRAINT fk_asistencia_motivo FOREIGN KEY (id_motivo) REFERENCES motivo_inasistencia(id_motivo),
    CONSTRAINT unique_asistencia UNIQUE (id_docente, id_horario, fecha_asistencia)
);

-- Tabla para licencias y permisos
CREATE TABLE licencia_permiso (
    id_licencia SERIAL PRIMARY KEY,
    id_docente INTEGER NOT NULL,
    tipo_licencia tipo_licencia_enum NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    dias_calendario INTEGER,
    dias_habiles INTEGER,
    descripcion TEXT,
    documento_sustentatorio VARCHAR(255),
    aprobado_por VARCHAR(150),
    estado estado_licencia DEFAULT 'solicitado',
    fecha_solicitud TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion TIMESTAMP WITH TIME ZONE,
    CONSTRAINT fk_licencia_docente FOREIGN KEY (id_docente) REFERENCES docente(id_docente)
);

-- Tabla para horarios especiales (recuperación, eventos, etc.)
CREATE TABLE horario_especial (
    id_horario_especial SERIAL PRIMARY KEY,
    id_docente INTEGER NOT NULL,
    fecha_especial DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    descripcion TEXT NOT NULL,
    tipo_actividad tipo_actividad_enum NOT NULL,
    id_aula INTEGER,
    estado estado_actividad DEFAULT 'programado',
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_horario_especial_docente FOREIGN KEY (id_docente) REFERENCES docente(id_docente),
    CONSTRAINT fk_horario_especial_aula FOREIGN KEY (id_aula) REFERENCES aula(id_aula)
);

-- TABLAS DE SOPORTE
-- ================================

-- Tabla usuarios del sistema
CREATE TABLE usuario (
    id_usuario SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    rol rol_usuario NOT NULL,
    estado BOOLEAN DEFAULT true,
    ultimo_acceso TIMESTAMP WITH TIME ZONE,
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para auditoría de cambios
CREATE TABLE auditoria (
    id_auditoria SERIAL PRIMARY KEY,
    tabla_afectada VARCHAR(50) NOT NULL,
    id_registro INTEGER NOT NULL,
    accion accion_auditoria NOT NULL,
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    id_usuario INTEGER,
    fecha_accion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);
