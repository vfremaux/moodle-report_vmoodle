<?php
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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Version info
 *
 * @package     report_vmoodle
 * @category    report
 * @copyright   2015 Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$renderer = $PAGE->get_renderer('report_vmoodle');

$str = '';

$str .= $OUTPUT->heading(get_string('syncusers', 'local_ent_installer'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_usersync', $CFG->wwwroot.'/admin/report/vmoodle/view.php?view=usersync', '', true);
}

foreach($vhosts as $vhost) {

    $vhost->id = 0 + @$vhost->id;

    $sql = "
        SELECT
            MAX(timestart) as maxrun
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}local_ent_installer lei
    ";
    $lastrun[$vhost->id] = $DB->get_field_sql($sql, array());
}

$table = new html_table();
$table->head = array(get_string('hostname', 'report_vmoodle'), get_string('lasttime', 'local_ent_installer'));
$table->size = array('70%', '30%');
$table->width = '90%';

$now = time();

foreach ($vhosts as $vhost) {
    $vhost->id = 0 + $vhost->id;
    $runfromdelay = $now - $lastrun[$vhost->id];
    if ($runfromdelay <= DAYSECS) {
        $lastrunstr = '<div class="vmoodle-report-normal">'.format_time($runfromdelay).'</div>';
    } else if ($runfromdelay <= DAYSECS * 2) {
        $lastrunstr = '<div class="vmoodle-report-warning">'.format_time($runfromdelay).'</div>';
    } else {
        $lastrunstr = '<div class="vmoodle-report-error">'.format_time($runfromdelay).'</div>';
    }
    $table->data[] = array($renderer->host_full_name($vhost), $lastrunstr);
}

$str .= html_writer::table($table);