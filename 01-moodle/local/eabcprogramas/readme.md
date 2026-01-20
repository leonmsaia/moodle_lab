> # En proceso de desarrollo

# Módulo de Programas e-learning

## Permisos
- `local/eabcprogramas:view` Para el abm de programas.
- `local/eabcprogramas:trabajador` -> Permiso para rol = Trabajador
- `local/eabcprogramas:holding` -> Pemiso para rol = Empresa

Nota: agregar estos permisos al rol = usuario identificado, en caso de no surtir efecto para el rol indicado

## Tareas Programadas
- `finalizacion_programa` Los datos de los certificados y diplomas son almacenados en una tabla llamada "local_eabcprogramas_usuarios", de manera que, estos datos se verán reflejados al consultar programas otorgados por los usuarios. 
- `inactivar_programa` Permite verificar los programas otorgados vencidos y cambiar su estado de activo a inactivo.
- `completion_regular_task` Nativa de Moodle
- `completion_daily_task` Nativa de Moodle

## Configuración
- `Configurar categorías que pueden ser usadas para crear programas:` ir a admin/settings.php?section=local_eabcprogramas

## Programas
- `Crear:` Para dar de alta a un programa ir al menú de navegación->Programas de e-learning->Programas, hacer clic en el botón "crear".
- `Agregar cursos:` Ir al menú de navegación->Programas de e-learning->Programas. Elegir un programa-versión, hacer clic en el botón "agregrar cursos" de la columna "Acción". Siempre que esté en estado = EN PREPARACION, se pueden agregar cursos. 
- `Inactivar: ` Ir al menú de navegación->Programas de e-learning->Programas. Elegir un programa-versión, hacer clic en el botón "Inactivar" de la columna "Acción".
- `Duplicar/Copiar:` Ir al menú de navegación->Programas de e-learning->Programas. Elegir un programa-versión, hacer clic en el botón "Duplicar" de la columna "Acción".
- `Activar: ` Ir al menú de navegación->Programas de e-learning->Programas. Elegir un programa-versión, hacer clic en el botón "Activar" de la columna "Acción".
- `Listar Cursos:` Para listar los cursos asociados a un programa ir al menú de navegación Programas de e-learning->Programas. Elegir un programa de la lista, hacer clic en el botón "Listar Cursos" de la columna "Acción", se mostrará la lista de cursos asociados al programa-versión. Para generar pdf, hacer clic en el botón "Generar PDF"


## Otorgamiento de programas a usuarios
Antes de ejecutar esta funcionalidad se debe tomar en cuenta lo siguiente:
- Configurar y activar programa - versión.
- Configurar año calendario de enrolamiento/inscripción. Ir a la ruta "/admin/settings.php?section=local_eabcprogramas"
- El año calendario de enrolamiento/inscripción en un curso, debe ser igual al año calendario de finalización  del curso.

 Para esta funcionalidad se crea una tarea programada llamada "finalizacion_programa", los datos de los certificados y diplomas son almacenados en una tabla llamada "local_eabcprogramas_usuarios", de manera que, estos datos se verán reflejados al consultar programas obtenidos por los usuarios.

Para ejecutar esta tarea programada ir a Administracion del sitio->Servidor->Tareas Programadas
Buscar la tarea "Generar diploma y certificado a partir de la finalización de un programa" y ejecutar.

## Modelos de diplomas
- `Crear`: Para dar de alta a un modelo de diploma ir al menú de navegación->Programas de e-learning->Modelos de diploma. Hacer clic en el botón 'Crear'
- `Inactivar:` Para incativar un modelo de diploma ir al menú de navegación->Programas de e-learning->Modelos de diploma, elegir un diploma de la lista y en la columna "Acción" hacer clic en el icono "Inactivar/ojo"
- `Eliminar:` Para eliminar un modelo de diploma ir al menú de navegación->Programas de e-learning->Modelos de diploma, elegir un diploma de la lista y en la columna "Acción" hacer clic en el icono "Eliminar/Papelera"
- `Reemplazar`: Para reemplazar un modelo de diploma ir al menú de navegación->Programas de e-learning->Modelos de diploma, elegir un diploma de la lista y en la columna "Acción" hacer clic en el icono "Reemplazar/doble flecha"

## Modelos de certificados
- `Crear`: Para dar de alta a un modelo de certificado ir al menú de navegación->Programas de e-learning->Modelos de Certificados. Hacer clic en el botón 'Crear'
- `Inactivar:` Para dar de baja a un modelo de certificado ir al menú de navegación->Programas de e-learning->Modelos de Certificados, elegir un certificado de la lista y en la columna "Acción" hacer clic en el icono "Inactivar/ojo"
- `Eliminar:` Para eliminar un modelo de certificado ir al menú de navegación->Programas de e-learning->Modelos de Certificados, elegir un certificado de la lista y en la columna "Acción" hacer clic en el icono "Eliminar/papelera".
- `Reemplazar:` Para reemplazar un modelo de certificado ir al menú de navegación->Programas de e-learning->Modelos de Certificados, elegir un certificado de la lista y en la columna "Acción" hacer clic en el icono "Reemplazar/doble flecha"

## Programas Otorgados
- `Otorgar programas:` Para esta funcionalidad se crea una tarea programada llamada "finalizacion_programa". Los datos de los certificados y diplomas son almacenados en una tabla llamada "local_eabcprogramas_usuarios", de manera que, estos datos se verán reflejados al consultar programas otorgados por los usuarios. 

Para ejecutar esta tarea programada, antes, se debe ejecutar dos tareas programadas:
1. Ir a Administracion del sitio->Servidor->Tareas Programadas. Buscar la tareas tareas "completion_daily_task" y "completion_regular_task"
2. Buscar la tarea "finalizacion_programa" y ejecutar.

- `Listar Programas Otorgados Rol=Admin:` Para listar los programas otorgados, ir al menu de navegación->Pregramas e-Learning->Programas Otorgados.
- `Listar Programas Otorgados Rol=Empresa/Holding:` Para listar los programas otorgados, ir al menu de navegación->Programas Otorgados
- `Listar Programas Otorgados Rol=Trabajador:` Para listar los programas otorgados, ir al menu de navegación->Programas Otorgados