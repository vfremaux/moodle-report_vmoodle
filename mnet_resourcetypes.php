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

require_once($CFG->dirroot.'/mod/resource/lib.php');

$year = optional_param('year', 0, PARAM_INT); 

$col = 0;
$overall = 0 ;
$allnodesstr = get_string('allnodes', 'report_vmoodle');

$yearclause = '';
if (!empty($year)) {
    $yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) <= $year ";
}

$table = new html_table();
$table->head = array($hostnamestr);
$table->size = array('25%');
$table->align = array('left');
$table->width = '95%';

$maxscale = 0;
$totresourcetypes = 0;

foreach ($vhosts as $vhost) {

    $sql = "
        SELECT
            m.name as typename,
            m.visible as visible,
            COUNT(cm.id) as rtcount
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}modules m
        LEFT JOIN
            `{$vhost->vdbname}`.{$vhost->vdbprefix}course_modules cm
        ON
            cm.module = m.id
        WHERE
            m.name IN ('resource', 'url', 'folder', 'sharedresource')
            $yearclause
        GROUP BY
            typename
        ORDER BY
            typename
    ";

    if ($restypes = $DB->get_records_sql($sql)) {
        foreach ($restypes as $r) {
            if (!in_array($r->modname, $restypenames)) {
                $restypenames[] = $r->typename;
                $resourcetypes[$r->typename] = $r;
            }
            $allnodes[$r->typename] = 0 + $r->rtcount + @$allnodes[$r->typename];
            $hostresourcetypes[$vhost->vhostname][$r->typename] = $r->rtcount;
            if ($r->rtcount > $maxscale) {
                $maxscale = $r->rtcount;
            }
        }
    }
}

foreach ($restypenames as $rt) {
    if (!is_dir($CFG->dirroot.'/mod/'.$rt)) {
        // Care about uninstalled modules.
        continue;
    }
    $fullmodnames[$rt] = get_string('modulenameplural', $rt);
    $modicons[$rt] = $OUTPUT->pix_icon('icon', $fullmodnames[$rt], $rt, array('width' => '32px', 'height' => '32px'));
}

asort($fullmodnames);

foreach ($fullmodnames as $rt => $fullmodname) {
    $table->head[] = $modicons[$rt];
    $table->align[] = 'center';
    $table->size[] = '5%';
}

$headers = array('host');
$firstline = true;

foreach ($vhosts as $vhost) {

    $stdresult = array($vhost->vhostname);

    $row = array();
    $row[] = $renderer->host_full_name($vhost);

    if (!empty($fullmodnames)) {
        foreach ($fullmodnames as $rt => $fullmodname) {
            if ($firstline) {
                $headers[] = $rt;
            }

            // Data in row.
            $count = $hostresourcetypes[$vhost->vhostname][$rt];
            $graphratio = 0;
            if ($count) {
                $graphratio = round(log($count) * 10) + 1;
            }
            $html = $renderer->format_number($count);
            $html .= '<br/>';
            if ($resourcetypes[$rt]->visible) {
                $html .= $OUTPUT->pix_icon('bluecircle', $fullmodname, 'report_vmoodle', array('width' => $graphratio, 'height' => $graphratio));
            } else {
                $html .= $OUTPUT->pix_icon('redcircle', $fullmodname, 'report_vmoodle', array('width' => $graphratio, 'height' => $graphratio));
            }
            $row[] = $html;

            $stdresult[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle'), $typename, $count);
        }
    }

    if ($firstline) {
        $headerarr[] = $headers;
    }
    $stdresultarr[] = $stdresult;
    $firstline = false;
    $table->data[] = $row;
}

$totalrow = array();
$totalrow[] = $allnodesstr;
foreach ($resourcetypes as $rt) {
    if (!empty($allnodes[$rt->typename])) {
        $totalrow[] = $renderer->format_number($allnodes[$rt->typename]);
    } else {
        $totalrow[] = $renderer->format_number(0);
    }
}
$table->data[] = $totalrow;

$str = '';
$str .= $renderer->filter_form('');

$str .= $OUTPUT->heading(get_string('resourcetypes', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $returnurl = new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'resourcetypes'));
    $str .= local_print_static_text('static_vmoodle_report_modules', $returnurl, '', true);
}

$str .= html_writer::table($table);
