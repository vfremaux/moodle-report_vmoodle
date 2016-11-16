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

    function graphbar($value, $valuemax, $width = 300) {
        $str = '';

        $relwidth = ($valuemax != 0) ? $value / $valuemax : 0;
        $str .= '<div class="outer-graphbar" style="width:'.$width.'px">';
        $str .= '<div class="inner-graphbar" style="width:'.round($width * $relwidth).'px">';
        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }
}