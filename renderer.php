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

class report_vmoodle_renderer extends plugin_renderer_base {

    public function graphbar($value, $valuemax, $width = 300) {
        $str = '';

        $relwidth = ($valuemax != 0) ? $value / $valuemax : 0;
        $str .= '<div class="outer-graphbar" style="width:'.$width.'px">';
        $str .= '<div class="inner-graphbar" style="width:'.round($width * $relwidth).'px">';
        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }

    public function tabs($view) {
        global $CFG, $DB;

        // Print tabs with options for user.

        $availableviews = array('online',
                               'cnxs',
                               'roles',
                               'users',
                               'logs',
                               'modules',
                               'blocks',
                               'courses',
                               'formats',
                               'assignmenttypes',
                               'questiontypes',
                               'resourcetypes',
                               'sharedresources',
                               'forumtypes',
                               'userclasses',
                               'slowpages');

        if (!in_array($view, $availableviews)) {
            if (preg_match('#'.@$CFG->mainhostprefix.'#', $CFG->wwwroot)) {
                $view = 'online';
            } else {
                $view = 'cnxs';
            }
        }
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'online'));
        $rows[0][] = new tabobject('online', $taburl, get_string('online', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'cnxs'));
        $rows[0][] = new tabobject('cnxs', $taburl, get_string('cnxs', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'users'));
        $rows[0][] = new tabobject('users', $taburl, get_string('users', 'report_vmoodle'));
        if (is_dir($CFG->dirroot.'/local/ent_installer')) {
            $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'usersync'));
            $rows[0][] = new tabobject('usersync', $taburl, get_string('syncusers', 'local_ent_installer'));
        }
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'logs'));
        $rows[0][] = new tabobject('logs', $taburl, get_string('logs', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'roles'));
        $rows[0][] = new tabobject('roles', $taburl, get_string('roles', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'userclasses'));
        $rows[0][] = new tabobject('userclasses', $taburl, get_string('userclasses', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'courses'));
        $rows[0][] = new tabobject('courses', $taburl, get_string('courses', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'modules'));
        $rows[0][] = new tabobject('modules', $taburl, get_string('modules', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'blocks'));
        $rows[0][] = new tabobject('blocks', $taburl, get_string('blocks', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'formats'));
        $rows[0][] = new tabobject('formats', $taburl, get_string('formats', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'assignmenttypes'));
        $rows[0][] = new tabobject('assignmenttypes', $taburl, get_string('assignmenttypes', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'questiontypes'));
        $rows[0][] = new tabobject('questiontypes', $taburl, get_string('questiontypes', 'report_vmoodle'));
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'resourcetypes'));
        $rows[0][] = new tabobject('resourcetypes', $taburl, get_string('resourcetypes', 'report_vmoodle'));

        if ($sharedinstalled = $DB->get_record('modules', array('name' => 'sharedresource'))) {
            $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'sharedresources'));
            $rows[0][] = new tabobject('sharedresources', $taburl, get_string('sharedresources', 'report_vmoodle'));
        }
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'forumtypes'));
        $rows[0][] = new tabobject('forumtypes', $taburl, get_string('forumtypes', 'report_vmoodle'));

        if (is_dir($CFG->dirroot.'/local/advancedperfs')) {
            $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'slowpages'));
            $rows[0][] = new tabobject('slowpages', $taburl, get_string('slowpages', 'local_advancedperfs'));
        }

        $tabs = print_tabs($rows, $view, null, null, true);

        return $tabs;
    }

    public function xlsexport($view) {

        $year = optional_param('year', 2010, PARAM_INT);

        $formurl = new moodle_url('/report/vmoodle/view.php');

        $str = '';

        $str .= '<form name="asxlsform" target="_blank" action="'.$formurl.'">';
        $str .= '<input type="hidden" name="view" value="'.$view.'">';
        $str .= '<input type="hidden" name="latin" value="">';
        $str .= '<input type="hidden" name="year" value="'.$year.'">';
        $str .= '<input type="hidden" name="output" value="asxls">';
        $jshandler = 'document.forms[\'asxlsform\'].latin.value = 0; document.forms[\'asxlsform\'].submit()';
        $str .= '<center><p><input type="button" name="asxls" value="'.get_string('asxls', 'report_vmoodle').'" onclick="'.$jshandler.'" />';
        $jshandler = 'document.forms[\'asxlsform\'].latin.value = 1; document.forms[\'asxlsform\'].submit()';
        $str .= ' <input type="button" name="asxls" value="'.get_string('asxlslatin', 'report_vmoodle').'" onclick="'.$jshandler.'" /></p></center>';
        $str .= '</form>';

        return $str;
    }
}