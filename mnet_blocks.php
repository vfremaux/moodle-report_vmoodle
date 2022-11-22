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

$overall = 0 ;
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$hostnamestr = get_string('hostname', 'report_vmoodle');

$stdresultarr = array();

$table = new html_table();
$table->head = array($hostnamestr);
$table->size = array('25%');
$table->align = array('left');
$table->width = '95%';

$maxscale = 0;
$blocknames = array();
foreach ($vhosts as $vhost) {

    $totmodules = 0;
    $sql = "
        SELECT
            b.name as blockname,
            b.visible as visible,
            COUNT(*) as blockcount
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}block b
        LEFT JOIN
            `{$vhost->vdbname}`.{$vhost->vdbprefix}block_instances bi
        ON
            bi.blockname = b.name
        GROUP BY
            blockname
        ORDER BY
            blockname
    ";

    if ($modules = $DB->get_records_sql($sql)) {
        foreach ($modules as $b) {
            if (!in_array($b->blockname, $blocknames)) {
                $blocknames[] = $b->blockname;
                $blocks[$b->blockname] = $b;
            }
            $allnodes[$b->blockname] = 0 + $b->blockcount + @$allnodes[$b->blockname];
            $hostmodules[$vhost->vhostname][$b->blockname] = $b->blockcount;
            if ($b->blockcount > $maxscale) {
                $maxscale = $b->blockcount;
            }
        }
    }
}

foreach ($blocknames as $bn) {
    if (!is_dir($CFG->dirroot.'/blocks/'.$bn)) {
        // Care about uninstalled modules.
        continue;
    }
    $fullblocknames[$bn] = get_string('pluginname', 'block_'.$bn);
    $blockicons[$bn] = $OUTPUT->pix_icon('icon', $fullblocknames[$bn], $bn, array('width' => '32px', 'height' => '32px'));
}

asort($fullblocknames);

foreach ($fullblocknames as $bn => $fullblockname) {
    $table->head[] = $blockicons[$bn];
    $table->align[] = 'center';
    $table->size[] = '5%';
}

$headers = array('host');
$firstline = true;

foreach ($vhosts as $vhost) {

    $stdresult = array($vhost->vhostname);

    $row = array();
    $row[] = $renderer->host_full_name($vhost);
    foreach ($fullblocknames as $bn => $fullblockname) {
        if ($firstline) {
            $headers[] = $bn;
        }
        if (!empty($hostmodules[$vhost->vhostname][$bn])) {
            $stdresult[] = $hostmodules[$vhost->vhostname][$bn];
            $html = $renderer->format_number($hostmodules[$vhost->vhostname][$bn]);
            $graphratio = round(log($hostmodules[$vhost->vhostname][$bn]) * 10) + 1;
            $html .= '<br/>';
            if ($blocks[$bn]->visible) {
                $html .= $OUTPUT->pix_icon('bluecircle', '', 'report_vmoodle', array('width' => $graphratio, 'height' => $graphtratio));
            } else {
                $html .= $OUTPUT->pix_icon('redcircle', '', 'report_vmoodle', array('width' => $graphratio, 'height' => $graphtratio));
            }
            $row[] = $html;
        } else {
            $stdresult[] = 0;
            $row[] = $renderer->format_number(0);
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
foreach ($fullblocknames as $bn => $fullblockname) {
    if (!empty($allnodes[$bn])) {
        $totalrow[] = $renderer->format_number($allnodes[$bn]);
    } else {
        $totalrow[] = $renderer->format_number(0);
    }
}
$table->data[] = $totalrow;

$str .= $OUTPUT->heading(get_string('blocks', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $returnurl = new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'modules'));
    $str .= local_print_static_text('static_vmoodle_report_modules', $returnurl, '', true);
}

$str .= html_writer::table($table);

