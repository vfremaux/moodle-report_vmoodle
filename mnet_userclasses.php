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
$context = optional_param('context', CONTEXT_COURSE, PARAM_INT); 

$str = '';

$str .= $OUTPUT->heading(get_string('userclasses', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_userstatus', $CFG->wwwroot.'/dmin/report/vmoodle/view.php?view=userstatus', '', true);
}

$str .= '<form name="chooseyearform">';
$str .= '<input type="hidden" name="view" value="'.$view.'" />';

$years[0] = 'Sans filtrage';
for ($i = 0 ; $i < 15 ; $i++) {
    $years[2009 + $i] = 2009 + $i;
}
$str .= html_writer::select($years, 'year', $year, array());

$gostr = get_string('apply', 'report_vmoodle');
$str .= '<input type="submit" value="'.$gostr.'" />';
$str .= '</form>';

$str .= '<table width="100%" cellspacing="10"><tr>';

$timeassignclause = '';
if ($year) {
    $timeassignclause = " AND YEAR(FROM_UNIXTIME(u.firstaccess)) <= $year ";
}

$userclasscount = array();

$col = 0;
foreach ($vhosts as $vhost) {

    $vdbprefix = $vhost->vdbprefix;
    $vdbname = $vhost->vdbname;

    $str .= '<td valign="top" align="center">';

    $localmnethostidsql = "
        SELECT
            value
        FROM 
            `{$vdbname}`.{$vdbprefix}config
        WHERE
            name = 'mnet_localhost_id'
    ";
    $remote_local_mnethostid = $DB->get_field_sql($localmnethostidsql);

    // limit count to real local users.
    $sql = "
        SELECT
            uif.name as userclass,
            COUNT(DISTINCT(u.id)) as users
        FROM 
            `{$vdbname}`.{$vdbprefix}user as u,
            `{$vdbname}`.{$vdbprefix}user_info_data as uid,
            `{$vdbname}`.{$vdbprefix}user_info_field as uif
        WHERE
            u.id = uid.userid AND
            uid.fieldid = uif.id AND
            u.mnethostid = {$remote_local_mnethostid} AND
            uid.data = 1 AND
            u.deleted = 0 AND
            uif.shortname IN ('parent', 'enseignant', 'eleve', 'administration', 'cdt')
            $timeassignclause
        GROUP BY
            uif.shortname
        ORDER BY
            uif.sortorder
    ";

    $str .= '<table width="100%" class="generaltable"><tr>';
    $str .= '<th colspan="2" class="header c0 report-vmoodle"><b>'.$vhost->name.'</b></th></tr>';

    if ($users = $DB->get_records_sql($sql)) {
        $r = 0;
        foreach ($users as $user) {
            $usercount = 0 + $user->users;
            $userclasscount[$user->userclass] = @$userclasscount[$user->userclass] + $user->users; 
            $str .= '<tr class="row r'.$r.'"><td width="80%" class="cell c0 report-moodle">'.$user->userclass.'</td><td width="20%" class="cell c1 report-vmoodle">'.$usercount.'</td></tr>';
            $r = ($r + 1) % 2;
        }
    } else {
        $str .= '<tr><td>'.get_string('nodata', 'report_vmoodle').'</td></tr>';
    }

    $str .= '</td></tr></table></td>';

    $col++;
    if ($col >= 4) {
        $str .= '</tr><tr>';
        $col = 0;
    }
}

$str .= '</tr></table>';

if (!empty($userclasscount)) {
    $str .= '<br/><center><table width="50%" class="generaltable">';
    foreach ($userclasscount as $userclass => $usercount) {
        $str .= '<tr class="row"><td class="cell" class="report-vmoodle">'.$userclass.'</td><td class="cell">'.$usercount.'</td></tr>';
    }
    $str .= '</table></center>';
}
