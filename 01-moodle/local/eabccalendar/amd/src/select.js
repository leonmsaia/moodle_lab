define(["local_eabccalendar/main"], 
  function(main) {
    return {
        init: function(iduser) {
            var selected = document.getElementById('id_selcurso'); 
           
                selected.onchange = function () {  
                    var options = '';                    
                    for ( var i = 0; i < selected.selectedOptions.length; i++) {
                        options += selected.selectedOptions[i].value +',';
                    }
                    var calendarEl = document.getElementById('calendar');
                    calendarEl.innerHTML = '';
                    main.init(iduser,options);
                    
                }

        }
    };
});