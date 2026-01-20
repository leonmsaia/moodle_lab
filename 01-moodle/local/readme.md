Se va a crear una preferencia de usuario el cual se usara para guardar los valores 1,2

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
si no tiene una marca como migrado
se dejará en 35