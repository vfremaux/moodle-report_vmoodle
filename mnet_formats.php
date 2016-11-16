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

if (!defined('MOODLE_INTERNAL')) {
    die('You cannot access this script directly');
}

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

$str .= "<table width=\"100%\"><tr>";

$col = 0;
$overall = 0 ;
$totalstr = get_string('totalformats', 'report_vmoodle');
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$networktotalstr = get_string('networktotal', 'report_vmoodle');

$yearclause = '';
if (!empty($year)) {
    $yearclause = " AND YEAR( FROM_UNIXTIME(c.timecreated)) = $year ";
}

$stdresultarr = array();

foreach ($vhosts as $vhost) {
    $totformats = 0;
    $str .= "<td valign=\"top\">";
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

    $str .= '<table width="100%" class="generaltable">';
    $str .= '<tr><th colspan="2" class="header c0 report-vmoodle" >'.$vhost->name.'</th></tr>';

    $r = 0;
    if ($formats = $DB->get_records_sql($sql)) {
        foreach ($formats as $c) {
            $formatname = get_string('pluginname', 'format_'.$c->format);
            if (strpos($formatname, '[[') !== false) {
                $formatname = get_string('format'.$format);
            }
            if (strpos($formatname, '[[') !== false) {
                $formatname = get_string($format);
            }

            $str .= '<tr class="row r'.$r.'"><td width="80%" class="cell c0 report-vmoodle">'.$formatname.'</td><td width="20%" class="cell c1 report-vmoodle">'.$c->formatcount.'</td></tr>';
            $totformats = 0 + $c->formatcount + @$totformats;
            $allnodes[$c->format] = 0 + $c->formatcount + @$allnodes[$c->format];
            $r = ($r + 1) % 2;
            $stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $formatname, $c->formatcount);
        }
    }
    $str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totformats}</td></tr>";
    $str .= '</table></td>';

    $col++;
    if ($col >= 4) {
        $str .= '</tr><tr>';
        $col = 0;
    }
}

$str .= '</tr></table>';

$str .= $OUTPUT->heading(get_string('totalformatsuses', 'report_vmoodle'), 2);

$str .= '<table width="250" class="generaltable">';
$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

$r = 0;
$nettotal = 0;
foreach ($allnodes as $format => $formatcount) {
    $formatname = get_string('pluginname', 'format_'.$format);
    if (strpos($formatname, '[[') !== false) {
        $formatname = get_string('format'.$format);
    }
    if (strpos($formatname, '[[') !== false) {
        $formatname = get_string($format);
    }
    $str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$formatname</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$formatcount}</td></tr>";
    $nettotal = 0 + $formatcount + @$nettotal;
    $r = ($r + 1) % 2;
}
$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
$str .= "</table></td>";
