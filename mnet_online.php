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

$str .= $OUTPUT->heading(get_string('onlineusers', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_onlineusers', $CFG->wwwroot.'/admin/report/vmoodle/view.php?view=online', '', true);
}

foreach($vhosts as $vhost) {

    $vhost->id = 0 + @$vhost->id;

    $sql = "
        SELECT
            value
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}config c
        WHERE 
            name = ?
    ";
    $localhostid = $DB->get_field_sql($sql, array('mnet_localhost_id'));

    $csql = "
        SELECT
            COUNT(u.id)
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}user u
        WHERE
            u.lastaccess > :timefrom AND
            u.lastaccess <= :now AND
            u.deleted = 0 AND
            mnethostid = :localhostid
    ";

    $now = time();
    $params['timefrom'] = $now - MINSECS * 5;
    $params['now'] = $now;
    $params['localhostid'] = $localhostid;

    $csql2 = "
        SELECT
            COUNT(u.id)
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}user u
        WHERE
            u.deleted = 0 AND
            mnethostid = :localhostid
    ";

    $timetoshowusers = 300; //Seconds default
    $now = time();
    $timefrom = 100 * floor(($now - $timetoshowusers) / 100); // Round to nearest 100 seconds for better query cache

    $maxusers = 0;
    $onlineuserscount = 0 + $DB->get_field_sql($csql, $params);
    $onlineusers[$vhost->id] = $onlineuserscount;
    $maxusers = max($maxusers, $onlineusers[$vhost->id]);

    $alluserscount = 0 + $DB->get_field_sql($csql2, $params);
    $allusers[0 + $vhost->id] = $alluserscount;
}

$table = new html_table();
$table->head = array(get_string('hostname', 'report_vmoodle'), get_string('usercount', 'report_vmoodle'), '', get_string('onlineratio', 'report_vmoodle'));
$table->size = array('30%', '10%', '50%', '10%');
$table->width = '90%';

foreach ($vhosts as $vhost) {
    $vhost->id = 0 + $vhost->id;
    $online = $onlineusers[$vhost->id];
    $onlineratio = (@$allusers[$vhosts->id]) ? sprintf("%0.1d", $online / $allusers[$vhosts->id]) : 0;
    $table->data[] = array($renderer->host_full_name($vhost), $online, $renderer->graphbar($online, $maxusers), $onlineratio);
}

$str .= html_writer::table($table);