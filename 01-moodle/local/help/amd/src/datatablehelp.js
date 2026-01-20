define(['jquery', 'local_help/dataTables'], function($, d) {
    return {
        init: function() {
            $(document).ready(function () {
                $("#faqtable").DataTable({
                    "order": [[0, "asc"]],
                    "language":{ 
                	"url": "//cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json"
                }
                });
            });
        }
    }
});