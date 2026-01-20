# local_migrategrades
se debe considerar que es una migracion y los nombres de las actividades deben ser iguales para que la migracion pudiera funcionar

Plugin local para migrar notas desde Moodle **viejo** hacia Moodle **nuevo** tomando la nota mayor; en empate gana **viejo**. Además, cuando gana **viejo**, copia a Moodle nuevo las fechas de **matrícula** y **finalización** asociadas (desde `course_completions` del viejo, con fallback de matrícula a `user_enrolments.timecreated`).

## CSV
- Debe incluir cabeceras: `username,shortname`
- Una fila por usuario/curso.

## Configuración (Admin)
Ruta: Administración del sitio → Plugins → Plugins locales → Migración de notas

Completar:
- `old_dbhost`, `old_dbport`, `old_dbname`, `old_dbuser`, `old_dbpass`, `old_dbprefix`, `old_dbcharset`

## Uso
Abrir:
- `/local/migrategrades/index.php`

Subir el CSV y revisar resultados.


Casos de prueba 

Tomar la data de "35" y la suba en "45" tomando en cuenta, calculo de notas, notas mayores, fecha en base a notas mayores para futuros casos que van a salir
casos
usaurio tiene nota en "35" pero no tiene nota en "45" ok
usuario tiene nota en "45" pero no tiene nota en "35" ok
usuario tiene nota en "45" 80 y en "35" 90  debe aplicar logica ok
usuario tiene nota en "45" 90 y "45" 40 debe aplicar logica
usuario tiene nota en ninguno de los sitios


Si el usuario no existe en "45" pero si existe en "35" debe consultar la tabla inscripcion_elarning_back y migrar el usuario, matricularlo y guardarlo en inscripcion elearning en "45"
