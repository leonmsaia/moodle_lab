define(['jquery', 'core/ajax', 'format_eabctiles/alertify'], function($, ajax, alertify) {
    return {
        init: function() {
            $('.buttom').click(function() {
                var groupid = $(this).attr('data-groupid');
                var courseid = $(this).attr('data-courseid');
                var closegroup = $(this).attr('data-closegroup');
                var msgalart = "";
                
                if(closegroup == 0){
                    msgalart = M.util.get_string('msgalertclose', 'format_eabctiles');
                } else {
                    msgalart = M.util.get_string('msgalertopen', 'format_eabctiles');
                }
                alertify.confirm(msgalart, function(confi){
                    if (confi) {
                        var promises = ajax.call([
                            {methodname: 'format_eabctiles_closeactivity',
                                args: {
                                    groupid: groupid,
                                    courseid: courseid,
                                }
                            }
                        ]);
                        var spinner = M.util.add_spinner(Y, Y.Node('.buttom'));
                        spinner.show();                        
                        promises[0].done(function(p) {
                            alertify.alert('Modificado con éxito', function(){
                                location.reload();                            
                            }).setHeader('Estatus');                            
                            
                            spinner.hide();
                        }).fail(function(ex) {
                            spinner.hide();
                            alertify.alert('Error', ex.message, function(){ 
                                alertify.error('Error: '+ex); 
                            });
                            console.log(ex);
                        });
                    }  
                }, function(cance){
                    if(cance){
                        alertify.error(M.util.get_string('cancel', 'format_eabctiles'));
                    }
                }).set('labels', {
                    ok: M.util.get_string('confirm', 'format_eabctiles'), 
                    cancel:M.util.get_string('cancel', 'format_eabctiles')
                }).setHeader(M.util.get_string('closeactivity', 'format_eabctiles'));
            });
        }
    };
});