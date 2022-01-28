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

defined('MOODLE_INTERNAL') || die();

abstract class base {

    protected $host;
    protected $vhost;
    protected $config;
    protected $output;

    public function __construct($hostorroot, $options = array()) {
        global $DB, $PAGE;

        if (is_string($hostorroot)) {
            $hostroot = $hostorroot;
            $this->host = $DB->get_record('mnet_host', array('wwwroot' => $hostroot));
        } else {
            $this->host = $hostorroot;
        }
        $this->vhost = $DB->get_record('local_vmoodle', array('vhostname' => $hostroot));
        $this->options = $options;
        $this->config = get_config('report_vmoodle');
        $this->output = $PAGE->get_renderer('report_vmoodle');
    }

    /**
     * returns a report "per host" fragment row.
     * @param objectref &$dataresult additional data that can be sent to the javascript
     * fragment receiver.
     * @return an html rendererd table row with actualized data.
     */
    abstract public function get_fragment();

}