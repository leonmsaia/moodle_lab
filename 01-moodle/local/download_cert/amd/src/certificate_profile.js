define(['jquery', 'core/templates', 'core/config', 'core/ajax'], function($, templates, config, ajax){
    return {
        init: function() {
            var promises = ajax.call([
                { methodname: 'local_download_cert_get_certs',
                    args: {
                        
                    }
                }
            ]);
            //seteo la data al tabulator
            promises[0].done(function(response) {
                if(response != []){
                    var context_elearning = {
                        elearning: response.elearning.elearning,
                        cfg: config,
                        ilerningbool: response.elearning.elearningbool,
                        presencial: response.presencial.presenciales,
                        presencialbool: response.presencial.presencialbool
                    };
                    templates.render("local_download_cert/link_certificate", context_elearning).then(function(html, js) {
                        templates.prependNodeContents(".profile_tree", html, js);
                    }).fail(function(ex) {
                    // Deal with this exception (I recommend core/notify exception function for this).
                    });
                }
            }).fail(function(ex) {
                console.log('errr');
                console.error(ex);
            });
        }
    }
})