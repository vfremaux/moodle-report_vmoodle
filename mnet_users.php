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
 * @package    report_vmoodle
 * @category   report
 * @copyright  2012 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');
local_vflibs_require_jqplot_libs();

if (!defined('MOODLE_INTERNAL')) {
    die('You cannot access this script directly');
}

$config = get_config('report_vmoodle');
$year = optional_param('year', 0, PARAM_INT);

$userpresentclause = '';
$usercreationrange = '';
if ($year && $year < 9000) {
    $yearstart = mktime(0, 0, 0, 1, 1, $year);
    $yearend = mktime(0, 0, 0, 1, 1, $year + 1) - 1;
    if (!empty($config->shiftyearstart)) {
        $yearstart = mktime(0, 0, 0, 9, 1, $year);
        $yearend = mktime(0, 0, 0, 9, 1, $year + 1) - 1;
    }
    $userpresentclause = " AND currentlogin >= $yearstart AND currentlogin <= $yearend ";
    $usercreationrange = ' ['.date('Y-m-d', $yearstart).' - '.date('Y-m-d', $yearend).']';
}

$totallocalusers = 0;
$localusers = array();

$hostnamestr = get_string('hostname', 'report_vmoodle');
$internalstr = get_string('locals', 'report_vmoodle');
$externalstr = get_string('remotes', 'report_vmoodle');
$deletedstr = get_string('deleted');
$suspendedstr =  get_string('suspended');
$unconnectedstr =  get_string('neverconnected', 'report_vmoodle');
$connectedstr =  get_string('connected', 'report_vmoodle');
$cnxratiostr =  get_string('cnxratio', 'report_vmoodle');
$totalstr = get_string('totalusers', 'report_vmoodle');
$cnxedstr = get_string('cnxed', 'report_vmoodle');
$uncnxedstr = get_string('uncnxed', 'report_vmoodle');

$pfconfig = explode(',', $config->profilefields);
$widthincr = floor(80 / (count($pfconfig) + 3));

$table = new html_table();
$cextstr = $connectedstr.' / '.$internalstr.'<br>'.$cnxratiostr;
$table->head = array($hostnamestr, $cextstr, $externalstr, $suspendedstr);
$table->xlshead = array($hostnamestr, $internalstr, $unconnectedstr, $externalstr, $suspendedstr);
$table->size = array('20%', $widthincr, $widthincr, $widthincr, $widthincr);
$table->width = '95%';
$table->align = array('left', 'center', 'center', 'center', 'center');

$alllocals = 0;
$allsuspendeds = 0;
$allunconnected = 0;
$allremotes = 0;
for ($i = 0 ; $i < count($pfconfig) ; $i++) {
    $key = 'pfdata'.($i + 1);
    $$key = 0;
}

foreach ($pfconfig as $pf) {
    $fieldname = $DB->get_field('user_info_field', 'name', array('shortname' => trim($pf)));
    $table->head[] = $fieldname.$uncextstr;
    $table->xlshead[] = $fieldname;
    $table->xlshead[] = $fieldname.' '.$unconnectedstr;
    $table->size[] = $widthincr;
    $table->align[] = 'center';
}

$totalusersinhoststr = get_string('totalusersinhost', 'report_vmoodle');

/*
 * Defines the max rows we can fetch at first page loading. All further rows will be delegated
 * to lazy loading with subsequent ajax queries.
 */
$htmllimit = 0;
$counter = 0;

$table->xlsdata = null;
$table->data = null;

foreach ($vhosts as $vhost) {

    if (($output == 'html') && ($counter >= $htmllimit) && ($vhost->vhostname != $CFG->wwwroot)) {
        // If over the fetchable limit, just print a delegated container.
        $delegated = new StdClass;
        $delegated->fragment = 'users';
        $delegated->contextstring = $vhost->vhostname;
        $table->data[] = $delegated;
        continue;
    }

    $vhostname = $renderer->host_full_name($vhost);

    list($insql, $inparams) = $DB->get_in_or_equal($pfconfig);
    // Prefetch profile info
    $sql = "
        SELECT
            shortname, id
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}user_info_field
        WHERE
            shortname $insql
    ";
    try {
        $fields = $DB->get_records_sql($sql, $inparams);
    } catch (Exception $ex) {
        // Jump unreachable hosts.
        continue;
    }

    $sql = "
        SELECT
            value
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}config
        WHERE
            name = 'mnet_localhost_id'
    ";
    $localusershost = $DB->get_field_sql($sql);

    $millesimclause = '';
    if (!is_dir($CFG->dirroot.'/local/ent_installer')) {
        $cohortfield = $DB->get_record('user_info_field', ['shortname' => 'cohort']);
        $millesim = get_config('local_ent_installer', 'cohort_ix');
        // Check for users having a matching millesim or no millesim at all (non students)
        $millesimclause = ' AND cf.data LIKE "'.$millesim.'%" OR cf.data = "" OR cf.data IS NULL ';
    }

    $profilefields = '';
    $profilejoins = '';
    $joinwheres = '';
    if ($fields) {
        $i = 1;
        foreach ($fields as $field) {
            $profilefields .= ', SUM(CASE WHEN pf'.$i.'.data IS NOT NULL AND pf'.$i.'.data > 0 THEN 1 ELSE 0 END) as pfdata'.$i."\n";
            $profilefields .= ', SUM(CASE WHEN u.currentlogin = 0 AND
                                               u.mnethostid = '.$localusershost.' AND
                                               pf'.$i.'.data IS NOT NULL AND
                                               pf'.$i.'.data > 0 THEN 1 ELSE 0 END) as pfdata'.$i.'unc';

            $joinwheres = ' ON pf'.$i.'.userid = u.id AND pf'.$i.'.fieldid = '.$field->id;
            $profilejoins .= " LEFT JOIN `{$vhost->vdbname}`.{$vhost->vdbprefix}user_info_data as pf".$i.$joinwheres;

            $i++;
        }

        // Add ENT_INSTALLER millesim check.
        if (!is_dir($CFG->dirroot.'/local/ent_installer')) {
            $joinwheres = ' ON pf'.$i.'.userid = u.id AND cf.fieldid = '.$cohortfield->id;
            $profilejoins .= " LEFT JOIN `{$vhost->vdbname}`.{$vhost->vdbprefix}user_info_data as cf ".$joinwheres;
        }
    }

    $sql = "
        SELECT
            SUM(CASE WHEN u.suspended = 0 AND u.mnethostid = ".$localusershost." THEN 1 ELSE 0 END) as localusers,
            SUM(CASE WHEN u.suspended = 0 AND u.mnethostid != ".$localusershost." THEN 1 ELSE 0 END) as remoteusers,
            SUM(CASE WHEN u.currentlogin = 0 AND u.suspended = 0 AND u.mnethostid = ".$localusershost." THEN 1 ELSE 0 END) as localunconnected,
            SUM(CASE WHEN u.suspended = 1 AND u.mnethostid = ".$localusershost." THEN 1 ELSE 0 END) as suspendedusers
            $profilefields
        FROM
            `{$vhost->vdbname}`.{$vhost->vdbprefix}user as u
            $profilejoins
        WHERE
            u.deleted = 0
            $userpresentclause
            $millesimclause
    ";

    $hoststats = $DB->get_records_sql($sql);

    if ($hoststats) {
        foreach ($hoststats as $us) {

            $lus = $us->localusers;
            $luu = $us->localunconnected;
            $luc = $us->localusers - $us->localunconnected;
            $ratio = ($lus > 0) ? sprintf('%.1f', $luc / $lus * 100).'%' : 0;

            // HTML rendering.
            $row = array();
            $row[] = $renderer->host_full_name($vhost);
            $alllocals += $us->localusers;
            $allremotes += $us->remoteusers;
            $localusers = $renderer->format_number($luc).' / '.$renderer->format_number($lus).' ('.$ratio.')';
            $data = array(array($cnxedstr, (int)$luc), array($uncnxedstr, (int)$luu));
            $attrs = array('height' => '150', 'width' => 150);
            // $localusers .= '<br/>'.local_vflibs_jqplot_simple_donut($data, 'users_'.$vhost->id, 'report-vmoodle-user-charts', $attrs);
            $row[] = $localusers;
            $row[] = $renderer->format_number($us->remoteusers);
            $row[] = $renderer->format_number($us->suspendedusers);
            $allsuspendeds += $us->suspendedusers;
            $allunconnected += $us->localunconnected;
            for ($i = 0 ; $i < count($pfconfig) ; $i++) {
                $key = 'pfdata'.($i + 1);
                $unckey = 'pfdata'.($i + 1).'unc';
                $pfu = 0 + @$us->$key;
                $pfunc = 0 + @$us->$unckey;
                $ratio = 0;
                if ($pfu) {
                    $ratio = (1 - ($pfunc / $pfu)) * 100;
                }
                $value = $renderer->format_number($pfu).' / '.$renderer->format_number($pfunc).' ('.sprintf('%.1f', $ratio).'%)';
                $data = array(array($cnxedstr, $pfu - $pfunc), array($uncnxedstr, $pfunc));
                $attrs = array('height' => '150', 'width' => 150);
                // $value .= '<br/>'.local_vflibs_jqplot_simple_donut($data, 'field_'.$vhost->id.'_'.$i, 'report-vmoodle-user-charts', $attrs);
                $row[] = $value;
                $$key += @$us->$key;
            }
            $table->data[] = $row;

            // XLS rendering.
            $row = array();
            $row[] = $renderer->host_full_name($vhost);
            $row[] = $lus;
            $row[] = $luu;
            $row[] = $us->remoteusers;
            $row[] = $us->suspendedusers;

            for ($i = 0 ; $i < count($pfconfig) ; $i++) {
                $key = 'pfdata'.($i + 1);
                $unckey = 'pfdata'.($i + 1).'unc';
                $row[] = @$us->$key;
                $row[] = @$us->$unckey;
            }
            $table->xlsdata[] = $row;

        }

        $counter++;
    }
}

$str = '';

$str .= $OUTPUT->heading(get_string('usersinrange', 'report_vmoodle', $usercreationrange), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    $returnurl = new moodle_url('/admin/report/vmoodle/view.php', array('view' => 'users'));
    $str .= local_print_static_text('static_vmoodle_report_users', $returnurl, '', true);
}

$str .= $renderer->filter_form($renderer->graphcontrol_button());

if (empty($table->data)) {
    $str = $OUTPUT->notification(get_string('nodata', 'report_vmoodle'));
} else {

    $totalratio = 0;
    if ($alllocals) {
        $totalratio = sprintf('%.1f', (1 - ($allunconnected / $alllocals)) * 100).'%';
    }

    $allconnected = $alllocals - $allunconnected;
    $allusers = '<span id="sumator-localusers">'.$alllocals.'</span> / <span id="sumator-localsunconnected">'.$allunconnected.'</span> (<span id="sumator-totalratio" class="sumator-ratio" data-formula="100 - (sumator-localsunconnected / sumator-localusers * 100)">'.$totalratio.'</span>)';

    // Note that sumators are initialized with the partial sum of amounts, and should be summed up with fragments.

    /*
    $data = array(array($cnxedstr, $allconnected), array($uncnxedstr, $allunconnected));
    $attrs = array('height' => '200', 'width' => 200);
    $allusers .= '<br/>'.local_vflibs_jqplot_simple_donut($data, 'total_'.$vhost->id.'_'.$i, 'report-vmoodle-user-charts', $attrs);
    */

    $totalrow = array($totalstr, $allusers, '<span id="sumator-remotes">'.$allremotes.'</span>', '<span id="sumator-suspendeds">'.$allsuspendeds.'</span>');
    for ($i = 0 ; $i < count($pfconfig) ; $i++) {
        $key = 'pfdata'.($i + 1);
        $totalrow[] = '<span id="sumator-'.$key.'">'.$$key.'</span>';
    }
    $table->data[] = $totalrow;

    $str .= $renderer->delegated_table($table);
}

// Prepare for Xls export :

$headerarr = array($table->xlshead);
$stdresultarr = $table->xlsdata;
