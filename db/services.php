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
        'description' => 'Get all attendance logs of a student in a course based on username and course ID',
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
    'local_webservices_get_images' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_images',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get images url of a student based on username',
        'type' => 'read',
        'capabilities' => '',
    ),
    'local_webservices_get_sessions_by_course_id' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_sessions_by_course_id',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get sessions infomation by course ID',
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
    'local_webservices_update_log' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'update_log',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => "Update status, timein, timeout in the log table based on the student ID or username
        and the session ID. If not need to update any field, don't pass that field",
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_checkin_online' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'checkin_online',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => "Create the log and upload checkin images when a student checkin in an online session",
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
    'local_webservices_create_images' => array(
        'classname' => 'local_webservices_external_write',
        'methodname' => 'create_images',
        'classpath' => 'local/webservices/externallib_write.php',
        'description' => "Create a record that contains 3 images url of a student",
        'type' => 'write',
        'capabilities' => '',
    ),
    'local_webservices_get_roles' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_roles',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get infomation of a user and his/her role',
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
    'local_webservices_get_campus' => array(
        'classname' => 'local_webservices_external',
        'methodname' => 'get_campus',
        'classpath' => 'local/webservices/externallib.php',
        'description' => 'Get all campus short name',
        'type' => 'read',
        'capabilities' => '',
    ),
);
$services = array(
    'Attendance' => array(
        'functions' => array(
            'mod_attendance_add_attendance',
            'mod_attendance_remove_attendance',
            'mod_attendance_add_session',
            'mod_attendance_remove_session',
            'mod_attendance_get_courses_with_today_sessions',
            'mod_attendance_get_session',
            'mod_attendance_update_user_status'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'mod_attendance'
    )
);
