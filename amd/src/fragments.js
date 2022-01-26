// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *
 * @module     report_vmoodle
 * @package    report
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// jshint unused: true, undef:true
define(['jquery', 'core/config', 'core/log'], function ($, cfg, log) {

    var reportfragment = {
        init: function() {

            // Attach to each expected fragment slot an asynchronous loading.
            $('.delegated-content').each(function() {
                $(this).promise().done(reportfragment.fragmentloader(this));
            });

            log.debug('ADM Vmoodle Report Report fragment initialized');
        },

        /*
         * This function launches asyncronous progressive loading. One instance
         * of this function gets the content of one content slot.
         */
        fragmentloader: function(elm) {
            var that = $(elm);

            var url = cfg.wwwroot + '/report/vmoodle/ajax/service.php';
            url += '?what=getfragment';
            url += '&fragment=' + that.attr('delegated-fragment');
            url += '&wwwroot=' + that.attr('delegated-context');

            $.get(url, function(data) {
                var newval;
                log.debug('Feeding '+'tr[delegated-context="' + data.source + '"]');
                $('tr[delegated-context="' + data.source + '"]').html(data.html);

                // find sumators and add results in it.
                for (var field in data.data) {
                    newval = parseInt($('#sumator-' + field).html());
                    log.debug('ADM Vmoodle updating field '+ field);
                    log.debug('ADM orgin value '+ newval);
                    $('#sumator-' + field).html(newval + parseInt(data.data[field]));
                }
            }, 'json');
        }
    };

    return reportfragment;
});