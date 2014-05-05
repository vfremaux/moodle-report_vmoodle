<?php // $Id: version.php,v 1.1 2012-06-28 22:56:47 vf Exp $

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
 * @copyright  2010 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2013072800;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2013040500;        // Requires this Moodle version
$plugin->component = 'report_vmoodle'; // Full name of the plugin (used for diagnostics)
$plugin->release = '2.5.0 (Build 2013072800)'; // Full name of the plugin (used for diagnostics)
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array('block_vmoodle' => 2013020801);
