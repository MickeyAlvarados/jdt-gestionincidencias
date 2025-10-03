

CREATE TABLE public.bd_conocimientos (
	id int8 NOT NULL,
	id_incidencia int8 NULL,
	descripcion_problema text NULL,
	fecha_incidencia date NULL,
	comentario_resolucion text NULL,
	empleado_resolutor varchar(100) NULL,
	CONSTRAINT bd_conocimientos_pkey PRIMARY KEY (id)
);

CREATE TABLE public.cargos (
	id int8 NOT NULL,
	descripcion int8 NULL,
	CONSTRAINT cargos_pkey PRIMARY KEY (id)
);

CREATE TABLE public.categorias (
	id int8 NOT NULL,
	descripcion varchar(200) NULL,
	CONSTRAINT categorias_pkey PRIMARY KEY (id)
);

CREATE TABLE public.chat (
	id int8 NOT NULL,
	fecha_chat int8 NULL,
	CONSTRAINT chat_pkey PRIMARY KEY (id)
);

CREATE TABLE public.estados (
	id int8 NOT NULL,
	descripcion varchar(30) NULL,
	CONSTRAINT estados_pkey PRIMARY KEY (id)
);

CREATE TABLE public.modulos (
	id int8 NOT NULL,
	descripcion varchar(100) NULL,
	CONSTRAINT modulos_pkey PRIMARY KEY (id)
);

CREATE TABLE public.roles (
	id int8 NOT NULL,
	descripcion varchar(100) NULL,
	CONSTRAINT roles_pkey PRIMARY KEY (id)
);

CREATE TABLE public.empleados (
	id int8 NOT NULL,
	idcargos int8 NULL,
	idusuarios int8 NULL,
	CONSTRAINT empleados_pkey PRIMARY KEY (id),
	CONSTRAINT fk_empleados_user_id_foreign FOREIGN KEY (idusuarios) REFERENCES public.users(id) ON DELETE CASCADE
	CONSTRAINT fk_empleados_idcargos_cargos_id FOREIGN KEY (idcargos) REFERENCES public.cargos(id)
);

CREATE TABLE public.incidencias (
	id int8 NOT NULL,
	descripcion_problema text NULL,
	fecha_incidencia date NULL,
	idcategoria int8 NULL,
	idempleado int8 NULL,
	estado int8 NULL,
	id_chat int8 NULL,
	prioridad int8 NULL,
	CONSTRAINT incidencias_pkey PRIMARY KEY (id),
	CONSTRAINT fk_incidencias_estado_estados_id FOREIGN KEY (estado) REFERENCES public.estados(id),
	CONSTRAINT fk_incidencias_id_chat_chat_id FOREIGN KEY (id_chat) REFERENCES public.chat(id),
	CONSTRAINT fk_incidencias_idcategoria_categorias_id FOREIGN KEY (idcategoria) REFERENCES public.categorias(id),
	CONSTRAINT fk_incidencias_idempleado_empleados_id FOREIGN KEY (idempleado) REFERENCES public.empleados(id)
);


CREATE TABLE public.chat_mensajes (
	id int8 NOT NULL,
	id_chat int8 NOT NULL,
	emisor int8 NULL,
	contenido_mensaje text NULL,
	fecha_envio timestamp NULL,
	CONSTRAINT chat_mensajes_pkey PRIMARY KEY (id, id_chat),
	CONSTRAINT fk_chat_mensajes_emisor_empleados_id FOREIGN KEY (emisor) REFERENCES public.empleados(id),
	CONSTRAINT fk_chat_mensajes_id_chat_chat_id FOREIGN KEY (id_chat) REFERENCES public.chat(id)
);

CREATE TABLE public.detalle_incidencia (
	id int8 NOT NULL,
	idincidencia int8 NOT NULL,
	fecha_inicio date NULL,
	estado_atencion int8 NULL,
	idempleado_informatica int8 NULL,
	comentarios text NULL,
	fecha_cierre date NULL,
	CONSTRAINT detalle_incidencia_pkey PRIMARY KEY (id, idincidencia),
	CONSTRAINT fk_detalle_incidencia_estado_atencion_estados_id FOREIGN KEY (estado_atencion) REFERENCES public.estados(id),
	CONSTRAINT fk_detalle_incidencia_idempleado_informatica_empleados_id FOREIGN KEY (idempleado_informatica) REFERENCES public.empleados(id),
	CONSTRAINT fk_detalle_incidencia_idincidencia_incidencias_id FOREIGN KEY (idincidencia) REFERENCES public.incidencias(id)
);