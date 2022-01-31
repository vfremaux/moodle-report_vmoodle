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

$year = optional_param('year', date('Y'), PARAM_INT);
$SESSION->vmoodle_stat_distinct_users = optional_param('distinctusers', @$SESSION->vmoodle_stat_distinct_users, PARAM_INT);
$SESSION->vmoodle_stat_allhits = optional_param('allhits', @$SESSION->vmoodle_stat_allhits, PARAM_INT);

$distinctchecked = ($SESSION->vmoodle_stat_distinct_users) ? 'checked="checked"' : '';
$individualchecked = (!$SESSION->vmoodle_stat_distinct_users) ? 'checked="checked"' : '';
$allhitschecked = (!empty($SESSION->vmoodle_stat_allhits)) ? 'checked="checked"' : '';

$allhitsdisabled = ($distinctchecked) ? 'disabled' : '';

$additions = " <input type=\"radio\" name=\"distinctusers\" value=\"1\" $distinctchecked />";
$additions .= get_string('distinctusers', 'report_vmoodle');
$additions .= " - <input type=\"radio\" name=\"distinctusers\" value=\"0\" $individualchecked />";
$additions .= get_string('individualconnections', 'report_vmoodle');
$additions .= " - <input type=\"checkbox\" name=\"allhits\" value=\"1\" $allhitschecked $allhitsdisabled />";
$additions .= get_string('allhits', 'report_vmoodle');

$str = '';
$str .= $renderer->filter_form($additions, false);

$str .= $OUTPUT->heading(get_string('cnxs', 'report_vmoodle'), 2);

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
$overall = 0;
$yearlytotalstr = get_string('totalyearly', 'report_vmoodle');
$shortyearlytotalstr = get_string('totalyearlyshort', 'report_vmoodle');
$actionclause = (@$SESSION->vmoodle_stat_allhits) ? '' : " action = '{$loginaction}' AND ";

$hostnamestr = get_string('hostname', 'report_vmoodle');
$jan = get_string('january', 'report_vmoodle');
$feb = get_string('february', 'report_vmoodle');
$mar = get_string('march', 'report_vmoodle');
$apr = get_string('april', 'report_vmoodle');
$jun = get_string('june', 'report_vmoodle');
$jul = get_string('july', 'report_vmoodle');
$aug = get_string('august', 'report_vmoodle');
$sep = get_string('september', 'report_vmoodle');
$oct = get_string('october', 'report_vmoodle');
$nov = get_string('november', 'report_vmoodle');
$dec = get_string('december', 'report_vmoodle');
$totalstr = get_string('year', 'report_vmoodle');

$table = new html_table();
$table->head = array($hostnamestr, $jan, $feb, $mar, $apr, $jun, $jul,  $aug, $sep, $oct, $nov, $dec, $totalstr);
$table->size = array('25%', '5%', '5%', '5%', '5%', '5%', '5%', '5%', '5%', '5%', '5%', '5%', '5%', '5%');
$table->align = array('left', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center',
                      'center', 'center', 'center', 'center', 'center');

foreach ($vhosts as $vhost) {

    $vhostname = $renderer->host_full_name($vhost);

    if ($SESSION->vmoodle_stat_distinct_users) {
        $sql = "
            SELECT
                CONCAT(MONTH(FROM_UNIXTIME({$timefield})), '-', MONTH(FROM_UNIXTIME({$timefield}))) as month,
                COUNT(DISTINCT userid) as cnxs
            FROM
                `{$vhost->vdbname}`.{$vhost->vdbprefix}{$logtable}
            WHERE
                $actionclause
                $yearclause
            GROUP BY
                YEAR( FROM_UNIXTIME({$timefield})), MONTH( FROM_UNIXTIME({$timefield}))
            ORDER BY
                month
            ";
    } else {
        $sql = "
            SELECT
                MONTH(FROM_UNIXTIME({$timefield})) as month,
                COUNT(*) as cnxs
            FROM
                `{$vhost->vdbname}`.{$vhost->vdbprefix}{$logtable}
            WHERE
                $actionclause
                YEAR( FROM_UNIXTIME({$timefield})) = $year
            GROUP
                BY MONTH( FROM_UNIXTIME({$timefield}))
            ORDER BY
                month
        ";
    }

    $row = array();
    $row[] = $vhostname;

    $yearly = 0;
    $r = 0;
    $overalhostname = array();
    if ($connections = $DB->get_records_sql($sql)) {
        for ($m = 1 ; $m <= 12 ; $m++) {
            $cnxs[$vhost->name][$m] = 0 + @$connections[$m]->cnxs;
            $yearly = $yearly + $cnxs[$vhost->name][$m];
            $overall += $cnxs[$vhost->name][$m];
            $overalmonthly[$m] = @$overalmonthly[$m] + $cnxs[$vhost->name][$m];
            $row[] = $cnxs[$vhost->name][$m];
            $stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $m, $cnxs[$vhost->name][$m]);
        }
        $row[] = $yearly;
        $table->data[] = $row;
    }
}

if (!empty($table->data)) {
    $str .= html_writer::table($table);
} else {
    $str .= $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
}

$str .= $OUTPUT->heading(get_string('graphs', 'report_vmoodle'), 2);

// Print monthly graphs.
$jqplot = report_vmoodle_prepare_graph_structure(get_string('events', 'report_vmoodle'));

$i = 0;
foreach ($vhosts as $vhost) {
    $i++;
    $str .= $OUTPUT->heading($renderer->host_full_name($vhost), 3);
    if (!empty($cnxs[$vhost->name])) {
        $graphdata = array();
        foreach ($cnxs[$vhost->name] as $m => $value) {
            $graphdata[0][] = array(sprintf('%02d', $m).'-01-'.$year, $value);
        }

        $str .= local_vflibs_jqplot_print_graph('plotlog'.$i, $jqplot, $graphdata, 750, 250, 'margin:20px;', true);
    } else {
        $str .= $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
    }
}

// Print overall results.

$str .= $OUTPUT->heading(get_string('totalcnxs', 'report_vmoodle'), 2);

if (!empty($table->data)) {
    $row = array();
    $row[] = get_string('totalmonthly', 'report_vmoodle');

    $overall = 0;
    for ($m = 1 ; $m <= 12 ; $m++) {
        $om = 0 + @$overalmonthly[$m];
        $overall += $om;
        $row[] = $om;
    }
    $row[] = $overall; // Full total.
    // Recycle the first table.
    $table->head[0] = '';
    $table->data = array($row);

    $str .= html_writer::table($table);
} else {
    $str .= $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
}

$str .= $OUTPUT->heading(get_string('graphs', 'report_vmoodle'), 2);

$graphdata = array();
if (!empty($overalmonthly)) {
    foreach ($overalmonthly as $m => $value) {
        $graphdata[0][] = array(sprintf('%02d', $m).'-01-'.$year, $value);
    }
    $str .= local_vflibs_jqplot_print_graph('plotlog'.$i, $jqplot, $graphdata, 750, 250, 'margin:20px;', true);
}
