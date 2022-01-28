
define(['jquery', 'core/log'], function ($, log) {

    var reportvmoodle = {

        init: function() {
            $('.report-vmoodle-panehandle').bind('click', this.toggle_panel);
        },

        toggle_panel: function() {
            var that = $(this);

            $('.report-vmoodle-panel').css('display', 'none');
            $('#panel' + that.attr('data-id')).css('display', 'block');
            log.debug("AMD report vmoodle initialisation");
        }
    };

    return reportvmoodle;
});