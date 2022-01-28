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

define ('VMOODLE_KILOBYTE', 1024);
define ('VMOODLE_MEGABYTE', 1048576);
define ('VMOODLE_GIGABYTE', 1073741824);
define ('VMOODLE_TERABYTE', 1099511627776);

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
                               'files',
                               'modules',
                               'blocks',
                               'courses',
                               'formats',
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
        $taburl = new moodle_url('/report/vmoodle/view.php', array('view' => 'files'));
        $rows[0][] = new tabobject('files', $taburl, get_string('files', 'report_vmoodle'));
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

        $year = optional_param('year', date('Y'), PARAM_INT);

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

    public function format_size($size) {
        if ($size < 100) {
            return $size;
        }
        if ($size < VMOODLE_MEGABYTE) {
            return sprintf('%0.1fk', $size / VMOODLE_KILOBYTE);
        }
        if ($size < VMOODLE_GIGABYTE) {
            return sprintf('%0.2fM', $size / VMOODLE_MEGABYTE);
        }
        if ($size < VMOODLE_TERABYTE) {
            return sprintf('%0.2fG', $size / VMOODLE_GIGABYTE);
        }
        return sprintf('%0.3fT', $size / VMOODLE_TERABYTE);
    }

    function size_bar($size) {
        $str = '<br/>';

        if ($size == 0) {
            return '';
        }

        if ($size >= VMOODLE_KILOBYTE) {
            $str .= '<div class="vmoodle-kilo vmoodle-size-bar"></div>';
        }
        if ($size >= VMOODLE_MEGABYTE) {
            $str .= '<div class="vmoodle-mega vmoodle-size-bar"></div>';
        }
        if ($size >= VMOODLE_GIGABYTE) {
            $str .= '<div class="vmoodle-giga vmoodle-size-bar"></div>';
        }
        if ($size >= VMOODLE_TERABYTE) {
            $str .= '<div class="vmoodle-tera vmoodle-size-bar"></div>';
        }

        return $str;
    }

    public function host_full_name($vhostorname) {
        global $DB, $CFG, $SITE;

        if (empty($vhostorname)) {
            throw new coding_exception('Null host or hostname');
        }

        if (is_string($vhostorname)) {
            if ($vhostorname == $CFG->wwwroot) {
                return $SITE->fullname;
            }

            $vhostname = $vhostorname;
            $vhost = $DB->get_record('local_vmoodle', array('vhostname' => $vhostname));
        } else {
            $vhostname = $vhostorname->vhostname;
            $vhost = $vhostorname;
        }

        $mnetname = '';
        if ($mneth = $DB->get_record('mnet_host', array('wwwroot' => $vhostname))) {
            return $vhost->name." ({$mneth->name})";
        }

        return $vhost->name;
    }

    public function format_number($value) {
        if ($value == 0) {
            return '<span class="null-value">'.$value.'</span>';
        }
        return $value;
    }

    public function filter_form($additionalinputs = '', $allyears = true) {

        $currentyear = date('Y');
        $year = optional_param('year', $currentyear, PARAM_INT);
        $view = optional_param('view', 'cnxs', PARAM_TEXT);

        $template = new StdClass;

        $config = get_config('report_vmoodle');

        $hasoptions;
        if (!empty($config->backexploredepth)) {
            $startyear = $currentyear - $config->backexploredepth;
            for ($i = 0 ; $i <= $config->backexploredepth ; $i++) {
                $years[$startyear + $i] = $startyear + $i;
            }

            $template->additionalinputs = $additionalinputs;
            $template->view = $view;
            if ($allyears) {
                $years[9999] = get_string('allyears', 'report_vmoodle');
            }
            $template->yearselect = html_writer::select($years, 'year', $year, array());
            $hasoptions = true;
        }

        if ($hasoptions || !empty($additionalinputs)) {
            $template->gostr = get_string('apply', 'report_vmoodle');
            return $this->output->render_from_template('report_vmoodle/filterform', $template);
        }
    }

    public function graphcontrol_button() {
        $str = '';

        $str .= '<input type="button" class="btn" id="report-vmoodle-togglegraph-handle" value="'.get_string('hidegraphs', 'report_vmoodle').'"/>';

        return $str;
    }

    /**
     * Given an html table standard structure, renderers specially the table with delegated row containers.
     * It omits that the data[] value array contain empty arrays for which a row empty container will
     * be printed.
     * We expect the data being a table of objects giving ajax delegation information. We may allow
     * some rows being real data array, in which case they will be printed the same way than the standard
     * table.
     */
    public function delegated_table(html_table $table) {

        if (empty($table->head)) {
            throw new coding_exception('Empty head in vmoodle report table');
        }

        if (empty($table->align)) {
            throw new coding_exception('Empty aligns in vmoodle report table');
        }

        if (empty($table->size)) {
            throw new coding_exception('Empty sizes in vmoodle report table');
        }

        if (empty($table->width)) {
            $table->width = '100%';
        }

        $str = '';
        $str = '<table width="'.$table->width.'" class="generaltable">';
        $str .= '<tr>';
        $maxcol = count($table->head);
        for ($i = 0 ; $i < $maxcol; $i++) {
            $hd = $table->head[$i];
            $class = '^header';
            if ($i == 0) {
                $class .= ' firstcol';
            }
            if ($i == $maxcol - 1) {
                $class .= ' lastcol';
            }
            $class .= ' c'.$i;

            if (!empty($table->colclasses[$i])) {
                $class .= ' '.$table->colclasses[$i];
            }

            $class = ' '.core_text::strtolower($table->align[$i]).'align';

            $str .= '<th class="'.$class.'" scope="col">'.$hd.'</th>';
        }
        $str .= '</tr>';

        $maxrows = count($table->data);
        for ($i = 0; $i < $maxrows; $i++) {

            if ($i == 0) {
                $class .= ' firstrow';
            }
            if ($i == $maxrows - 1) {
                $class .= ' lastrow';
            }

            if (!empty($table->rowclasses[$i])) {
                $class .= ' '.$table->rowclasses[$i];
            }

            if (!empty($table->rowclasses[$i])) {
                $class .= ' '.$table->rowclasses[$i];
            }

            if (is_object($table->data[$i])) {
                $rowcontent = '';
                $delegationattrs = ' delegated-fragment="'.$table->data[$i]->fragment.'"';
                $delegationattrs .= ' delegated-context="'.urlencode($table->data[$i]->contextstring).'"';
                if ($year = optional_param('year', false, PARAM_INT)) {
                    $delegationattrs .= ' delegated-filter="year_'.$year.'"';
                }
                $str .= '<tr class="delegated-content '.$class.'" '.$delegationattrs.' ></tr>';
            } else {
                $str .= '<tr class="undelegated '.$class.'" >';
                for ($j = 0; $j < $maxcol; $j++) {
                    $align = 'leftalign';
                    if (!empty($table->align[$j])) {
                        $align = $table->align[$j];
                    }
                    if (!empty($table->data[$i][$j])) {
                        $str .= '<td class="cell '.$align.' c'.$j.'">'.$table->data[$i][$j].'</td>';
                    } else {
                        $str .= '<td class="cell '.$align.' c'.$j.'"></td>';
                    }
                }
                $str .= '</tr>';
            }

        }
        $str .= '<table>';

        return $str;
    }
}