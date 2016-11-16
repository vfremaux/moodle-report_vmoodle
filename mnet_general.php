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

$year = optional_param('year', 2010, PARAM_INT);
$SESSION->vmoodle_stat_distinct_users = optional_param('distinctusers', @$SESSION->vmoodle_stat_distinct_users, PARAM_INT);
$SESSION->vmoodle_stat_allhits = optional_param('allhits', @$SESSION->vmoodle_stat_allhits, PARAM_INT);

$str = '';
$str .= "<form name=\"chooseyearform\">";
for ($i = 0 ; $i < 15 ; $i++) {
    $years[2009 + $i] = 2009 + $i;
}

$distinctchecked = ($SESSION->vmoodle_stat_distinct_users) ? 'checked="checked"' : '';
$individualchecked = (!$SESSION->vmoodle_stat_distinct_users) ? 'checked="checked"' : '';
$allhitschecked = ($SESSION->vmoodle_stat_allhits && !$SESSION->vmoodle_stat_distinct_users) ? 'checked="checked"' : '';

$allhitsdisabled = ($distinctchecked) ? 'disabled' : '';

$str.= html_writer::select($years, 'year', $year, array());
$str .= " <input type=\"radio\" name=\"distinctusers\" value=\"1\" $distinctchecked />";
$str .= get_string('distinctusers', 'report_vmoodle');
$str .= " - <input type=\"radio\" name=\"distinctusers\" value=\"0\" $individualchecked />";
$str .= get_string('individualconnections', 'report_vmoodle');
$str .= " - <input type=\"checkbox\" name=\"allhits\" value=\"0\" $allhitschecked $allhitsdisabled />";
$str .= get_string('allhits', 'report_vmoodle');
$gostr = get_string('apply', 'report_vmoodle');
$str .= " <input type=\"submit\" value=\"$gostr\" />";
$str .= '</form>';

$str .= $OUTPUT->heading(get_string('cnxs', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_general', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
}

$logmanager = get_log_manager();
$readers = $logmanager->get_readers('\core\log\sql_select_reader');
$reader = reset($readers);

if (empty($reader)) {
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
    return;
}

$str .= '<table width="100%"><tr>';

$stdresultarr = array();
$col = 0;
$overall = 0;
$yearlytotalstr = get_string('totalyearly', 'report_vmoodle');
$shortyearlytotalstr = get_string('totalyearlyshort', 'report_vmoodle');
$actionclause = (@$SESSION->vmoodle_stat_allhits) ? '' : " action = '{$loginaction}' AND ";
foreach ($vhosts as $vhost) {
    $str .= '<td valign="top">';
    if ($SESSION->vmoodle_stat_distinct_users) {
        $sql = "
            SELECT
                MONTH(FROM_UNIXTIME({$timefield})) as month,
                COUNT(DISTINCT userid) as cnxs
            FROM
                `{$vhost->vdbname}`.{$vhost->vdbprefix}{$logtable}
            WHERE
                $actionclause
                YEAR( FROM_UNIXTIME({$timefield})) = $year
            GROUP BY
                MONTH( FROM_UNIXTIME({$timefield}))
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

    $str .= '<table width="100%" class="generaltable">';
    $str .= '<tr>';
    $str .= '<th colspan="2" class="header c0" style="line-height:20px" >'.$vhost->name.'</th>';
    $str .= '</tr>';

    $yearly = 0;
    $r = 0;
    $overalhostname = array();
    if ($connections = $DB->get_records_sql($sql)) {
        for ($m = 1 ; $m <= 12 ; $m++) {
            $cnxs[$vhost->name][$m] = 0 + @$connections[$m]->cnxs;
            $yearly = $yearly + $cnxs[$vhost->name][$m];
            $overall += $cnxs[$vhost->name][$m];
            $overalmonthly[$m] = @$overalmonthly[$m] + $cnxs[$vhost->name][$m];
            $str .= '<tr class="row r'.$r.'">';
            $str .= '<td width="80%" class="cell c0" style="border:1px solid #808080">'.$m.'</td>';
            $str .= '<td width="20%" class="cell c1" style="border:1px solid #808080">'.$cnxs[$vhost->name][$m].'</td>';
            $str .= '</tr>';
            $r = ($r + 1) % 2;
            $stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $m, $cnxs[$vhost->name][$m]);
        }
    }
    if ($SESSION->vmoodle_stat_distinct_users) {
        $sql = "
            SELECT
                COUNT(DISTINCT userid) as cnxs
            FROM
                `{$vhost->vdbname}`.{$vhost->vdbprefix}{$logtable}
            WHERE
                ACTION = 'login' AND
                YEAR( FROM_UNIXTIME({$timefield})) = $year
        ";
        $totaldistinct = $DB->count_records_sql($sql);
        $overalhostname[$vhost->name] = $totaldistinct;
        $str .= '<tr class="row r'.$r.'">';
        $str .= '<td width="80%" class="cell c0" style="line-height:20px">'.$shortyearlytotalstr.'</td>';
        $str .= '<td width="20%" class="cell c1" style="font-weight:bolder;border:1px solid #808080">'.$totaldistinct.'</td>';
        $str .= '</tr>';
    } else {
        $str .= '<tr class="row r'.$r.'">';
        $str .= '<td width="80%" class="cell c0" style="line-height:20px">'.$shortyearlytotalstr.'</td>';
        $str .= '<td width="20%" class="cell c1" style="font-weight:bolder;border:1px solid #808080">'.$yearly.'</td>';
        $str .= '</tr>';
    }
    $str .= '</table></td>';

    $col++;
    if ($col >= 4) {
        $str .= '</tr><tr>';
        $col = 0;
    }
}

$str .= '</tr></table>';

$str .= $OUTPUT->heading(get_string('graphs', 'report_vmoodle'), 2);

// Print monthly graphs.
$jqplot = report_vmoodle_prepare_graph_structure(get_string('events', 'report_vmoodle'));

$i = 0;
foreach ($vhosts as $vhost) {
    $i++;
    $str .= $OUTPUT->heading($vhost->name, 3);
    if (!empty($cnxs[$vhost->name])) {
        $graphdata = array();
        foreach ($cnxs[$vhost->name] as $m => $value) {
            $graphdata[0][] = array(sprintf('%02d', $m).'-01-'.$year, $value);
        }

        $str .= local_vflibs_jqplot_print_graph('plotlog'.$i, $jqplot, $graphdata, 750, 250, 'margin:20px;', true);
    } else {
        $str .= $OUTPUT->notification('no data');
    }
}

// Print overall results.

$str .= $OUTPUT->heading(get_string('totalcnxs', 'report_vmoodle'), 2);

$overalmonthlystr = get_string('totalmonthly', 'report_vmoodle');
$str .= '<table width="250" class="generaltable">';
$str .= '<tr>';
$str .= '<th colspan="2" class="header c0" style="line-height:20px;">'.$overalmonthlystr.'</th>';
$str .= '</tr>';

$r = 0;
for ($m = 1 ; $m <= 12 ; $m++) {
    $om = 0 + @$overalmonthly[$m];
    $str .= '<tr class="row r'.$r.'">';
    $str .= '<td class="cell c0" style="border:1px solid #808080">'.$m.'</td>';
    $str .= '<td class="cell c1" style="border:1px solid #808080">'.$om.'</td>';
    $str .= '</tr>';
    $r = ($r + 1) % 2;
}
if ($SESSION->vmoodle_stat_distinct_users) {
    $overall = array_sum(array_values($overalhostname));
}
$str .= '<tr class="row r'.$r.'">';
$str .= '<td class="cell c0" style="border:1px solid #808080">'.$yearlytotalstr.'</td>';
$str .= '<td class="cell c1" style="border:1px solid #808080">'.$overall.'</td>';
$str .= '</tr>';
$str .= '</table>';
$str .= '</td>';

$str .= $OUTPUT->heading(get_string('graphs', 'report_vmoodle'), 2);

$graphdata = array();
if (!empty($overalmonthly)) {
    foreach ($overalmonthly as $m => $value) {
        $graphdata[0][] = array(sprintf('%02d', $m).'-01-'.$year, $value);
    }
    $str .= local_vflibs_jqplot_print_graph('plotlog'.$i, $jqplot, $graphdata, 750, 250, 'margin:20px;', true);
}

