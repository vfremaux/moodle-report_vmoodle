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
namespace report_vmoodle\fragment;

use \StdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/report/vmoodle/classes/fragment_base.class.php');

class users extends base {

    protected function get_data(&$dataresult = null) {
        global $DB;

        if (is_null($dataresult)) {
            $dataresult = new Stdclass;
        }

        $firstaccessclause = '';
        $cnxedstr = get_string('cnxed', 'report_vmoodle');
        $uncnxedstr = get_string('uncnxed', 'report_vmoodle');

        $pfconfig = explode(',', $this->config->profilefields);

        // Note that here the mnet host may be null if not mnet bound. so we use the VHost definition.
        $vhostname = $this->output->host_full_name($this->vhost->vhostname);

        list($insql, $inparams) = $DB->get_in_or_equal($pfconfig);

        // Prefetch profile info.
        $sql = "
            SELECT
                shortname, id
            FROM
                `{$this->vhost->vdbname}`.{$this->vhost->vdbprefix}user_info_field
            WHERE
                shortname $insql
        ";
        $fields = $DB->get_records_sql($sql, $inparams);

        $sql = "
            SELECT
                value
            FROM
                `{$this->vhost->vdbname}`.{$this->vhost->vdbprefix}config
            WHERE
                name = 'mnet_localhost_id'
        ";
        $localusershost = $DB->get_field_sql($sql);

        $profilefields = '';
        $profilejoins = '';
        $joinwheres = '';
        if ($fields) {
            $i = 1;
            foreach ($fields as $field) {
                $profilefields .= ', SUM(CASE WHEN pf'.$i.'.data IS NOT NULL AND pf'.$i.'.data > 0 THEN 1 ELSE 0 END) as pfdata'.$i."\n";
                $profilefields .= ', SUM(CASE WHEN u.firstaccess = 0 AND
                                                   u.mnethostid = '.$localusershost.' AND
                                                   pf'.$i.'.data IS NOT NULL AND
                                                   pf'.$i.'.data > 0 THEN 1 ELSE 0 END) as pfdata'.$i.'unc';

                $joinwheres = ' ON pf'.$i.'.userid = u.id AND pf'.$i.'.fieldid = '.$field->id;
                $profilejoins .= " LEFT JOIN `{$this->vhost->vdbname}`.{$this->vhost->vdbprefix}user_info_data as pf".$i.$joinwheres;

                $i++;
            }
        }

        $sql = "
            SELECT
                SUM(CASE WHEN u.suspended = 0 AND u.mnethostid = ".$localusershost." THEN 1 ELSE 0 END) as localusers,
                SUM(CASE WHEN u.suspended = 0 AND u.mnethostid != ".$localusershost." THEN 1 ELSE 0 END) as remoteusers,
                SUM(CASE WHEN u.firstaccess = 0 AND u.mnethostid = ".$localusershost." THEN 1 ELSE 0 END) as localunconnected,
                SUM(CASE WHEN u.suspended = 1 AND u.mnethostid = ".$localusershost." THEN 1 ELSE 0 END) as suspendedusers
                $profilefields
            FROM
                `{$this->vhost->vdbname}`.{$this->vhost->vdbprefix}user as u
                $profilejoins
            WHERE
                u.deleted = 0
                $firstaccessclause
        ";

        $hoststats = $DB->get_records_sql($sql);

        if ($hoststats) {
            foreach ($hoststats as $us) {

                $lus = $us->localusers;
                $luu = $us->localunconnected;
                $luc = $us->localusers - $us->localunconnected;
                $ratio = sprintf('%.1f', $luc / $lus * 100).'%';

                $row = new StdClass;
                $row->hostname = $this->output->host_full_name($this->vhost->vhostname);
                $dataresult->hostname = $this->vhost->vhostname;
                $localusers = $this->output->format_number($lus).' / '.$this->output->format_number($luu).' ('.$ratio.')';
                $data = array(array($cnxedstr, (int)$luc), array($uncnxedstr, (int)$luu));
                $attrs = array('height' => '150', 'width' => 150);
                $localusers .= '<br/>'.local_vflibs_jqplot_simple_donut($data, 'users_'.$this->vhost->id, 'report-vmoodle-user-charts', $attrs);
                $row->localusers = $localusers;
                $dataresult->locals = $luc;
                $dataresult->localsunconnected = $luu;
                $row->remoteusers = $this->output->format_number($us->remoteusers);
                $dataresult->remotes = $us->remoteusers;
                $row->suspended = $this->output->format_number($us->suspendedusers);
                $dataresult->suspendeds = $us->suspendedusers;

                for ($i = 0 ; $i < count($pfconfig) ; $i++) {
                    $row->hasfields = true;
                    $fieldtpl = new StdClass;
                    $key = 'pfdata'.($i + 1);
                    $unckey = 'pfdata'.($i + 1).'unc';
                    $pfu = 0 + @$us->$key;
                    $pfunc = 0 + @$us->$unckey;
                    $ratio = 0;
                    if ($pfu) {
                        $ratio = (1 - ($pfunc / $pfu)) * 100;
                    }
                    $value = $this->output->format_number($pfu).' / '.$this->output->format_number($pfunc).' ('.sprintf('%.1f', $ratio).'%)';
                    $data = array(array($cnxedstr, $pfu - $pfunc), array($uncnxedstr, $pfunc));
                    $attrs = array('height' => '150', 'width' => 150);
                    $value .= '<br/>'.local_vflibs_jqplot_simple_donut($data, 'field_'.$this->vhost->id.'_'.$i, 'report-vmoodle-user-charts', $attrs);
                    $fieldtpl->fieldvalue = $value;
                    $dataresult->$key = @$us->$key;
                    $row->fields[] = $fieldtpl;
                }
                return $row;

            }
        }

    }

    public function get_fragment(&$dataresult = null) {
        global $OUTPUT;

        $template = $this->get_data($dataresult);

        return $OUTPUT->render_from_template('report_vmoodle/fragmentusers', $template);
    }

}