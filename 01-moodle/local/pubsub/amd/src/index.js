define(['jquery',  'core/ajax'], function($, ajax){
    return {
        init: function(id_curso) {
            //declaro el tabulator con sus variables
            // var table = new Tabulator("#tabulator-table", {
            //     layout:"fitColumns", //fit columns to width of table (optional)
            //     columns:[ //Define Table Columns
            //             {title: M.util.get_string('nombrecurso', 'local_resumencursos'), field:"nombrecurso"},
            //             {title: M.util.get_string('calificacion', 'local_resumencursos'), field:"calificacion"},
            //             {title: M.util.get_string('duracion', 'local_resumencursos'), field:"duracion"},
            //             {title: M.util.get_string('direccion', 'local_resumencursos'), field:"direccion",},
            //             {title: M.util.get_string('finalizacion', 'local_resumencursos'), field:"finalizacion",},
            //             {title: M.util.get_string('actividad', 'local_resumencursos'), field:"actividad",},
            //             {title: M.util.get_string('caducidad', 'local_resumencursos'), field:"caducidad",},
            //             {title: M.util.get_string('valoracion', 'local_resumencursos'), field:"valoracion",},
            //             {title: M.util.get_string('disponibilidad', 'local_resumencursos'), field:"disponibilidad",},
            //             {title: M.util.get_string('sessdate', 'local_resumencursos'), field:"sessdate",},
            //             {title: M.util.get_string('status', 'local_resumencursos'), field:"status",},
            //             {title: M.util.get_string('time', 'local_resumencursos'), field:"time",},
            //     ],
            //     rowClick:function(e, row){ //trigger an alert message when the row is clicked
            //             alert("Row " + row.getData().id + " Clicked!!!!");
            //     },

            // });
            // //creo la promesa para llamar al ws con la data de la tabla
            // var promises = ajax.call([
            //     { methodname: 'local_resumencursos_get_data_sumary',
            //         args: {
            //             courseid: id_curso
            //         }
            //     }
            // ]);
            // //seteo la data al tabulator
            // promises[0].done(function(response) {
            //     table.setData(response);
            // }).fail(function(ex) {
            //     console.error(ex);
            // });
            
            // //click sobre el boton de descargar
            // $('#download-csv').click(function(){
            //     table.download('csv', 'data.csv');
            // });
        }
    }
})