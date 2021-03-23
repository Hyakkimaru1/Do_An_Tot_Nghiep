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
 * @package    local_wsgetreports
 * @copyright  Copyleft
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_wsgetreports_get_a_report_by_ids' => array(
        'classname' => 'local_wsgetreports_external',
        'methodname' => 'get_a_report',
        'classpath' => 'local/wsgetreports/externallib.php',
        'description' => 'Get a report of a student in a class at a day',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_wsgetreports_get_reports_by_id' => array(
        'classname' => 'local_wsgetreports_external',
        'methodname' => 'get_reports',
        'classpath' => 'local/wsgetreports/externallib.php',
        'description' => 'Get reports of students in a class base on the attendance ID',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_wsgetreports_update_log' => array(
        'classname' => 'local_wsgetreports_external',
        'methodname' => 'update_log',
        'classpath' => 'local/wsgetreports/externallib.php',
        'description' => 'Update status, timein, timeout in the report table based on the student ID
        and the session ID. If not need to update any field, leave this field null. If 
        there is no record, it will create a new one',
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_wsgetreports_get_roles' => array(
        'classname' => 'local_wsgetreports_external',
        'methodname' => 'get_roles',
        'classpath' => 'local/wsgetreports/externallib.php',
        'description' => 'Get roles. If you give over empty lists, all roles will be returned.',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_wsgetreports_get_schedules' => array(
        'classname' => 'local_wsgetreports_external',
        'methodname' => 'get_schedules',
        'classpath' => 'local/wsgetreports/externallib.php',
        'description' => 'Get schedules.',
        'type' => 'read',
        'capabilities' => '',
    ),
);
