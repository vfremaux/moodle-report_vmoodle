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

$plugin->version   = 2023062605;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2020060900;        // Requires this Moodle version.
$plugin->component = 'report_vmoodle'; // Full name of the plugin (used for diagnostics).
$plugin->release = '4.1.0 (Build 2023062605)';
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array('local_vmoodle' => 2017090101);
$plugin->supported = [401, 402];

// Non moodle attributes.
$plugin->codeincrement = '4.1.0001';

