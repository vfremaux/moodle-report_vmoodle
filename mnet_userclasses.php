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

$config = get_config('report_vmoodle');
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

$timeassignclause = '';
if ($year) {
    $timeassignclause = " AND YEAR(FROM_UNIXTIME(u.firstaccess)) <= $year ";
}

$pfconfig = explode(',', $config->profilefields);
list($insql, $inparams) = $DB->get_in_or_equal($pfconfig);
$widthincr = floor(80 / (count($pfconfig) + 3));
$hostnamestr = get_string('hostname', 'report_vmoodle');
$totalstr = get_string('totalusers', 'report_vmoodle');

$table = new html_table();
$table->head = array($hostnamestr);
$table->size = array('20%');
$table->align = array('left');

foreach ($pfconfig as $pf) {
    $fieldname = $DB->get_field('user_info_field', 'name', array('shortname' => trim($pf)));
    $table->head[] = $fieldname;
    $table->xlshead[] = $fieldname;
    $table->size[] = $widthincr;
    $table->align[] = 'center';

    $totals[$pf] = 0;
}

$table->head[] = $totalstr;
$table->xlshead[] = $totalstr;
$table->size[] = $widthincr;
$table->align[] = 'center';

$userclasscount = array();

$col = 0;
$hostresults = array();
foreach ($vhosts as $vhost) {

    $vdbprefix = $vhost->vdbprefix;
    $vdbname = $vhost->vdbname;

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
            uif.shortname as userclass,
            uif.name as userclassname,
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
            uif.shortname $insql
            $timeassignclause
        GROUP BY
            uif.shortname
        ORDER BY
            uif.sortorder
    ";

    if ($usercounts = $DB->get_records_sql($sql, $inparams)) {
        foreach ($usercounts as $user) {
            $usercount = 0 + $user->users;
            $userclasscount[$user->userclass] = @$userclasscount[$user->userclass] + $user->users;
            $hostresults[$vhost->vhostname][$user->userclass] = $renderer->format_number($usercount);
        }
    }
}

if (!empty($hostresults)) {
    foreach ($hostresults as $vhostname => $results) {

        // Print for HTML.
        $row = array();
        $totalrow = 0;
        $row[] = $renderer->host_full_name($vhostname);
        foreach ($pfconfig as $pf) {
            $row[] = $renderer->format_number(0 + @$results[$pf]);
            $totals[$pf] += 0 + @$results[$pf];
            $totalrow += 0 + @$results[$pf];
        }
        $row[] = $totalrow;
        $table->data[] = $row;

        // Print for XLS.
        $row = array();
        $row[] = $renderer->host_full_name($vhost);
        $totalrow = 0;
        foreach ($pfconfig as $pf) {
            $row[] = $results[$pf];
            $totalrow += $results[$pf];
        }
        $row[] = $totalrow;
        $table->xlsdata[] = $row;
    }

    $row = array();
    $row[] = $totalstr;
    $totalrow = 0;
    foreach ($pfconfig as $pf) {
        $row[] = '<b>'.$renderer->format_number($totals[$pf]).'</b>';
        $totalrow += $totals[$pf];
    }
    $row[] = '<b>'.$totalrow.'</b>';
    $table->data[] = $row;

    $str .= html_writer::table($table);

} else {
    $str .= $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
}

$headerarr = array($table->xlshead);
$stdresultarr = $table->xlsdata;
