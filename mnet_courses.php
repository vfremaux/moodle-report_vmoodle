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

$year = optional_param('year', date('Y'), PARAM_INT); 
$mode = optional_param('mode', 0, PARAM_INT); 

$str = '';
$str .= '<form name="chooseyearform">';
$years[] = get_string('whenever', 'report_vmoodle');
for ($i = 0 ; $i < 15 ; $i++) {
    $years[2009 + $i] = 2009 + $i;
}

$str .= get_string('addeddate', 'report_vmoodle');
$str .= html_writer::select($years, 'year', $year, array());

$modecreatedsel = ($mode == 0) ? 'checked="checked"' : '';
$modecreatedbeforesel = ($mode == 1) ? 'checked="checked"' : '';

$str .= ' <input type="radio" name="mode" value="0" '.$modecreatedsel.' />';
$str .= get_string('created', 'report_vmoodle');

$str .= ' <input type="radio" name="mode" value="1" '.$modecreatedbeforesel.' />';
$str .= get_string('createdbefore', 'report_vmoodle');

$gostr = get_string('apply', 'report_vmoodle');
$str .= ' <input type="hidden" name="view" value="courses" />';
$str .= ' <input type="submit" value="'.$gostr.'" />';
$str .= '</form>';

$str .= $OUTPUT->heading(get_string('courses'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_modules', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
}

$str .= '<table width="100%"><tr>';

$col = 0;
$overall = 0 ;
$totalstr = get_string('totalcourses', 'report_vmoodle');
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$networktotalstr = get_string('networktotal', 'report_vmoodle');
$yearlytotalstr = get_string('totalyearly', 'report_vmoodle');
$shortyearlytotalstr = get_string('totalyearlyshort', 'report_vmoodle');

$yearclause = '';
$stdresultarr = array();

foreach ($vhosts as $vhost) {
    $totcourses = 0;

    $courses = mnet_report_get_courses($vhost, $mode, $yearclause);

    $str .= '<td valign="top">';
    $str .= '<table width="100%" class="generaltable">';
    $str .= '<tr><th colspan="2" class="header c0" style="line-height:20px" >'.$vhost->name.'</th></tr>';

    $r = 0;
    $yearly = 0;
    if ($courses) {
        for ($m = 1 ; $m <= 12 ; $m++) {
            $count = 0 + @$courses[$m]->coursecount;
            $yearly = $yearly + $count;
            $overalmonthly[$m] = @$overalmonthly[$m] + $count;
            $overall = $overall + $count;
            $totcourses += $count;
            if ($mode == 0) {
                $coursesperhost[$vhost->name] = @$coursesperhost[$vhost->name] + $count;
            } else {
                $coursesperhost[$vhost->name] = $count;
            }
            $str .= '<tr class="row r'.$r.'"><td width="80%" class="cell c0 report-vmoodle">'.$m.'</td><td width="20%" class="cell c1 report-vmoodle">'.$count.'</td></tr>';
            $r = ($r + 1) % 2;
            $stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $m, $count);
        }
    }
    if ($mode == 0) {
        $str .= '<tr class="row r'.$r.'"><td width="80%" class="cell c0 report-vmoodle">'.$totalstr.'</td><td width="20%" class="cell c1 report-vmoodle">'.$totcourses.'</td></tr>';
    }
    $str .= '</table></td>';

    $col++;
    if ($col >= 4) {
        $str .= '</tr><tr>';
        $col = 0;
    }
}

$str .= '</tr></table>';

$str .= $OUTPUT->heading(get_string('coursetotals', 'report_vmoodle'), 2);

$str .= '<table width="100%"><tr valign="top"><td>';
$overalmonthlystr = get_string('totalmonthly', 'report_vmoodle');
$str .= '<table width="250" class="generaltable">';
$str .= '<tr><th colspan="2" class="header c0 report-vmoodle">'.$overalmonthlystr.'</th></tr>';

$r = 0;
for ($m = 1 ; $m <= 12 ; $m++) {
    $om = 0 + @$overalmonthly[$m];
    $str .= '<tr class="row r'.$r.'"><td class="cell c0 report-vmoodle">'.$m.'</td><td class="cell c1">'.$om.'</td></tr>';
    $r = ($r + 1) % 2;
}

if ($mode == 0) {
    $str .= '<tr class="row r'.$r.'"><td class="cell c0 report-vmoodle">'.$yearlytotalstr.'</td><td class="cell c1 report-vmoodle">'.$overall.'</td></tr>';
}
$str .= '</table>';

$str .= '</td><td>';

$overalperhoststr = get_string('coursesperhost', 'report_vmoodle');
$str .= '<table width="250" class="generaltable">';
$str .= '<tr><th colspan="2" class="header c0 report-vmoodle">'.$overalperhoststr.'</th></tr>';
foreach ($vhosts as $vhost) {
    $str .= '<tr class="row r'.$r.'"><td class="cell c0 report-vmoodle">'.$vhost->name.'</td><td class="cell c1 report-vmoodle">'.@$coursesperhost[$vhost->name].'</td></tr>';
}
$str .= '</table>';

$str .= '</td></tr></table>';
