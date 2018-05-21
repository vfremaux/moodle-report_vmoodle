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

$col = 0;
$overall = 0 ;
$totalstr = get_string('totalmodules', 'report_vmoodle');
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$hostnamestr = get_string('hostname', 'report_vmoodle');

$yearclause = '';
if (!empty($year) && $year != 9999) {
    $yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) = $year ";
}

$stdresultarr = array();

$table = new html_table();
$table->head = array($hostnamestr);
$table->size = array('25%');
$table->align = array('left');
$table->width = '95%';

$maxscale = 0;
$modnames = array();
foreach ($vhosts as $vhost) {

    $totmodules = 0;
    $sql = "
        SELECT 
            m.name as modname,
            m.visible as visible,
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
            if (!in_array($m->modname, $modnames)) {
                $modnames[] = $m->modname;
                $mods[$m->modname] = $m;
            }
            $allnodes[$m->modname] = 0 + $m->modcount + @$allnodes[$m->modname];
            $hostmodules[$vhost->vhostname][$m->modname] = $m->modcount;
            if ($m->modcount > $maxscale) {
                $maxscale = $m->modcount;
            }
        }
    }
}
foreach ($modnames as $mn) {
    $fullmodnames[$mn] = get_string('modulenameplural', $mn);
    $modicons[$mn] = $OUTPUT->pix_icon('icon', $fullmodnames[$mn], $mn, array('width' => '32px', 'height' => '32px'));
}

asort($fullmodnames);

foreach ($fullmodnames as $mn => $fullmodname) {
    $table->head[] = $modicons[$mn];
    $table->align[] = 'center';
    $table->size[] = '5%';
}

foreach ($vhosts as $vhost) {

    $row = array();
    $row[] = $renderer->host_full_name($vhost);
    foreach ($fullmodnames as $mn => $fullmodname) {
        if (!empty($hostmodules[$vhost->vhostname][$mn])) {
            $html = $renderer->format_number($hostmodules[$vhost->vhostname][$mn]);
            $graphratio = round(log($hostmodules[$vhost->vhostname][$mn]) * 10);
            $html .= '<br/>';
            if ($mods[$mn]->visible) {
                $html .= $OUTPUT->pix_icon('bluecircle', '', 'report_vmoodle', array('width' => $graphratio, 'height' => $graphtratio));
            } else {
                $html .= $OUTPUT->pix_icon('redcircle', '', 'report_vmoodle', array('width' => $graphratio, 'height' => $graphtratio));
            }
            $row[] = $html;
        } else {
            $row[] = $renderer->format_number(0);
        }
    }
    $table->data[] = $row;
}

$totalrow = array();
$totalrow[] = $allnodesstr;
foreach ($fullmodnames as $mn => $fullmodname) {
    if (!empty($allnodes[$mn])) {
        $totalrow[] = $renderer->format_number($allnodes[$mn]);
    } else {
        $totalrow[] = $renderer->format_number(0);
    }
}
$table->data[] = $totalrow;

$str = '';
$str .= $renderer->filter_form('');

$str .= $OUTPUT->heading(get_string('modules', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $returnurl = new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'modules'));
    $str .= local_print_static_text('static_vmoodle_report_modules', $returnurl, '', true);
}

$str .= html_writer::table($table);

