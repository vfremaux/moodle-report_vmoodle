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
                // $(this).promise().done(reportfragment.fragmentloader(this));
                reportfragment.fragmentloader(this);
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
            url += '&filter=' + that.attr('delegated-filter');

            $.get(url, function(data) {
                var newval;
                log.debug('Feeding '+'tr[delegated-context="' + data.source + '"]');
                $('tr[delegated-context="' + data.source + '"]').html(data.html);

                // find sumators and add results in it.
                for (var field in data.data) {
                    newval = parseInt($('#sumator-' + field).html());
                    log.debug('ADM Vmoodle updating field '+ field);
                    log.debug('ADM origin value '+ newval);
                    $('#sumator-' + field).html(newval + parseInt(data.data[field]));
                }

                // sumator-ratios provide formula to refresh their values.
                // formulas contain non terminal references to sumators ids.
                $('.sumator-ratio').each(function() {
                    var that = $(this);
                    var formula = that.attr('data-formula');
                    var regexp = new RegExp('(sumator-[a-z]+)', 'g');
                    var vars = formula.match(regexp);
                    for (const variable of vars) {
                        varvalue = parseInt($('#' + variable).html());
                        log.debug('replacing ' + variable + ' with ' + varvalue);
                        formula = formula.replace(variable, varvalue);
                    }
                    log.debug('formula ' + formula);
                    var ratioresult = eval(formula);
                    that.html(ratioresult.toLocaleString(undefined, { 
  minimumFractionDigits: 2, 
  maximumFractionDigits: 2 
}) + '%');
                })
            }, 'json');
        }
    };

    return reportfragment;
});