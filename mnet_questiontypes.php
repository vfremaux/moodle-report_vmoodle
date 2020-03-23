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

// Preloads all QTYPES.
require_once($CFG->dirroot.'/lib/questionlib.php');

$year = optional_param('year', 0, PARAM_INT);

$yearclause = '';
if (!empty($year)) {
    $yearclause = " AND YEAR( FROM_UNIXTIME(q.timecreated)) <= $year ";
}


$table = new html_table();
$hostnamestr = get_string('hostname', 'report_vmoodle');
$table->head = array($hostnamestr);
$table->size = array('25%');
$table->align = array('left');
$table->width = '95%';

$allnodes = array();
$stdresultarr = array();

$totquestiontypes = 0;

$installedqtypes = question_bank::get_all_qtypes();
$qtypenames = array();
foreach (array_keys($installedqtypes) as $qtname) {
    if (!in_array($qtname, $qtypenames)) {
        $qtypenames[] = $qtname;
    }
}

foreach ($vhosts as $vhost) {
    $sql = "
        SELECT
            q.qtype as typename,
            COUNT(*) as qtcount
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}question q
        WHERE
            1 = 1
            $yearclause
        GROUP
            BY typename
        ORDER BY
            typename
    ";

    if ($qtypes = $DB->get_records_sql($sql)) {
        // From real use.
        foreach ($qtypes as $q) {
            if (!in_array($q->modname, $qtypenames)) {
                $qtypenames[] = $q->typename;
            }
            $allnodes[$q->typename] = 0 + $q->qtcount + @$allnodes[$q->typename];
            $hostquestiontypes[$vhost->vhostname][$q->typename] = $q->qtcount;
            if ($q->qtcount > $maxscale) {
                $maxscale = $q->qtcount;
            }
        }
    }

    // Feed missing installed types.
    foreach (array_keys($installedqtypes) as $qtname) {
        if (!array_key_exists($qtname, $hostquestiontypes[$vhost->vhostname])) {
            $hostquestiontypes[$vhost->vhostname][$qtname] = 0;
        }
    }
}


foreach ($qtypenames as $qt) {
    if (!is_dir($CFG->dirroot.'/question/type/'.$qt)) {
        // Care about uninstalled modules.
        continue;
    }
    $fullmodnames[$qt] = get_string('pluginname', 'qtype_'.$qt);
    $modicons[$qt] = $OUTPUT->pix_icon('icon', $fullmodnames[$qt], 'qtype_'.$qt, array('width' => '32px', 'height' => '32px'));
}

asort($fullmodnames);

foreach ($fullmodnames as $qt => $fullmodname) {
    $table->head[] = $modicons[$qt];
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
        foreach ($fullmodnames as $qt => $fullmodname) {
            if ($firstline) {
                $headers[] = $qt;
            }

            // Data in row.
            $count = $hostquestiontypes[$vhost->vhostname][$qt];
            $graphratio = 0;
            if ($count) {
                $graphratio = round(log($count) * 10) + 1;
            }
            $html = $renderer->format_number($count);
            $html .= '<br/>';

            $sql = "
                SELECT
                    value
                FROM
                    `{$vhost->vdbname}`.{$vhost->vdbprefix}config_plugins cf
                WHERE
                    plugin = ? AND
                    name = ?
            ";
            $disabled = $DB->get_field_sql($sql, array('question', $qt.'_disabled'));

            if ($disabled) {
                $html .= $OUTPUT->pix_icon('redcircle', $fullmodname, 'report_vmoodle', array('width' => $graphratio, 'height' => $graphratio));
            } else {
                $html .= $OUTPUT->pix_icon('bluecircle', $fullmodname, 'report_vmoodle', array('width' => $graphratio, 'height' => $graphratio));
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
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$totalrow[] = $allnodesstr;
foreach (array_keys($fullmodnames) as $qt) {
    if (!empty($allnodes[$qt])) {
        $totalrow[] = $renderer->format_number($allnodes[$qt]);
    } else {
        $totalrow[] = $renderer->format_number(0);
    }
}
$table->data[] = $totalrow;

$str = '';
$str .= $renderer->filter_form('');

$str .= $OUTPUT->heading(get_string('questiontypes', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_questiontypes', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
}

$str .= html_writer::table($table);