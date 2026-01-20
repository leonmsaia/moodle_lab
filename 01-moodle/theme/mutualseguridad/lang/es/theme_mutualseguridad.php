<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language file.
 *
 * @package   theme_mutualseguridad
 * @copyright 2017 Willian Mano - http://conecti.me
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['pluginname'] = 'mutualseguridad';
$string['configtitle'] = 'mutualseguridad';
$string['choosereadme'] = 'mutualseguridad es un tema moderno y altamente personalizable. Este tema está pensado para usarse directamente, o como un tema padre al crear nuevos temas que utilicen Bootstrap 4.';

$string['currentinparentheses'] = '(actual)';
$string['region-side-pre'] = 'Derecha';
$string['prev_section'] = 'Sección anterior';
$string['next_section'] = 'Siguiente sección';
$string['themedevelopedby'] = 'Este tema fue desarrollado orgullosamente por';
$string['themedevelopedbyconectimepartner'] = 'una Compañía Moodle Partner Brasileña.';
$string['needsupport'] = '¿Necesita soporte para su sitio Moodle? ';
$string['pleasuretohelp'] = '¡Será un placer ayudarle!';
$string['access'] = 'Acceder';
$string['prev_activity'] = 'Actividad anterior';
$string['next_activity'] = 'Siguiente actividad';
$string['donthaveanaccount'] = '¿No tiene una cuenta?';
$string['signinwith'] = 'Iniciar sesión con';

// General settings tab.
$string['generalsettings'] = 'General';
$string['logo'] = 'Logo';
$string['logodesc'] = 'El logo se muestra en la cabecera.';
$string['favicon'] = 'Favicon personalizado';
$string['favicondesc'] = 'Suba su propio favicon. Debe ser un archivo .ico.';
$string['preset'] = 'Preajuste del tema';
$string['preset_desc'] = 'Elija un preajuste para cambiar ampliamente la apariencia del tema.';
$string['presetfiles'] = 'Archivos de preajuste de tema adicionales';
$string['presetfiles_desc'] = 'Los archivos de preajuste se pueden usar para alterar drásticamente la apariencia del tema. Consulte <a href="https://docs.moodle.org/dev/Boost_Presets">Preajustes de Boost</a> para obtener información sobre cómo crear y compartir sus propios archivos de preajuste.';
$string['loginbgimg'] = 'Fondo de la página de inicio de sesión';
$string['loginbgimg_desc'] = 'Suba su imagen de fondo personalizada para la página de inicio de sesión.';
$string['brandcolor'] = 'Color de la marca';
$string['brandcolor_desc'] = 'El color de acento.';
$string['secondarymenucolor'] = 'Color del menú secundario';
$string['secondarymenucolor_desc'] = 'Color de fondo del menú secundario';
$string['navbarbg'] = 'Color de la barra de navegación';
$string['navbarbg_desc'] = 'El color de la barra de navegación izquierda';
$string['navbarbghover'] = 'Color de la barra de navegación (al pasar el cursor)';
$string['navbarbghover_desc'] = 'El color de la barra de navegación izquierda (al pasar el cursor)';
$string['fontsite'] = 'Fuente del sitio';
$string['fontsite_desc'] = 'Fuente predeterminada del sitio. Puede probar las fuentes en el <a href="https://fonts.google.com">sitio de Google Fonts</a>.';
$string['enablecourseindex'] = 'Habilitar índice del curso';
$string['enablecourseindex_desc'] = 'Puede mostrar/ocultar la navegación del índice del curso';
$string['enableclassicbreadcrumb'] = 'Habilitar migas de pan clásicas';
$string['enableclassicbreadcrumb_desc'] = 'Este ajuste habilita las migas de pan clásicas, mostrándolas en páginas como cursos y categorías.';

// Advanced settings tab.
$string['advancedsettings'] = 'Avanzado';
$string['rawscsspre'] = 'SCSS inicial en crudo';
$string['rawscsspre_desc'] = 'En este campo puede proporcionar código SCSS de inicialización, se inyectará antes que todo lo demás. La mayoría de las veces usará este ajuste para definir variables.';
$string['rawscss'] = 'SCSS en crudo';
$string['rawscss_desc'] = 'Use este campo para proporcionar código SCSS o CSS que se inyectará al final de la hoja de estilos.';
$string['googleanalytics'] = 'Código de Google Analytics V4';
$string['googleanalyticsdesc'] = 'Por favor, ingrese su código de Google Analytics V4 para habilitar las analíticas en su sitio web. El formato del código debe ser como [G-XXXXXXXXXX]';
$string['hvpcss'] = 'CSS de H5P en crudo';
$string['hvpcss_desc'] = 'Use este campo para proporcionar un archivo CSS que se inyectará en las páginas del plugin mod_hvp.';

// Frontpage settings tab.
$string['frontpagesettings'] = 'Página principal';
$string['displaymarketingboxes'] = 'Mostrar cajas de marketing en la página principal';
$string['displaymarketingboxesdesc'] = 'Si quiere ver las cajas, seleccione sí <strong>luego haga clic en GUARDAR</strong> para cargar los campos de entrada.';
$string['marketingsectionheading'] = 'Título del encabezado de la sección de marketing';
$string['marketingsectioncontent'] = 'Contenido de la sección de marketing';
$string['marketingicon'] = 'Icono de Marketing {$a}';
$string['marketingheading'] = 'Encabezado de Marketing {$a}';
$string['marketingcontent'] = 'Contenido de Marketing {$a}';

$string['disableteacherspic'] = 'Deshabilitar foto de los profesores';
$string['disableteacherspicdesc'] = 'Este ajuste oculta las fotos de los profesores de las tarjetas del curso.';

$string['sliderfrontpageloggedin'] = '¿Mostrar carrusel en la página principal después de iniciar sesión?';
$string['sliderfrontpageloggedindesc'] = 'Si está habilitado, el carrusel se mostrará en la página principal reemplazando la imagen de cabecera.';
$string['slidercount'] = 'Cantidad de diapositivas';
$string['slidercountdesc'] = 'Seleccione cuántas diapositivas quiere agregar <strong>luego haga clic en GUARDAR</strong> para cargar los campos de entrada.';
$string['sliderimage'] = 'Imagen de la diapositiva';
$string['sliderimagedesc'] = 'Agregue una imagen para su diapositiva. El tamaño recomendado es 1500px x 540px o superior.';
$string['slidertitle'] = 'Título de la diapositiva';
$string['slidertitledesc'] = 'Agregue el título de la diapositiva.';
$string['slidercaption'] = 'Leyenda de la diapositiva';
$string['slidercaptiondesc'] = 'Agregue una leyenda para su diapositiva';

$string['numbersfrontpage'] = 'Mostrar números del sitio';
$string['numbersfrontpagedesc'] = 'Si está habilitado, muestra el número de usuarios activos y cursos en la página principal.';
$string['numbersfrontpagecontent'] = 'Contenido de la sección de números';
$string['numbersfrontpagecontentdesc'] = 'Puede agregar cualquier texto al lado izquierdo de la sección de números';
$string['numbersfrontpagecontentdefault'] = '<h2>Con la confianza de más de 25,000 clientes satisfechos.</h2>
                    <p>Con montones de bloques únicos, puede construir fácilmente <br class="d-none d-sm-block d-md-none d-xl-block">
                        una página sin codificar. Construya su próximo sitio web <br class="d-none d-sm-block d-md-none d-xl-block">
                        en pocos minutos.</p>';
$string['numbersusers'] = 'Usuarios activos accediendo a nuestros increíbles recursos';
$string['numberscourses'] = '¡Cursos hechos para usted en los que puede confiar!';

$string['faq'] = 'Preguntas frecuentes';
$string['faqcount'] = 'Cantidad de preguntas frecuentes';
$string['faqcountdesc'] = 'Seleccione cuántas preguntas quiere agregar <strong>luego haga clic en GUARDAR</strong> para cargar los campos de entrada.<br>Si no quiere preguntas frecuentes, solo seleccione 0.';
$string['faqquestion'] = 'Pregunta frecuente {$a}';
$string['faqanswer'] = 'Respuesta frecuente {$a}';

// Footer settings tab.
$string['footersettings'] = 'Pie de página';
$string['website'] = 'URL del sitio web';
$string['websitedesc'] = 'Sitio web principal de la compañía';
$string['mobile'] = 'Móvil';
$string['mobiledesc'] = 'Ingrese Nro. de Móvil. Ej: +5598912341234';
$string['mail'] = 'Correo electrónico';
$string['maildesc'] = 'Correo electrónico de soporte de la compañía';
$string['facebook'] = 'URL de Facebook';
$string['facebookdesc'] = 'Ingrese la URL de su Facebook. (ej: http://www.facebook.com/miinstitucion)';
$string['twitter'] = 'URL de Twitter';
$string['twitterdesc'] = 'Ingrese la URL de su Twitter. (ej: http://www.twitter.com/miinstitucion)';
$string['linkedin'] = 'URL de Linkedin';
$string['linkedindesc'] = 'Ingrese la URL de su Linkedin. (ej: http://www.linkedin.com/miinstitucion)';
$string['youtube'] = 'URL de Youtube';
$string['youtubedesc'] = 'Ingrese la URL de su Youtube. (ej: https://www.youtube.com/user/miinstitucion)';
$string['instagram'] = 'URL de Instagram';
$string['instagramdesc'] = 'Ingrese la URL de su Instagram. (ej: https://www.instagram.com/miinstitucion)';
$string['whatsapp'] = 'Número de Whatsapp';
$string['whatsappdesc'] = 'Ingrese su número de Whatsapp para contacto.';
$string['telegram'] = 'Telegram';
$string['telegramdesc'] = 'Ingrese su contacto de Telegram o enlace de grupo.';
$string['contactus'] = 'Contáctenos';
$string['followus'] = 'Síganos';

// Mypublic page.
$string['aboutme'] = 'Sobre mí';
$string['personalinformation'] = 'Información personal';
$string['addcontact'] = 'Añadir contacto';
$string['removecontact'] = 'Eliminar contacto';

// Theme settings.
$string['themesettings:accessibility'] = 'Accesibilidad';
$string['themesettings:fonttype'] = 'Tipo de fuente';
$string['themesettings:defaultfont'] = 'Fuente predeterminada';
$string['themesettings:dyslexicfont'] = 'Fuente para dislexia';
$string['themesettings:enableaccessibilitytoolbar'] = 'Habilitar barra de herramientas de accesibilidad';
$string['themesettingg:successfullysaved'] = 'Ajustes de accesibilidad guardados exitosamente';

// Accessibility features.
$string['accessibility:fontsize'] = 'Tamaño de fuente';
$string['accessibility:decreasefont'] = 'Disminuir tamaño de fuente';
$string['accessibility:resetfont'] = 'Restablecer tamaño de fuente';
$string['accessibility:increasefont'] = 'Aumentar tamaño de fuente';
$string['accessibility:sitecolor'] = 'Color del sitio';
$string['accessibility:resetsitecolor'] = 'Restablecer color del sitio';
$string['accessibility:sitecolor2'] = 'Bajo contraste 1';
$string['accessibility:sitecolor3'] = 'Bajo contraste 2';
$string['accessibility:sitecolor4'] = 'Alto contraste';

// Data privacy.
$string['privacy:metadata:preference:accessibilitystyles_fontsizeclass'] = 'La preferencia del usuario para el tamaño de fuente.';
$string['privacy:metadata:preference:accessibilitystyles_sitecolorclass'] = 'La preferencia del usuario para el color del sitio.';
$string['privacy:metadata:preference:thememutualseguridadsettings_fonttype'] = 'La preferencia del usuario para el tipo de fuente.';
$string['privacy:metadata:preference:thememutualseguridadsettings_enableaccessibilitytoolbar'] = 'La preferencia del usuario para habilitar la barra de herramientas de accesibilidad.';

$string['privacy:accessibilitystyles_fontsizeclass'] = 'La preferencia actual para el tamaño de fuente es: {$a}.';
$string['privacy:accessibilitystyles_sitecolorclass'] = 'La preferencia actual para el color del sitio es: {$a}.';
$string['privacy:thememutualseguridadsettings_fonttype'] = 'La preferencia actual para el tipo de fuente es: {$a}.';
$string['privacy:thememutualseguridadsettings_enableaccessibilitytoolbar'] = 'La preferencia actual para habilitar la barra de herramientas de accesibilidad es mostrarla.';

$string['redirectmessage'] = 'Esta página debería redirigirse automáticamente.';
$string['redirectbtntext'] = 'Si no sucede nada, por favor haga clic aquí para continuar.';

$string['certvalidator'] = 'Validador de Diplomas';