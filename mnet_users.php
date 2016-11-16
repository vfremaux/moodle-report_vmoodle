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
 * @package    report
 * @subpackage vmoodle
 * @copyright  2012 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('You cannot access this script directly');
}

$year = optional_param('year', 0, PARAM_INT);

$str = '';

$str .= $OUTPUT->heading(get_string('users', 'report_vmoodle'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $str .= local_print_static_text('static_vmoodle_report_users', $CFG->wwwroot.'/admin/report/vmoodle/view.php?view=users', '', true);
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

$str .= '<table width="100%"><tr>';

$firstaccessclause = '';
if ($year) {
    $firstaccessclause = " AND YEAR(FROM_UNIXTIME(firstaccess)) <= $year ";
}

$totallocalusers = 0;
$localusers = array();

$col = 0;
$totalusersinhoststr = get_string('totalusersinhost', 'report_vmoodle');
foreach($vhosts as $vhost) {

    $str .= "<td valign=\"top\">";

    $sql = "
        SELECT
            h.name as host,
            h.wwwroot as vhost,
            COUNT(*) as users
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}user as u,
            `{$vhost->vdbname}`.{$vhost->vdbprefix}mnet_host as h
        WHERE 
            u.mnethostid = h.id AND
            u.deleted = 0
            $firstaccessclause
        GROUP BY
            u.mnethostid
        ORDER BY
            h.name
    ";

    $str .= "<table width=\"100%\" class=\"generaltable\"><tr>";
    $str .= "<th colspan=\"2\" class=\"header c0\"  style=\"line-height:20px;\" >$vhost->name</th></tr>";

    $localusertotal = 0;
    if ($users = $DB->get_records_sql($sql)) {
        $r = 0;
        foreach ($users as $user) {
            if ($user->vhost == $vhost->vhostname) {
                $localusers[$vhost->name] = $user->users;
                $totallocalusers += $user->users;
            }
            $usercount = 0 + $user->users;
            $localusertotal = 0 + @$localusertotal + $user->users;
            $str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"border:1px solid #808080\">$user->host</td><td width=\"20%\" class=\"cell c1\" style=\"border:1px solid #808080\">{$usercount}</td></tr>";
            $r = ($r + 1) % 2;
        }
    }

    $str .= "<tr class=\"row r$r\"><td width=\"80%\" class=\"cell c0\" style=\"font-weight:bolder;border:1px solid #808080\">$totalusersinhoststr</td><td width=\"20%\" class=\"cell c1\" style=\"font-weight:bolder;border:1px solid #808080\">{$localusertotal}</td></tr>";
    $str .= "</table></td>";

    $col++;
    if ($col >= 4) {
        $str .= '</tr><tr>';
        $col = 0;
    }
}

$str .= '</tr></table>';

if (!empty($localusers)) {
    $str .= '<br/><center><table width="50%" class=\"generaltable\">';
    foreach ($localusers as $hostname => $localcount) {
        $str .= '<tr class="row"><td class="cell">'.$hostname.'</td><td class="cell">'.$localcount.'</td></tr>';
    }
    $str .= '<tr class="row"><td class="cell"><b>TOTAL</b></td><td class="cell">'.$totallocalusers.'</td></tr>';
    $str .= '</table></center>';
}
