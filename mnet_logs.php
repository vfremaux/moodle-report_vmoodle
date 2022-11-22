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

/**
 * Version info
 *
 * @package     report_vmoodle
 * @category    report
 * @copyright   2015 Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$renderer = $PAGE->get_renderer('report_vmoodle');
require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');
local_vflibs_require_jqplot_libs();

$str = '';

$str .= $OUTPUT->heading(get_string('logs', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_logstats', $CFG->wwwroot.'/admin/report/vmoodle/view.php?view=logstats', '', true);
}

$aggregate = array();
foreach($vhosts as $vhost) {

    $vhost->id = 0 + @$vhost->id;

    $sql = "
        SELECT
            value
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}config_plugins cp
        WHERE 
            plugin = 'tool_log' AND
            name = 'enabled_stores'
    ";

    $logstore = $DB->get_field_sql($sql);

    if ($logstore == 'logstore_standard') {
        $tablename = "logstore_standard_log";
        $timefield = 'timecreated';
        $pkey = "CONCAT(YEAR(FROM_UNIXTIME(timecreated)), '-', MONTH(FROM_UNIXTIME(timecreated)), '-', origin) as id";
        $dimensions = array(
            'period' => "CONCAT(YEAR(FROM_UNIXTIME(timecreated)), '-', MONTH(FROM_UNIXTIME(timecreated))) as period",
            'origin' => 'origin'
        );
    } else {
        $tablename = "log";
        $timefield = 'time';
        $pkey = "CONCAT(YEAR(FROM_UNIXTIME(time)), '-', MONTH(FROM_UNIXTIME(time))) as id";
        $dimensions = array(
            'period' => "CONCAT(YEAR(FROM_UNIXTIME(time)), '-', MONTH(FROM_UNIXTIME(time))) as period",
        );
    }

    $dimensionfields = implode(',', array_values($dimensions));
    $dimensionkeys = implode(',', array_keys($dimensions));

    $sql = "
        SELECT
            $pkey,
            $dimensionfields,
            $timefield,
            COUNT(*) as logs
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}{$tablename}
        GROUP BY
            $dimensionkeys
        ORDER BY
            $timefield
    ";

    $logcurves[$vhost->name] = $DB->get_records_sql($sql);
    foreach ($logcurves[$vhost->name] as $key => $data) {
        $logtotals[$vhost->name] = 0 + @$logtotals[$vhost->name] + $data->logs;
        foreach (array_keys($dimensions) as $dim) {
            if (!isset($aggregate[$vhost->name])) {
                $aggregate[$vhost->name] = array();
            }
            if (!isset($aggregate[$vhost->name][$dim])) {
                $aggregate[$vhost->name][$dim] = array();
            }
            $aggregate[$vhost->name][$dim][date('m-01-Y', $data->$timefield)] = 0 + @$aggregate[$vhost->name][$dim][date('m-01-Y', $data->$timefield)] + $data->logs;
        }
    }
}

// Prepare graph structures

$logsizestr = get_string('logsize', 'report_vmoodle');
$hostnamestr = get_string('hostname', 'report_vmoodle');
$hosturlstr = get_string('url');

$jqplot = report_vmoodle_prepare_graph_structure($logsizestr);

$str .= $OUTPUT->heading(get_string('logsize', 'report_vmoodle'));

$table = new html_table();
$table->head = array("<b>$hostnamestr</b>", "<b>$hosturlsstr</b>", "<b>$logsizestr</b>");
$table->size = array('50%', '30%', '20%');
$table->width = '95%';

foreach ($vhosts as $vhost) {
    $vhostname = $renderer->host_full_name($vhost);
    $table->data[] = array($vhostname, $vhost->vhostname, $logtotals[$vhost->name]);
}

$str .= html_writer::table($table);

$i = 0;
foreach ($vhosts as $vhost) {
    $i++;
    $str .= $OUTPUT->heading($vhost->name);
    if (!empty($aggregate[$vhost->name]['period'])) {
        $sum = 0;
        $graphdata = array();
        foreach ($aggregate[$vhost->name]['period'] as $date => $value) {
            $sum += $value;
            $graphdata[0][] = array($date, $sum);
        }

        $str .= local_vflibs_jqplot_print_graph('plotlog'.$i, $jqplot, $graphdata, 750, 250, 'margin:20px;', true);
    } else {
        $str .= $OUTPUT->notification('no data');
    }
}
