define(['jquery', 'core/ajax', 'format_eabctiles/alertify', 'core/templates'], function($, ajax, alertify, templates) {
    return {
        init: function(reasons, reasonsopen) {
            alertify.dialog('customModal', function factory() {
                return{
                    main: function(content) {
                        this.setContent(content);
                        
                        $('#motivo').change(function(event) {
                            var motivo = $(this).find(':selected').data('questiontype');
                            if (motivo == 'openquestion') {
                                $("#textotherlabel").attr('style', 'display: block;');
                                $('#confirm').removeAttr("disabled");
                            } else {
                                if (motivo == '') {
                                    $("#textotherlabel").attr('style', 'display: none;');
                                    $("#textotherlabel").val('');
                                    $('#confirm').attr('disabled', 'true');
                                } else {
                                    $("#textotherlabel").attr('style', 'display: none;');
                                    $("#textotherlabel").val('');
                                    $('#confirm').removeAttr("disabled");
                                }

                            }
                        });
                    },
                    setup: function() {
                        return {
                            options: {
                                basic: false,
                                maximizable: false,
                                resizable: false,
                                padding: true,
                                closableByDimmer: false,
                                title: M.util.get_string('suspendactivity', 'format_eabctiles'),
                            },
                            buttons: [
                                {text: "Confirmar", key: 'ok', attrs: {id: 'confirm'}, },
                                {text: "Cancelar", key: 'cancel'}
                            ]
                        };
                    },
                    hooks: {
                        // triggered when the dialog is shown, this is seperate from user defined onshow
                        onshow: function() {
                            $('#confirm').attr('disabled', 'true');
                        },
                        // triggered when the dialog is closed, this is seperate from user defined onclose
                        onclose: function() {
                            //console.log('cerro');
                        },
                        // triggered when a dialog option gets updated.
                        // IMPORTANT: This will not be triggered for dialog custom settings updates ( use settingUpdated instead).
                        onupdate: function() {
                            //console.log('onupdate');
                        },
                    },
                    callback: function(event) {

                        switch (event.button.key) {
                            case 'ok':
                                var motivo = $("#motivo").val();
                                var textother = $("#textother").val();
                                
                                var promises = ajax.call([
                                    {
                                        methodname: 'format_eabctiles_suspendactivity',
                                        args: {
                                            groupid: window.groupid,
                                            courseid: window.courseid,
                                            motivo: motivo,
                                            textother: textother
                                        }
                                    }
                                ]);
                                var spinner = M.util.add_spinner(Y, Y.Node('.buttom'));
                                spinner.show(); 
                                promises[0].done(function(p) {
                                    alertify.alert('Suspendido con éxito', function(){
                                        location.reload();                            
                                    }).setHeader('Estatus');                                      
                                }).fail(function(ex) {
                                    spinner.hide();
                                    alertify.alert('Error', ex.message, function(){ 
                                        alertify.error('Error: '+ex); 
                                    });
                                    console.log(ex);
                                });
                            
                                break;
                            case 'cancel':
                                alertify.error('Cancelar');
                                break;
                        }
                    },
                };
            });

            $("body").on("click", ".suspend", function() {
                window.groupid = $(this).attr('data-groupid');
                window.courseid = $(this).attr('data-courseid');
                                
                templates.render('format_eabctiles/body_alert_suspend', reasons, reasonsopen)
                        .then(function(html, js) {
                            alertify.customModal(
                                    html
                                    );
                        }).fail(function(ex) {

                });
            });
        }
    };
});