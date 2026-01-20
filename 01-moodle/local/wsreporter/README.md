# Plugin WS Reporter para Moodle

Este es un plugin local para Moodle que expone un servicio web para obtener métricas clave sobre la actividad de e-learning.

## Descripción

El plugin `local_wsreporter` proporciona un único endpoint de servicio web que devuelve los siguientes datos en formato JSON:

-   **`enrolled_users_24h`**: Cantidad de alumnos inscritos en cursos e-learning en las últimas 24 horas.
-   **`completed_courses_24h`**: Cantidad de alumnos que han finalizado cursos e-learning en las últimas 24 horas.
-   **`completed_users_sent_24h`**: Cantidad de finalizaciones de cursos e-learning que han sido notificadas al sistema externo (back) en las últimas 24 horas.
-   **`pending_enrollments_approved`**: Total de inscripciones con calificaciones aprobadas que están pendientes de ser enviadas al sistema externo (back).

## Requisitos

-   Moodle 4.4 o superior.

## Instalación

1.  Copia el directorio `wsreporter` en el directorio `local/` de tu instalación de Moodle.
2.  Inicia sesión en tu sitio Moodle como administrador.
3.  Ve a `Administración del sitio > Notificaciones`.
4.  Moodle detectará el nuevo plugin y te guiará a través del proceso de instalación.

## Configuración del Servicio Web

Para utilizar el servicio web, sigue estos pasos:

### 1. Habilitar Servicios Web

-   Ve a `Administración del sitio > Servidor > Servicios web > General`.
-   Asegúrate de que la opción **Habilitar servicios web** esté marcada.

### 2. Habilitar Protocolos

-   Ve a `Administración del sitio > Servidor > Servicios web > Gestionar protocolos`.
-   Habilita el protocolo que desees utilizar (se recomienda **REST**).

### 3. Crear un Usuario Específico (Recomendado)

-   Es una buena práctica crear un usuario dedicado exclusivamente para acceder a los servicios web.
-   Ve a `Administración del sitio > Usuarios > Cuentas > Agregar un usuario`.
-   Crea un nuevo usuario. No es necesario que tenga privilegios elevados.

### 4. Crear un Rol para el Servicio Web

-   Ve a `Administración del sitio > Usuarios > Permisos > Definir roles`.
-   Crea un nuevo rol (ej. "Usuario de Servicio Web").
-   Asigna la capacidad `local/wsreporter:view` a este rol.

### 5. Asignar el Rol al Usuario

-   Asigna el rol recién creado al usuario específico del servicio web a nivel de **Sistema**.

### 6. Crear el Servicio Externo

-   Ve a `Administración del sitio > Servidor > Servicios web > Servicios externos`.
-   Haz clic en **Agregar** y crea un nuevo servicio (ej. "Servicio de Reportería").
-   En la configuración del servicio, asegúrate de que la casilla **Habilitado** esté marcada.
-   Haz clic en **Agregar funciones** y añade la función `local_wsreporter_get_report_data`.

### 7. Crear un Token

-   Ve a `Administración del sitio > Servidor > Servicios web > Gestionar tokens`.
-   Haz clic en **Agregar**.
-   Selecciona el **Usuario** y el **Servicio** que creaste en los pasos anteriores.
-   Guarda los cambios. Se generará un token que necesitarás para realizar las llamadas al servicio web.

## Uso del Servicio Web

Para llamar al servicio web, realiza una petición GET o POST a la siguiente URL:

`http://<tu-sitio-moodle>/webservice/rest/server.php`

**Parámetros requeridos:**

-   `wstoken`: El token que generaste.
-   `wsfunction`: `local_wsreporter_get_report_data`
-   `moodlewsrestformat`: `json`

### Ejemplo de Respuesta Exitosa

```json
{
    "enrolled_users_24h": 15,
    "completed_courses_24h": 10,
    "completed_users_sent_24h": 8,
    "pending_enrollments_approved": 5
}
```

### Ejemplo con cURL

```bash
curl -X POST \
-H "Content-Type: application/x-www-form-urlencoded" \
'https://URL_DE_MOODLE/webservice/rest/server.php' \
-d 'wstoken=TOKEN_DEL_USUARIO' \
-d 'wsfunction=local_wsreporter_get_report_data' \
-d 'moodlewsrestformat=json'
```
