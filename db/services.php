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
 * @package    local_webservices
 * @copyright  Copyleft
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_webservices_get_a_log_by_ids' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_a_log',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get a log of a student in a class at a day',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_logs_by_id' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_logs',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get attendance logs of students in a class base on the attendance ID',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_update_log' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'update_log',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Update status, timein, timeout in the report table based on the student ID
        and the session ID. If not need to update any field, leave this field null. If 
        there is no record, it will create a new one',
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_get_roles' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_roles',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get roles. If you give over empty lists, all roles will be returned.',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_schedules' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_room_schedules',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get all schedules of room in date',
        'type' => 'read',
        'capabilities' => '',
    ),
);
