Se va a crear una preferencia de usuario el cual se usara para guardar los valores 1,2,3

Criterio a considerar: Curso activo (Matriculado en un curso y está en un periodo de 30 días antes de vender la matricula)

los valores de estas serán
no existe la preferencia = usuario no migrado
1 = usuario no migrado por cursos activos
2 = usuario migrado

Al hacer login con el sso se aplicarán la siguiente lógica

el usuario que ingrese por sucursal virtual de trabajadores se validara y llegara al sso(35)

Condición 1
si tiene una marca como migrado, se validara si tiene "curso activo"
si tiene curso activo se deja en 35
Condición 2
si tiene una marca como migrado, se validara si tiene "curso activo"
si no tiene curso activo se redireccionara a 45
Condición 3
Marcado permanente
se dejará en 35


http://mutual-moodle35/local/sso/login.php?token=LtpaToken2=xxxxxxxxx

para cargar usuario por  csv usamos la ruta: http://mutual-moodle35/local/sso/sso_assign_preference_csv.php


para cargar usuario por  csv usamos la ruta solo update: http://mutual-moodle35/local/sso/sso_assign_preference_csv_no_create_user.php


Restore curso cli 
php admin/cli/restore_backup.php --file="/var/www/html/mbz/copia_de_seguridad-moodle2-course-8-cap000266-20250725-0945-nu.mbz" --categoryid=1

Completar datos de uusario con servicio de personas buscar_datos_personas_completar

Query migracion
SELECT 
 u.id as id, 
u.username as username, 
u.password as password , 
u.firstname as firstname, 
u.lastname, 
u.email,
c.name as profile_field_empresarazonsocial, 
c.rut as profile_field_empresarut, 
c.contrato as profile_field_empresacontrato,
true as raw_password
	
FROM prefix_company c
left join prefix_company_users cu on cu.companyid = c.id
left join prefix_user as u on cu.userid = u.id
LEFT JOIN prefix_user_preferences up
    ON up.userid = u.id AND up.name = 'migrado'
WHERE u.lastlogin >= UNIX_TIMESTAMP(NOW() - INTERVAL 15 DAY)
AND up.id IS NULL
ORDER BY u.lastlogin DESC

SELECT 
 u.id as id, 
u.username as username, 
u.password as password , 
u.firstname as firstname, 
u.lastname, 
u.email,
'' as profile_field_empresarazonsocial, 
'' as profile_field_empresarut, 
'' as profile_field_empresacontrato,
true as raw_password
	
FROM prefix_user u
LEFT JOIN prefix_user_preferences up
    ON up.userid = u.id AND up.name = 'migrado'
WHERE u.lastlogin >= UNIX_TIMESTAMP(NOW() - INTERVAL 15 DAY)
AND up.id IS NULL
ORDER BY u.lastlogin DESC