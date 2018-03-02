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
 * @package     report_vmoodle
 * @category    report
 * @copyright   2012 Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
$str .= " <input type=\"hidden\" name=\"view\" value=\"modules\" />";
$str .= " <input type=\"submit\" value=\"$gostr\" />";
$str .= '</form>';

$str .= $OUTPUT->heading(get_string('modules', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $returnurl = new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'modules'))
    $str .= local_print_static_text('static_vmoodle_report_modules', $returnurl, '', true);
}

$col = 0;
$overall = 0 ;
$totalstr = get_string('totalmodules', 'report_vmoodle');
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$networktotalstr = get_string('networktotal', 'report_vmoodle');

$yearclause = '';
if (!empty($year)) {
    $yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) = $year ";
}

$stdresultarr = array();

foreach ($vhosts as $vhost) {
    $totmodules = 0;
    $sql = "
        SELECT 
            m.name as modname,
            COUNT(*) as modcount
        FROM 
            `{$vhost->vdbname}`.{$vhost->vdbprefix}modules m,
            `{$vhost->vdbname}`.{$vhost->vdbprefix}course_modules cm
        WHERE 
            cm.module = m.id 
            $yearclause
        GROUP 
            BY modname
        ORDER BY
            modname
    ";

    if ($modules = $DB->get_records_sql($sql)) {
        foreach ($modules as $m) {
            $modname = get_string('modulenameplural', $m->modname);
            $icon = $OUTPUT->pix_icon('icon', $m->modname);
            $str .= $modname $m->modcount;
            $totmodules = 0 + $m->modcount + @$totmodules;
            $allnodes[$m->modname] = 0 + $m->modcount + @$allnodes[$m->modname];
            $stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $modname, $m->modcount);
        }
    }
}

foreach ($modnames)

if (!empty($results)) {
    foreach ()
}

$str .= $OUTPUT->heading(get_string('totalmodulesuses', 'report_vmoodle'), 2);

$str .= '<table width="250" class="generaltable">';
$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

$r = 0;
$nettotal = 0;
foreach ($allnodes as $modname => $modcount) {
    $modname = get_string('modulenameplural', $modname);
    $str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$modname</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$modcount}</td></tr>";
    $nettotal = 0 + $modcount + @$nettotal;
    $r = ($r + 1) % 2;
}
$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
$str .= '</table></td>';
