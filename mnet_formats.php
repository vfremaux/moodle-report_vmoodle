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

defined('MOODLE_INTERNAL') || die();

/**
 * Version info
 *
 * @package    report_vmoodle
 * @category   report
 * @copyright  2012 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/report/vmoodle/locallib.php');

$year = optional_param('year', 0, PARAM_INT);

$str = '';
$str .= "<form name=\"chooseyearform\">";
$years[] = get_string('whenever', 'report_vmoodle');
for ($i = 0 ; $i < 15 ; $i++) {
    $years[2009 + $i] = 2009 + $i;
}

$str .= get_string('addeddate', 'report_vmoodle');
$str .= html_writer::select($years, 'year', $year, array());
$gostr = get_string('apply', 'report_vmoodle');
$str .= " <input type=\"hidden\" name=\"view\" value=\"formats\" />";
$str .= " <input type=\"submit\" value=\"$gostr\" />";
$str .= '</form>';

$str .= $OUTPUT->heading(get_string('formats', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_formats', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
}

$col = 0;
$overall = 0 ;
$totalstr = get_string('totalformats', 'report_vmoodle');
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$networktotalstr = get_string('networktotal', 'report_vmoodle');

$yearclause = '';
if (!empty($year)) {
    $yearclause = " AND YEAR( FROM_UNIXTIME(c.timecreated)) = $year ";
}

$hostnamestr = get_string('hostname', 'report_vmoodle');

$table = new html_table();
$table->head = array($hostnamestr); // To be completed later.
$table->width = '95%'; // To be completed later.
$table->align = array('left');
$table->size = array('25%');
$table2 = new html_table();
$table2->head = array(''); // To be completed later.
$table2->width = '95%'; // To be completed later.
$table2->align = array('left');
$table2->size = array('25%');

$totformats = array();
$tothostcourses = array();

foreach ($vhosts as $vhost) {

    $vhostname = $renderer->host_full_name($vhost);

    $sql = "
        SELECT
            c.format as format,
            COUNT(*) as formatcount
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}course c
        WHERE
            1 = 1
            $yearclause
        GROUP
            BY c.format
        ORDER BY
            c.format
    ";

    if ($formats = $DB->get_records_sql($sql)) {
        foreach ($formats as $c) {
            if ($c->format != 'site') {
                $formatname = get_string('pluginname', 'format_'.$c->format);
                if (strpos($formatname, '[[') !== false) {
                    $formatname = get_string('format'.$format);
                }
                if (strpos($formatname, '[[') !== false) {
                    $formatname = get_string($format);
                }
            } else {
                $formatname = get_string('site');
            }

            $results[$vhostname][$formatname]  = $c->formatcount;
            @$totformats[$formatname] += 0 + $c->formatcount;
            @$tothosts[$vhostname] += 0 + $c->formatcount;
        }
    }
}

if (!empty($results)) {

    $numformats = count(array_keys($totformats));
    $widthinc = floor(75 / ($numformats + 1));
    $formatnames = array_keys($totformats);
    for ($i = 0 ; $i < $numformats ; $i++) {
        $table->head[] = $formatnames[$i];
        $table->align[] = 'center';
        $table->size[] = $widthinc;
        $table2->head[] = $formatnames[$i];
        $table2->align[] = 'center';
        $table2->size[] = $widthinc;
    }
    $table->head[] = get_string('totalformats', 'report_vmoodle');
    $table->align[] = 'center';
    $table->size[] = $widthinc;
    $table2->head[] = get_string('totalformats', 'report_vmoodle');
    $table2->align[] = 'center';
    $table2->size[] = $widthinc;

    foreach ($results as $vhostname => $formatarr) {
        $sum = 0;
        $row = array($vhostname);
        foreach ($formatnames as $fname) {
            $fcount = $renderer->format_number(0 + @$formatarr[$fname]);
            $row[] = $fcount;
            $sum += $fcount;
        }
        $row[] = $sum;
        $table->data[] = $row;
    }

    $str .= html_writer::table($table);

} else {
    $str .= $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
}

$str .= $OUTPUT->heading(get_string('totalformatsuses', 'report_vmoodle'), 2);

if (!empty($results)) {
    $row = array(get_string('allnodes', 'report_vmoodle'));
    $sum = 0;
    foreach ($totformats as $formatname => $formatcount) {
        $row[] = $renderer->format_number($formatcount);
        $sum += $formatcount;
    }
    $row[] = $sum;
    $table2->data = array($row);

    $str .= html_writer::table($table2);
} else {
    $str .= $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
}