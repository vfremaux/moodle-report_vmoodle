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

$config = get_config('report_vmoodle');

$year = optional_param('year', date('Y'), PARAM_INT); 
$mode = optional_param('mode', 0, PARAM_INT); 

$modecreatedsel = ($mode == 0) ? 'checked="checked"' : '';
$modecreatedbeforesel = ($mode == 1) ? 'checked="checked"' : '';

$additionalinputs = ' <input type="radio" name="mode" value="0" '.$modecreatedsel.' />';
$additionalinputs .= get_string('created', 'report_vmoodle');

$additionalinputs .= ' <input type="radio" name="mode" value="1" '.$modecreatedbeforesel.' />';
$additionalinputs .= get_string('createdbefore', 'report_vmoodle');

$str = '';
$str .= $renderer->filter_form($additionalinputs);

$overall = 0 ;
$totalstr = get_string('totalcourses', 'report_vmoodle');
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$yearlytotalstr = get_string('totalyearly', 'report_vmoodle');
$shortyearlytotalstr = get_string('totalyearlyshort', 'report_vmoodle');

$stdresultarr = array();
$headers = array($hostnamestr);
$firstline = true;
$mstart = 1;
if (!empty($config->shiftyearstart)) {
    $mstart = 9;
}

$table = new html_table();
$table->head = array($hostnamestr);
$table->size = array('25%');
$table->align = array('left');
$table->width = '95%';

foreach ($vhosts as $vhost) {
    $totcourses = 0;

    list($courses, $previouscourses) = mnet_report_get_courses($vhost, $mode, $year);

    $row = array($renderer->host_full_name($vhost));
    $stdresult = array($renderer->host_full_name($vhost));

    $hostyearly = 0;
    if ($courses) {
        $m = $mstart;
        for ($i = 1 ; $i <= 12; $i++) {
            if ($firstline) {
                $dt = DateTime::createFromFormat('!m', $m);
                $month = $dt->format('F');
                $table->head[] = get_string(strtolower($month), 'report_vmoodle');
                $headers[] = $month;
                $m = ($m % 12) + 1;
            }
            $count = 0 + @$courses[$m]->coursecount;
            $hostyearly = $hostyearly + $count; // line aggregation.
            $overalmonthly[$i] = @$overalmonthly[$i] + $count; // monthly for all hosts.
            $overall = $overall + $count; // overall for all hosts.
            if ($mode == 0) {
                $coursesperhost[$vhost->name] = 0 + @$coursesperhost[$vhost->name] + $count;
            } else {
                $coursesperhost[$vhost->name] = 0 + $count;
            }
            $row[] = $count;
            $stdresult[] = $count;
        }
        if ($firstline) {
            $table->head[] = $yearlytotalstr;
            $headers[] = $yearlytotalstr;
        }
        $row[] = $hostyearly;
        $stdresult[] = $hostyearly;
    }

    if ($firstline) {
        $headerarr[] = $headers;
    }
    $stdresultarr[] = $stdresult;
    $firstline = false;
    $table->data[] = $row;
}

// Per month overall sumators.
$lastrow = [''];
for ($i = 0 ; $i < 12; $i++) {
    $lastrow[] =  0 + $overalmonthly[$i];
}
$lastrow[] = $overall;
$table->data[] = $lastrow;

$str .= $OUTPUT->heading(get_string('courses'));

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $returnurl = new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'courses'));
    $str .= local_print_static_text('static_vmoodle_report_courses', $returnurl, '', true);
}

$str .= html_writer::table($table);