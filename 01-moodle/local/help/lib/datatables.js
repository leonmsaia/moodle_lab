define(['jquery'], function($) {
    require(['core/loader'], function() {
        // DataTables ya se autoinicializa con jQuery
    });
    return $.fn.dataTable;
});