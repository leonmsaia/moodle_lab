define(["local_eabccalendar/fullcalendar", "local_eabccalendar/daygrid","core/ajax",'jquery', 'core/modal_factory','local_eabccalendar/moment', 'core/templates', 'local_eabccalendar/interaction','core/modal_events','core/str','local_eabccalendar/alertify'], 
  function(FullCalendar, dayGrid, ajax,$, ModalFactory, moment, Templates, interaction, ModalEvents, Str, alertify) {
    return {
        init: function(iduser, idcourse) {
            var calendarEl = document.getElementById('calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                plugins: [ dayGrid.default, interaction.default ],
                themeSystem: 'bootstrap',
                header: {
                    center: 'dayGridMonth,dayGridWeek,dayGridDay,timeGridFourDay' // buttons for switching between views
                },
                views: {
                    dayGridMonth: {                        
                    }
                },
                buttonText: {
                    today:    M.util.get_string('today', 'local_eabccalendar'),
                    month:    M.util.get_string('month', 'local_eabccalendar'),
                    week:     M.util.get_string('week', 'local_eabccalendar'),
                    day:      M.util.get_string('day', 'local_eabccalendar'),
                    list:     M.util.get_string('list', 'local_eabccalendar'),
                },
                locale: M.util.get_string('locale', 'local_eabccalendar'),                  
                events: function(info, successCallback) {
                  ajax.call([{
                      methodname: "mod_wseabcattendance_get_dates_sessions", args: {  
                          userid: iduser,                                               
                          courseid: idcourse,
                          start: moment(info.startStr).format("X"),
                          end: moment(info.endStr).format("X"),   
                      }
                  }])[0].done(function (response) {
                      //console.log(response);
                      successCallback(
                      response.map(function(eventEl) {                                               
                          //console.log(eventEl)
                          return {      
                              
                              id: eventEl.id,                                                       
                              title: 'Duración: '+eventEl.duracion,
                              start: moment(eventEl.sesiondate).format('YYYY-MM-DD HH:mm'),                      
                              end: moment(eventEl.sesiondate).format('YYYY-MM-DD HH:mm'),                               
                              color: eventEl.color,
                              textColor: '#FFFFFF', 
                              description: 'This is a cool event',   
                              allow: eventEl.direccion,   
                              classNames: eventEl.nombre, 
                              overlap: eventEl.courseid,    
                              constraint : eventEl.estatus,                
                          }
                      })                                                    
                  )                        
                  }).fail(function(ex) {
                      console.error(ex);
                  });
                },
                eventClick: function(info) {                
                    var trigger = $('#fullCalModal');                    
                    var actividad = info.event.classNames;
                    var fecha = moment(info.event.start).format('DD-MM-YYYY'); 
                    var sesionid = info.event.id;
                                    
                    if (info.event.classNames=='bloqueos'){
                        var id = info.event.id;
                        var motivo = info.event.title;
                        var h_fin = info.event.borderColor;
                        var h_inicio = info.event.allow;
                        var render = 'local_eabccalendar/bloqueados';
                        var datos = {id, fecha, h_inicio, h_fin, motivo};
                        var titulo = 'Detalles del Bloqueo '
                    }else{
                        
                        var direccion = (info.event.allow !=null) ? info.event.allow : 'No tiene';
                        var duracion = (info.event.title).slice(9,18);
                        var estatus  = info.event.constraint;
                        var url = info.event.overlap;
                        var h_inicio = moment(info.event.start).format('HH:mm');
                        var render = 'local_eabccalendar/planificado';
                        var titulo = 'Detalles de la Actividad ';
                        var datestart = moment(info.event.start);
                        var days = datestart.diff(moment(), 'days');
                        
                        if (days >= 1){
                            var link = '<a href="../../mod/eabcattendance/focalizacion/view.php?id='+url+'&sesionid='+info.event.id+'&fecha='+fecha+'&hora='+h_inicio+' "><i class="icon fa fa-envelope"></i>Enviar correo empresa</a>';
                        }else{
                            var link = 'Fecha limite de contacto expiró, No puede enviar email';
                        }
                        var datos = {fecha, actividad, direccion, h_inicio, duracion, estatus, url, link, iduser, sesionid};    
                    }

                   ModalFactory.create({
                        title: titulo,
                        body: Templates.render(render, datos),
                        type: ModalFactory.types.SAVE_CANCEL,
                        large: true
                    }).then(function(modal) {
                        modal.show();
                        if (info.event.classNames=='bloqueos'){
                            modal.setSaveButtonText(Str.get_string('delete'));
                            $('.modal-footer > .btn-primary').removeClass().addClass('btn btn-danger');
                        }else{
                            $('.modal-footer').remove();                            
                        }
        
                        modal.getRoot().on(ModalEvents.save, function() {
                            var id = document.getElementById("id").value;                            
                                alertify.confirm(M.util.get_string('confirmDelete', 'local_eabccalendar'), function(confi){
                                    if (confi) {
                                        delete_bloqueo(id);            
                                    }  
                                }, function(cancel){
                                    if(cancel){
                                        alertify.error(M.util.get_string('cancel', 'local_eabccalendar'));
                                    }
                                }).set('labels', {
                                    ok: M.util.get_string('confirm', 'local_eabccalendar'), 
                                    cancel:M.util.get_string('cancel', 'local_eabccalendar')
                                }).setHeader(M.util.get_string('delete', 'local_eabccalendar'));
                            });
        
                        modal.getRoot().on(ModalEvents.hidden, function() {
                            modal.destroy();
                        });
        
                        $('.modal').on('mousedown', function(e) {
                            if (e.which === 1) { 
                                modal.hide();
                            }
                        });
                        $('.modal-content').on('mousedown', function(e) {
                            e.stopPropagation(); 
                        });                 
    
                    }).catch(Notification.exception);
                },                  
                dateClick: function(info) {                 
                    var date    = moment(info.dateStr).format("DD-MM-YYYY");                    
                    if (moment().isSameOrBefore(info.dateStr) ){    
                        var trigger = $('#create-modal');
                        ModalFactory.create({
                            type: ModalFactory.types.SAVE_CANCEL,
                            title: 'Bloquear calendario del día: '+date,
                            body: Templates.render('local_eabccalendar/bloqueo_agenda', {}),
                        }, trigger)
                        .done(function(modal) {                            
                            modal.show();                        
                            modal.getRoot().on(ModalEvents.save, function(e) {                                                        
                                var descripcion = document.getElementById("descripcion").value;
                                var hora_desde =  document.getElementById("hora_desde").value;
                                var hora_hasta =  document.getElementById("hora_hasta").value;

                                if ((hora_desde == '') || (hora_hasta == '')){
                                    e.preventDefault();   
                                    ModalEvents.cancel
                                    var div = document.getElementById('requeridos');
                                    div.innerHTML = 'Por favor coloque las horas requeridas';
                                }else if(hora_desde == hora_hasta){
                                    e.preventDefault();   
                                    ModalEvents.cancel
                                    var div = document.getElementById('requeridos');
                                    div.innerHTML = 'La hora de inicio y fin no puede ser igual';
                                }else if(hora_desde > hora_hasta){
                                    e.preventDefault();   
                                    ModalEvents.cancel
                                    var div = document.getElementById('requeridos');
                                    div.innerHTML = 'La hora de inicio no puede ser mayor a la hora Hasta';
                                }
                                else{
                                    var promises = ajax.call([{
                                        methodname: "local_wseabccalendar_agenda", args: {  
                                            motivo: descripcion,                                               
                                            hora_desde: hora_desde, 
                                            hora_hasta: hora_hasta,
                                            fecha: info.dateStr
                                        }
                                    }]);
                                    var spin = document.getElementById('loader');
                                    spin.setAttribute("style","display:block");
                                    var spinner = M.util.add_spinner(Y, Y.Node('.loader'));
                                    spinner.show(); 
                                    promises[0].done(function (response) {
                                       if (response.estatus){
                                        location.reload();
                                       }else{
                                            spin.setAttribute("style","display:none");
                                            alertify.alert('Error', 'No se guardo, las horas solapan a una actividad planificada', function(){ 
                                                alertify.error('Error'); 
                                            });                                           
                                           modal.destroy(); 
                                       }                                        
                                    }).fail(function(ex) {
                                        spinner.hide();
                                        spin.setAttribute("style","display:none");
                                        alertify.alert('Error', 'Error en el Back. Mensaje: '+ex.errorcode.data + ' Codido de error: '+ex.errorcode.status , function(){ 
                                            alertify.error('Error'); 
                                        });
                                        modal.destroy();
                                        console.log(ex);
                                    });
                                }                                
                            });
                            modal.getRoot().on(ModalEvents.cancel, function(e) {
                                //console.log('CANCEL');
                                modal.destroy(); 
                            });
                        });
                    }  
                },              
          });          
          calendar.render();                    

          var get_dates_bloqueados = ()=> { 
            ajax.call([{
                methodname: "local_wseabccalendar_get_bloqueo_agenda", args: {  
                    userid: iduser,                                               
                }
            }])[0].done(function (response) {
                //console.log(response);
                response.map(function(eventEl) {                                         
                    var eventos = [{                             
                        id: eventEl.id,                                                       
                        title: eventEl.motivo==null ? 'No tiene' : eventEl.motivo,
                        start: moment(eventEl.fecha+ ' '+eventEl.hora_desde).format('YYYY-MM-DD HH:mm'),  
                        end: eventEl.hora_hasta, 
                        allow: eventEl.hora_desde, 
                        color: '#CCC000',
                        textColor: '#FFFFFF', 
                        classNames: 'bloqueos',
                        borderColor: eventEl.hora_hasta
                    }]
                    calendar.addEventSource(eventos);
                })
            }).fail(function(ex) {
                console.error(ex);
            });
          }
          get_dates_bloqueados();

          var delete_bloqueo = (iden)=>{
            ajax.call([{
                methodname: "local_wseabccalendar_delete_bloqueo_agenda", args: {  
                    id: iden,                                               
                }
            }])[0].done(function (response) {
                //console.log('DELETE: '+response);  
                location.reload();                                                     
            }).fail(function(ex) {
                console.error(ex);
            });
          }

        }
    }    
});
