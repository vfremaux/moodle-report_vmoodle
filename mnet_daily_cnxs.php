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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version info
 *
 * @package    report_vmoodle
 * @category   report
 * @copyright  2012 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/report/vmoodle/locallib.php');
require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');
local_vflibs_require_jqplot_libs();

$PAGE->requires->js_call_amd('report_vmoodle/report_vmoodle', 'init');

$str .= $OUTPUT->heading(get_string('dailystats', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_general', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
}

$logmanager = get_log_manager();
$readers = $logmanager->get_readers('\core\log\sql_reader');
$reader = reset($readers);

if (empty($reader)) {
    $str .= $OUTPUT->notification(get_string('nologreader', 'report_vmoodle'));
    return false; // No log reader found.
}

if ($reader instanceof \logstore_standard\log\store) {
    $loginaction = 'loggedin';
    $timefield = 'timecreated';
    $logtable = 'logstore_standard_log';
} else if ($reader instanceof \logstore_legacy\log\store) {
    $loginaction = 'login';
    $logtable = 'log';
    $timefield = 'time';
} else {
    $class = get_class($reader);
    $str .= $OUTPUT->notification(get_string('nologstore', 'report_vmoodle', $class));
    return;
}

$stdresultarr = array();
$col = 0;

$hostnamestr = get_string('hostname', 'report_vmoodle');
$totalstr = get_string('overall', 'report_vmoodle');

$minday = '';
$maxday = '99-99';

$dcnxs = array();
$duniquecnxs = array();
$dsessions = array();

$dtotalcnx = array();
$dtotaluniquecnx = array();
$dtotalsesssions = array();

$totalcnx = 0;
$totaluniquecnx = 0;
$totalsesssions = 0;

$statdays = array();

foreach ($vhosts as $vhost) {

    $sql = "
        SELECT
            FROM_UNIXTIME({$timefield}, \"%m-%d\") as day,
            COUNT(DISTINCT userid) as uniquecnxs,
            COUNT(*) as cnxs,
            SUM(CASE WHEN action = '{$loginaction}' THEN 1 ELSE 0 END) as sessions
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}{$logtable}
        WHERE
            {$timefield} > UNIX_TIMESTAMP() - (24*3600 * 30 * 2)
        GROUP BY
            FROM_UNIXTIME({$timefield}, \"%m-%d\")
        ORDER BY
            day
    ";

    if ($dailycons = $DB->get_records_sql($sql)) {
        foreach ($dailycons as $con) {
            $data = $con->cnxs;
            $totalcnx = $totalcnx + $data;
            $dtotalcnx[$con->day] = $dtotalcnx[$con->day] + $data;
            $dcnx[$vhost->shortname][$con->day] = $data;

            $data = $con->uniquecnxs;
            $totaluniquecnx = $totaluniquecnx + $data;
            $dtotaluniquecnx[$con->day] = $dtotaluniquecnx[$con->day] + $data;
            $duniquecnx[$vhost->shortname][$con->day] = $data;

            $data = $con->sessions;
            $totalsessions = $totalsessions + $data;
            $dtotalsessions[$con->day] = $dtotalsessions[$con->day] + $data;
            $dsessions[$vhost->shortname][$con->day] = $data;

            if (!in_array($con->day, $statdays)) {
                $statdays[] = $con->day;
            }
        }
    }
}

// Fix empty cells values
if (!empty($statdays)) {
    sort($statdays);
} else {
    echo $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
    return;
}

$template = new StdClass;
$template->h1 = $OUTPUT->heading(get_string('dailycnxs', 'report_vmoodle'), 3, 'report-vmoodle-panehandle');

$table = new html_table();
$table->head = array($hostnamestr);
$table->align = array('left');
$table->size = array(10);
$numdays = count($statdays);
foreach ($statdays as $d) {
    $table->head[] = $d;
    $table->align[] = 'center';
    $table->size[] = 90 / $numdays;
}

foreach ($vhosts as $vhost) {
    $vhostname = $renderer->host_full_name($vhost);
    $row = array($vhostname);
    foreach ($statdays as $d) {
        $data = 0 + @$dcnxs[$vhost->shortname][$d];
        $row[] = ($data) ? '<b>'.$data.'</b>' : '-';
    }
    $stdresultarr[] = $row;
    $table->data[] = $row;
}
$lastrow = array(get_string('daytot', 'report_vmoodle'));
foreach ($statdays as $d) {
    $data = 0 + @$dtotalcnxs[$d];
    $row[] = ($data) ? '<b>'.$data.'</b>' : '-';
}

$stdresultarr[] = [];

$template->t1 = html_writer::table($table);

$template->h2 = $OUTPUT->heading(get_string('dailyuniquecnxs', 'report_vmoodle'), 3, 'report-vmoodle-panehandle');

$table = new html_table();
$table->head = array($hostnamestr);
$table->align = array('left');
$table->size = array(10);
$numdays = count($statdays);
foreach ($statdays as $d) {
    $table->head[] = $d;
    $table->align[] = 'center';
    $table->size[] = 90 / $numdays;
}

foreach ($vhosts as $vhost) {
    $vhostname = $renderer->host_full_name($vhost);
    $row = array($vhostname);
    foreach ($statdays as $d) {
        $data = 0 + @$duniquecnxs[$vhost->shortname][$d];
        $row[] = ($data) ? '<b>'.$data.'</b>' : '-';
    }
    $stdresultarr[] = $row;
    $table->data[] = $row;
}
$lastrow = array(get_string('daytot', 'report_vmoodle'));
foreach ($statdays as $d) {
    $data = 0 + @$dtotaluniquecnxs[$d];
    $row[] = ($data) ? '<b>'.$data.'</b>' : '-';
}

$template->t2 = html_writer::table($table);
$stdresultarr[] = [];

$template->h3 = $OUTPUT->heading(get_string('dailysessions', 'report_vmoodle'), 3, 'report-vmoodle-panehandle');

$table = new html_table();
$table->head = array($hostnamestr);
$table->align = array('left');
$table->size = array(10);
$numdays = count($statdays);
foreach ($statdays as $d) {
    $table->head[] = $d;
    $table->align[] = 'center';
    $table->size[] = 90 / $numdays;
}

foreach ($vhosts as $vhost) {
    $vhostname = $renderer->host_full_name($vhost);
    $row = array($vhostname);
    foreach ($statdays as $d) {
        $data = 0 + @$dsessions[$vhost->shortname][$d];
        $row[] = ($data) ? '<b>'.$data.'</b>' : '-';
    }
    $stdresultarr[] = $row;
    $table->data[] = $row;
}
$lastrow = array(get_string('daytot', 'report_vmoodle'));
foreach ($statdays as $d) {
    $data = 0 + @$dtotalsessions[$d];
    $row[] = ($data) ? '<b>'.$data.'</b>' : '-';
}

$template->t3 = html_writer::table($table);

$str = $OUTPUT->render_from_template('report_vmoodle/daily_cnxs', $template);
