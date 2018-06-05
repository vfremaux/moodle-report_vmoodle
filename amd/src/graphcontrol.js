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
define(['jquery', 'core/str', 'core/config', 'core/log'], function ($, str, cfg, log) {

    var state = true;
    var mtdstrings;

    var graphcontrol = {
        init: function() {
            $('#report-vmoodle-togglegraph-handle').bind('click', this.toggle_graphs);

            var stringsreq = [

                {
                    key: 'hidegraphs',
                    component: 'report_vmoodle'
                },
                {
                    key: 'showgraphs',
                    component: 'report_vmoodle'
                },
            ];

            str.get_strings(stringsreq).done(function(strings) {
                mtdstrings = strings;
            });

            log.debug('ADM Vmoodle Report Graphcontrol initialized');
        },

        toggle_graphs: function() {

            if (state) {
                state = false;
                $('.report-vmoodle-user-charts').css('display', 'none');
                $('#report-vmoodle-togglegraph-handle').attr('value', mtdstrings[1]);
            } else {
                state = true;
                $('.report-vmoodle-user-charts').css('display', 'block');
                $('#report-vmoodle-togglegraph-handle').attr('value', mtdstrings[0]);
            }
        }
    };

    return graphcontrol;
});