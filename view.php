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

require('../../config.php');

ob_start();
require_once($CFG->dirroot.'/report/vmoodle/locallib.php');
require_once($CFG->libdir.'/filelib.php');

// Security.

$systemcontext = context_system::instance();
require_login();
require_capability('report/vmoodle:view', $systemcontext);

$view = optional_param('view', 'cnxs', PARAM_TEXT);
$output = optional_param('output', 'html', PARAM_TEXT);

$url = new moodle_url('/report/vmoodle/view.php');
$PAGE->set_url($url, array('view' => $view));
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('vmoodlereport', 'report_vmoodle'));
$PAGE->set_heading(get_string('vmoodlereport', 'report_vmoodle'));

$thishost = new StdClass;
$thishost->name = $SITE->fullname;
$thishost->vdbtype = $CFG->dbtype;
$thishost->vdbname = $CFG->dbname;
$thishost->vhostname = $CFG->wwwroot;
$thishost->vdblogin = $CFG->dbuser;
$thishost->vdbpass = $CFG->dbpass;
$thishost->vdbprefix = $CFG->prefix;

$vhosts[] = $thishost;

if (preg_match('#'.@$CFG->mainhostprefix.'#', $CFG->wwwroot)) {
    $vhosts = $vhosts + $DB->get_records('block_vmoodle', array('enabled' => '1'));
}

// Print tabs with options for user.

if (!preg_match('/online|cnxs|roles|users|logs|modules|blocks|courses|formats|assignmenttypes|questiontypes|resourcetypes|sharedresources|forumtypes|userclasses|slowpages/', $view)) {
    if (preg_match('#'.@$CFG->mainhostprefix.'#', $CFG->wwwroot)) {
        $view = 'online';
    } else {
        $view = 'cnxs';
    }
}
$rows[0][] = new tabobject('online', new moodle_url('/report/vmoodle/view.php', array('view' => 'online')), get_string('online', 'report_vmoodle'));
$rows[0][] = new tabobject('cnxs', new moodle_url('/report/vmoodle/view.php', array('view' => 'cnxs')), get_string('cnxs', 'report_vmoodle'));
$rows[0][] = new tabobject('users', new moodle_url('/report/vmoodle/view.php', array('view' => 'users')), get_string('users', 'report_vmoodle'));
if (is_dir($CFG->dirroot.'/local/ent_installer')) {
    $rows[0][] = new tabobject('usersync', new moodle_url('/report/vmoodle/view.php', array('view' => 'usersync')), get_string('syncusers', 'local_ent_installer'));
}
$rows[0][] = new tabobject('logs', new moodle_url('/report/vmoodle/view.php', array('view' => 'logs')), get_string('logs', 'report_vmoodle'));
$rows[0][] = new tabobject('roles', new moodle_url('/report/vmoodle/view.php', array('view' => 'roles')), get_string('roles', 'report_vmoodle'));
$rows[0][] = new tabobject('userclasses', new moodle_url('/report/vmoodle/view.php', array('view' => 'userclasses')), get_string('userclasses', 'report_vmoodle'));
$rows[0][] = new tabobject('courses', new moodle_url('/report/vmoodle/view.php', array('view' => 'courses')), get_string('courses', 'report_vmoodle'));
$rows[0][] = new tabobject('modules', new moodle_url('/report/vmoodle/view.php', array('view' => 'modules')), get_string('modules', 'report_vmoodle'));
$rows[0][] = new tabobject('blocks', new moodle_url('/report/vmoodle/view.php', array('view' => 'blocks')), get_string('blocks', 'report_vmoodle'));
$rows[0][] = new tabobject('formats', new moodle_url('/report/vmoodle/view.php', array('view' => 'formats')), get_string('formats', 'report_vmoodle'));
$rows[0][] = new tabobject('assignmenttypes', new moodle_url('/report/vmoodle/view.php', array('view' => 'assignmenttypes')), get_string('assignmenttypes', 'report_vmoodle'));
$rows[0][] = new tabobject('questiontypes', new moodle_url('/report/vmoodle/view.php', array('view' => 'questiontypes')), get_string('questiontypes', 'report_vmoodle'));
$rows[0][] = new tabobject('resourcetypes', new moodle_url('/report/vmoodle/view.php', array('view' => 'resourcetypes')), get_string('resourcetypes', 'report_vmoodle'));

if ($sharedinstalled = $DB->get_record('modules', array('name' => 'sharedresource'))) {
    $rows[0][] = new tabobject('sharedresources', new moodle_url('/report/vmoodle/view.php', array('view' => 'sharedresources')), get_string('sharedresources', 'report_vmoodle'));
}
$rows[0][] = new tabobject('forumtypes', new moodle_url('/report/vmoodle/view.php', array('view' => 'forumtypes')), get_string('forumtypes', 'report_vmoodle'));

if (is_dir($CFG->dirroot.'/local/advancedperfs')) {
    $rows[0][] = new tabobject('slowpages', new moodle_url('/report/vmoodle/view.php', array('view' => 'slowpages')), get_string('slowpages', 'local_advancedperfs'));
}

$tabs = print_tabs($rows, $view, NULL, NULL, true);

if ($view == 'online') {
    include($CFG->dirroot.'/report/vmoodle/mnet_online.php');
}

if ($view == 'cnxs') {
    include($CFG->dirroot.'/report/vmoodle/mnet_general.php');
}

if ($view == 'users') {
    include($CFG->dirroot.'/report/vmoodle/mnet_users.php');
}

if (is_dir($CFG->dirroot.'/local/ent_installer')) {
    if ($view == 'usersync') {
        include($CFG->dirroot.'/report/vmoodle/mnet_usersync.php');
    }
}

if ($view == 'logs') {
    include($CFG->dirroot.'/report/vmoodle/mnet_logs.php');
}

if ($view == 'roles') {
    include($CFG->dirroot.'/report/vmoodle/mnet_roles.php');
}

if ($view == 'userclasses') {
    include($CFG->dirroot.'/report/vmoodle/mnet_userclasses.php');
}

if ($view == 'modules') {
    include($CFG->dirroot.'/report/vmoodle/mnet_modules.php');
}

if ($view == 'blocks') {
    include($CFG->dirroot.'/report/vmoodle/mnet_blocks.php');
}

if ($view == 'formats') {
    include($CFG->dirroot.'/report/vmoodle/mnet_formats.php');
}

if ($view == 'assignmenttypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_assignmenttypes.php');
}

if ($view == 'questiontypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_questiontypes.php');
}

if ($view == 'resourcetypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_resourcetypes.php');
}

if ($sharedinstalled) {
    if ($view == 'sharedresources') {
        include($CFG->dirroot.'/report/vmoodle/mnet_sharedresources.php');
    }
}

if ($view == 'forumtypes') {
    include($CFG->dirroot.'/report/vmoodle/mnet_forumtypes.php');
}

if (is_dir($CFG->dirroot.'/local/advancedperfs')) {
    if ($view == 'slowpages') {
        include($CFG->dirroot.'/report/vmoodle/mnet_slowpages.php');
    }
}

if ($view == 'courses') {
    include($CFG->dirroot.'/report/vmoodle/mnet_courses.php');
}

if ($output == 'html') {
    ob_end_clean();
    echo $OUTPUT->header();
    echo $tabs;
    echo $str;

    if (isset($stdresultarr)) {
        $formurl = new moodle_url('/report/vmoodle/view.php');
        echo '<form name="asxlsform" target="_blank" action="'.$formurl.'">';
        echo '<input type="hidden" name="view" value="'.$view.'">';
        echo '<input type="hidden" name="latin" value="">';
        echo '<input type="hidden" name="year" value="'.$year.'">';
        echo '<input type="hidden" name="output" value="asxls">';
        echo '<center><p><input type="button" name="asxls" value="'.get_string('asxls', 'report_vmoodle').'" onclick="document.forms[\'asxlsform\'].latin.value = 0; document.forms[\'asxlsform\'].submit()" /> <input type="button" name="asxls" value="'.get_string('asxlslatin', 'report_vmoodle').'" onclick="document.forms[\'asxlsform\'].latin.value = 1; document.forms[\'asxlsform\'].submit()" /></p></center>';
        echo '</form>';
    }

    echo $OUTPUT->footer();
} else {
    // print as xls the stdresultarr array
    require_once($CFG->libdir.'/excellib.class.php');

    $latin = optional_param('latin', false, PARAM_BOOL);

    $filename = 'vmoodle_report_'.$view.'_'.$year.'_'.date('d-M-Y', time()).'.xls';
    $workbook = new MoodleExcelWorkbook("-");
    // Sending HTTP headers
    ob_end_clean();

    $workbook->send($filename);
    $worksheet = vmoodle_report_write_init_xls($workbook, $view, $latin);

    $headerarr[] = array(get_string('hostname', 'report_vmoodle'), get_string('year', 'report_vmoodle'), get_string('objecttype', 'report_vmoodle'), get_string('objectcount', 'report_vmoodle'));

    vmoodle_report_write_results_xls($worksheet, $headerarr, 0, $latin);
    vmoodle_report_write_results_xls($worksheet, $stdresultarr, 1, $latin);

    $workbook->close();
}
