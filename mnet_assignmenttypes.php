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

require_once($CFG->dirroot.'/mod/assignment/lib.php');

$year = optional_param('year', 0, PARAM_INT); 

$str = '';
$str .= '<form name="chooseyearform">';
$years[] = get_string('whenever', 'report_vmoodle');
for ($i = 0 ; $i < 15 ; $i++) {
    $years[2009 + $i] = 2009 + $i;
}

$str .= get_string('addeddate', 'report_vmoodle');
$str .= choose_from_menu($years, 'year', $year, '', false, false, true);
$gostr = get_string('apply', 'report_vmoodle');
$str .= ' <input type="hidden" name="view" value="assignmenttypes" />';
$str .= ' <input type="submit" value="'.$gostr.'" />';
$str .= '</form>';

$str .= $OUTPUT->heading(get_string('assignmenttypes', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_assignmenttypes', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
}

$str .= '<table width="100%"><tr>';

$col = 0;
$overall = 0;
$totalstr = get_string('totalassignmenttypes', 'report_vmoodle');
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$networktotalstr = get_string('networktotal', 'report_vmoodle');

$alltypes = mnet_report_assignment_get_types();

$stdresultarr = array();

foreach ($vhosts as $vhost) {

    $assignmenttypes = mnet_report_get_assignmenttypes($vhost, $year);

    $totassignmenttypes = 0;
    $str .= '<td valign="top">';

    $str .= '<table width="100%" class="generaltable">';
    $str .= '<tr><th colspan="2" class="header c0 report-vmoodle">'.$vhost->name.'</th></tr>';

    $r = 0;
    if ($assignmenttypes) {
        foreach ($assignmenttypes as $at) {
            $typename = $alltypes[$at->assignmenttype]->typestr;
            $str .= '<tr class="row r'.$r.'"><td width="80%" class="cell c0">'.$typename.'</td><td width="20%" class="cell c1 report-vmoodle">'.$at->atcount.'</td></tr>';
            $totassignmenttypes = 0 + $at->atcount + @$totassignmenttypes;
            $allnodes[$at->assignmenttype] = 0 + $at->atcount + @$allnodes[$at->atcount];
            $r = ($r + 1) % 2;
            $stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $typename, $at->atcount);
        }
    }
    $str .= '<tr class="row r'.$r.'"><td width="80%" class="cell c0 report-vmoodle">'.$totalstr.'<td><td width="20%" class="cell c1 report-vmoodle">'.$totassignmenttypes.'</td></tr>';
    $str .= '</table></td>';

    $col++;
    if ($col >= 4) {
        $str .= '</tr><tr>';
        $col = 0;
    }
}

$str .= '</tr></table>';

$str .= $OUTPUT->heading(get_string('totalassignmenttypesuses', 'report_vmoodle'), 2);

$str .= '<table width="250" class="generaltable">';
$str .= '<tr><th colspan="2" class="header c0 report-vmoodle">'.$allnodesstr.'</th></tr>';

$r = 0;
$nettotal = 0;
foreach ($allnodes as $typename => $assigntypecount) {
    $typename = $alltypes[$at->assignmenttype]->typestr;
    $str .= '<tr class="row r'.$r.'"><td class="cell c0 report-vmoodle">'.$typename.'</td><td class="cell c1 report-vmoodle">'.$assigntypecount.'</td></tr>';
    $nettotal = 0 + $assigntypecount + @$nettotal;
    $r = ($r + 1) % 2;
}
$str .= '<tr class="row r'.$r.'"><td class="cell c0 report-vmoodle">'.$networktotalstr.'</td><td class="cell c1 report-vmoodle">'.$nettotal.'</td></tr>';
$str .= '</table></td>';

