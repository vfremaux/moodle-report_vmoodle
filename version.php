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
 * Version details.
 *
 * @package     report_vmoodle
 * @category    report
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2010 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2016030800;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2020022400;        // Requires this Moodle version.
$plugin->component = 'report_vmoodle'; // Full name of the plugin (used for diagnostics).
$plugin->release = '3.9.0 (Build 2016030800)';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array('local_vmoodle' => 2017090101);

// Non moodle attributes.
$plugin->codeincrement = '3.9.0001';

