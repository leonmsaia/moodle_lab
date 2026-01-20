define(['jquery', 'jquery', 'local_showallactivities/doubleScroll'], function($, jQuery, doubleScroll) {
    return {
        init: function() {
            $('.showfilters').hide();
            $('.toggle-filter').click(function() {
                $('.showfilters').toggle();
            });
            $(document).ready(function() {
                $('.double-scroll').doubleScroll();
                $('.no-overflow').doubleScroll({
                    resetOnWindowResize: true
                });
            });
        }
    };
});