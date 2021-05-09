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
    'local_webservices_frontend_get_feedbacks_pagination' => array(
        'classname' => 'local_webservices_frontend',
        'methodname' => 'get_feedbacks_pagination',
        'classpath' => 'local/webservices/externallib_frontend.php',
        'description' => 'Get all feedbacks based on session ID with pagination',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_frontend_get_action_logs_pagination' => array(
        'classname' => 'local_webservices_frontend',
        'methodname' => 'get_action_logs_pagination',
        'classpath' => 'local/webservices/externallib_frontend.php',
        'description' => 'Get all attendance action logs based on session ID with pagination',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_frontend_get_courses_pagination' => array(
        'classname' => 'local_webservices_frontend',
        'methodname' => 'get_courses_pagination',
        'classpath' => 'local/webservices/externallib_frontend.php',
        'description' => 'Get all checkin-courses with pagination',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_a_log_by_ids' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_a_log',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get a log of a student in a class at a day based on student ID and session ID',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_logs_by_id' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_logs',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get attendance logs of students in a class based on the attendance ID',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_student_logs_by_course_id' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_student_logs_by_course_id',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get all attendance logs of a student in a course based on student ID and course ID',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_logs_by_course_id' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_logs_by_course_id',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get attendance logs of students in a class based on the course ID',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_session_detail' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_session_detail',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get session detail based on session ID',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_create_attendance' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'create_attendance',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => 'Create attendance record based on course ID',
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_create_sessions' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'create_sessions',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => 'Create session records based on course ID, room ID and lessons number',
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_create_logs' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'create_logs',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => 'Create log records based on student ID and course ID',
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_update_session' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'update_session',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => "Update session date, last date and room ID based on the session ID. 
        If not need to update any field, don't pass that field",
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_update_log' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'update_log',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => "Update status, timein, timeout in the log table based on the student ID
        and the session ID. If not need to update any field, don't pass that field",
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_checkin' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'checkin',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => "Create the log when a student checkin by username and room ID",
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_create_feedback' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'create_feedback',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => "Create a feedback record in the database",
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
    'local_webservices_get_teacher_courses' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_teacher_courses',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get all courses of teacher',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_rooms' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_rooms',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get all rooms',
        'type' => 'read',
        'capabilities' => '',
    ),
);
