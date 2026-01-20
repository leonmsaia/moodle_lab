# Emma

> Plugin para envio de Mails para mutual, usando el servicio Emma de chile

## Configuración del plugin:
- WSDL del servicio EMMA (emma_wsdl): https://www.emma.cl/WSUI/ws_emma.wsdl
- Web services de EMMA (emma_webservices): https://www.emma.cl/cgi-bin/webservice/WSUI/ws_emma.cgi
- Id de la empresa (idempresa): 649
- Clave de empresa (clave): 2979.ws456hn67xS
- Id de la campaña (idcampana): 181279
- Id de la categoria (idcategoria): 165734

## Métodos implementados del Web Service EMMA

### enviadirecto
- Este método recibe datos para crear o actualizar inteligentemente una ficha en una categoría indicada,
 opcionalmente asociarle un estado y enviar una campaña indicada a esta ficha en forma inmediata.

### campanainfo
- Este método devuelve 5 strings ; tema de la campaña, fecha del último envío, hora del último envío, fecha 
finalización y hora finalización del último envío.

### campanadevueltos
- Este método devuelve el total de correos devueltos de una campaña así como el detalle por tipo de correo devuelto.

### campanareporteenvio
- Este método permite solicitar la generación de un reporte de resultado general de una campaña, el cual es enviado en formato CSV al correo indicado. Reporta sobre el último envío de la campaña solicitada.
El CSV provee; nombre de la campaña, asunto, hora inicio envío, hora fin envío, mails enviados, mails entregados, mails rebotados, aperturas únicas, aperturas totales, tasa apertura desktop, tasa apertura mobile y otros, clics únicos por evento y clics totales por evento.

