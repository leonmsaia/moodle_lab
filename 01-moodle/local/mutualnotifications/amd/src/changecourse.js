define(['jquery'], function($) {
   return {
       init: function(url) {
            $("#id_s_local_mutualnotifications_curso").change(function(){
                var id_s_local_mutualnotifications_curso = $("#id_s_local_mutualnotifications_curso").val();
                console.log("cambio");
                console.log(url);
                window.location.href = url+"&courseid="+id_s_local_mutualnotifications_curso;
            });
         }
    };
});