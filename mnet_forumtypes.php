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

// Preloads all QTYPES.
require_once($CFG->dirroot.'/mod/forum/lib.php');

$forum_types = forum_get_forum_types();
$forum_types['news'] = get_string('namenews', 'forum');
$forum_types['social'] = get_string('namesocial', 'forum');

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
$str .= " <input type=\"hidden\" name=\"view\" value=\"forumtypes\" />";
$str .= " <input type=\"submit\" value=\"$gostr\" />";
$str .= '</form>';

$str .= $OUTPUT->heading(get_string('forumtypes', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_forumtypes', $CFG->wwwroot.'/admin/report/vmoodle/view.php', '', true);
}

$str .= '<table width="100%"><tr>';

$col = 0;
$overall = 0 ;
$totalstr = get_string('totalforumtypes', 'report_vmoodle');        
$allnodesstr = get_string('allnodes', 'report_vmoodle');
$networktotalstr = get_string('networktotal', 'report_vmoodle');

$yearclause = '';
if (!empty($year)) {
    $yearclause = " AND YEAR( FROM_UNIXTIME(cm.added)) = $year ";
}

$allnodes = array();
$stdresultarr = array();

foreach ($vhosts as $vhost) {
    $totforumtypes = 0;
    $str .= '<td valign="top">';
    $sql = "
        SELECT
            f.type as typename,
            COUNT(*) as ftcount
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}forum f,
            `{$vhost->vdbname}`.{$vhost->vdbprefix}course_modules cm,
            `{$vhost->vdbname}`.{$vhost->vdbprefix}modules m
        WHERE
            f.id = cm.instance AND
            cm.module = m.id AND
            m.name = 'forum'
            $yearclause
        GROUP
            BY typename
        ORDER BY
            typename
    ";

    $str .= "<table width=\"100%\" class=\"generaltable\">";
    $str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px\" >$vhost->name</th></tr>";

    $r = 0;
    if ($forumtypes = $DB->get_records_sql($sql)) {
        foreach ($forumtypes as $ft) {
            $typename = $forum_types[$ft->typename];
            $str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$ft->ftcount}</td></tr>";
            $totforumtypes = 0 + $ft->ftcount + @$totforumtypes;
            $allnodes[$ft->typename] = 0 + $ft->ftcount + @$allnodes[$ft->typename];
            $r = ($r + 1) % 2;
            $stdresultarr[] = array($vhost->name, ($year) ? $year : get_string('whenever', 'report_vmoodle') , $typename, $ft->ftcount);
        }
    }
    $str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"line-height:20px\">$totalstr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$totforumtypes}</td></tr>";
    $str .= "</table></td>";

    $col++;
    if ($col >= 4) {
        $str .= '</tr><tr>';
        $col = 0;
    }
}

$str .= '</tr></table>';

$str .= $OUTPUT->heading(get_string('totalforumtypesuses', 'report_vmoodle'), 2);

$str .= "<table width=\"250\" class=\"generaltable\">";
$str .= "<tr><th colspan=\"2\" class=\"header c0\" style=\"line-height:20px;\">$allnodesstr</th></tr>";

$r = 0;
$nettotal = 0;
foreach ($allnodes as $typename => $ftcount) {
    $typename = $forum_types[$typename];
    $str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080\">$typename</td><td class=\"cell c1\" style=\"border:1px solid #808080\">{$ftcount}</td></tr>";
    $nettotal = 0 + $ftcount + @$nettotal;
    $r = ($r + 1) % 2;
}
$str .= "<tr class=\"row r$r\"><td class=\"cell c0\" style=\"border:1px solid #808080;font-weight:bolder\">$networktotalstr</td><td class=\"cell c1\" style=\"border:1px solid #808080;font-weight:bolder\">{$nettotal}</td></tr>";
$str .= "</table></td>";
