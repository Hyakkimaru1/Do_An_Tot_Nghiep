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
 * Web service definitions for local_wsgetroles
 *
 * @package    local_wsgetroles
 * @copyright  2020 corvus albus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'get_a_report_by_ids' => array(
        'classname' => 'local_wsgetreports_external',
        'methodname' => 'get_a_report',
        'classpath' => 'local/wsgetreports/externallib.php',
        'description' => 'Get a report of a student in a class at a day',
        'type' => 'read',
        'capabilities' => '',
    ),
    'get_reports_by_id' => array(
        'classname' => 'local_wsgetreports_external',
        'methodname' => 'get_reports',
        'classpath' => 'local/wsgetreports/externallib.php',
        'description' => 'Get reports of students in a class base on the attendance ID',
        'type' => 'read',
        'capabilities' => '',
    ),
);
