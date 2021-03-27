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
 * Web service library functions
 *
 * @package    local_webservices
 * @copyright  Copyleft
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Web service API definition.
 *
 * @package local_webservices
 * @copyright Copyleft
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class student_object {
    public $id;
    public $name;
    public $timein;
    public $timeout;
    public $status;
}

class local_webservices_external extends external_api {

    // Functionset for get_roles() ************************************************************************************************.

    /**
     * Parameter description for get_a_log().
     *
     * @return external_function_parameters.
     */
    public static function get_a_log_parameters() {
        return new external_function_parameters(
            array(
                'studentid' => new external_value(PARAM_TEXT, 'student ID parameter'),
                'sessionid'  => new external_value(PARAM_INT, 'session ID parameter'),
            )
        );
    }

    /**
     * Return roleinformation.
     *
     * This function returns roleid, rolename and roleshortname for all roles or for given roles.
     *
     * @param string $studentid Student ID.
     * @param string $sessionid session ID.
     * @return object The student's log.
     * @throws invalid_parameter_exception
     */
    public static function get_a_log(string $studentid, string $sessionid)
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_a_log_parameters(), array(
            'studentid' => $studentid,
            'sessionid' => $sessionid));
        global $DB;

        $sql = "SELECT l.*, a.course
                  FROM {attendance_log} l
            LEFT JOIN {attendance_session} s ON s.id = l.sessionid
            LEFT JOIN {attendance} a ON a.id = s.attendanceid
             WHERE (l.studentid = :studentid AND l.sessionid = :sessionid)
              ORDER BY l.id ASC";

        return $DB->get_records_sql($sql,array('studentid'=>$studentid,'sessionid'=>$sessionid));
    }

    /**
     * Parameter description for create_sections().
     *
     * @return external_description
     */
    public static function get_a_log_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'log ID', VALUE_DEFAULT, null),
                    'studentid' => new external_value(PARAM_TEXT, 'student ID', VALUE_DEFAULT, null),
                    'course' => new external_value(PARAM_TEXT, 'course ID', VALUE_DEFAULT, null),
                    'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'timein' => new external_value(PARAM_TEXT, 'time when the student checkin', VALUE_DEFAULT, null),
                    'timeout' => new external_value(PARAM_TEXT, 'time when the student go out', VALUE_DEFAULT, null),
                    'status' => new external_value(PARAM_INT, 'status of the student', VALUE_DEFAULT, null),
                )
            )
        );
    }

    public static function get_logs_parameters() {
        return new external_function_parameters(
            array(
                'attendanceid' => new external_value(PARAM_INT, 'Attendance ID'),
            )
        );
    }
    public static function get_logs($attendanceid) {
        $params = self::validate_parameters(self::get_logs_parameters(), array(
            'attendanceid' => $attendanceid
            )
        );
        global $DB;
        $sql = "SELECT l.*, a.course
                FROM {attendance_log} l
                LEFT JOIN {attendance_session} s ON l.sessionid = s.id 
                LEFT JOIN {attendance} a 
                ON s.attendanceid = a.id 
                WHERE a.id = :attendanceid
                ORDER BY l.id ASC";

        return $DB->get_records_sql($sql,array('attendanceid' => $attendanceid));
    }

    public static function get_logs_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'log ID', VALUE_DEFAULT, null),
                    'studentid' => new external_value(PARAM_TEXT, 'student ID', VALUE_DEFAULT, null),
                    'course' => new external_value(PARAM_TEXT, 'class ID', VALUE_DEFAULT, null),
                    'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'timein' => new external_value(PARAM_TEXT, 'time when the student checkin', VALUE_DEFAULT, null),
                    'timeout' => new external_value(PARAM_TEXT, 'time when the student go out', VALUE_DEFAULT, null),
                    'status' => new external_value(PARAM_INT, 'status of the student', VALUE_DEFAULT, null),
                )
            )
        );
    }

    public static function get_session_detail_parameters() {
        return new external_function_parameters(
            array(
                'sessionid' => new external_value(PARAM_INT, 'Session ID'),
            )
        );
    }
    public static function get_session_detail(int $sessionid) {
        $params = self::validate_parameters(self::get_session_detail_parameters(), array(
                'sessionid' => $sessionid
            )
        );
        global $DB;
        $sql1 = "SELECT s.*, course.id as courseid, course.fullname
                FROM {attendance_session} s
                LEFT JOIN {attendance} a ON s.attendanceid = a.id
                LEFT JOIN {course} course ON a.course = course.id
                WHERE s.id = :sessionid";
        $data = $DB->get_record_sql($sql1,array('sessionid' => $sessionid));


        $sql2 = "SELECT l.*, u.lastname, u.firstname
                FROM {attendance_log} l
                LEFT JOIN {user} u ON l.studentid = u.id
                LEFT JOIN {attendance_session} s ON l.sessionid = s.id
                WHERE s.id = :sessionid";

        $students = $DB->get_records_sql($sql2,array('sessionid' => $sessionid));
        $students_array = array();
        foreach ($students as $student) {
            if (!empty($student)) {
                $student_object = new student_object();
                $student_object->id = $student->studentid;
                $student_object->name = $student->lastname. ' ' .$student->firstname;
                $student_object->timein = $student->timein;
                $student_object->timeout = $student->timeout;
                $student_object->status = $student->status;
                $students_array[] = $student_object;
            }
        }
        return array('id'=>(int) $data->courseid,'name'=>$data->fullname,'sessdate'=>
            $data->sessdate,'lastdate'=>$data->lastdate,'class'=>$data->lesson,
            'room'=>$data->roomid,'students'=>$students_array);
    }
    public static function get_session_detail_returns() {
           return new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, null),
                    'name' => new external_value(PARAM_TEXT, 'course name', VALUE_DEFAULT, null),
                    'sessdate' => new external_value(PARAM_INT, 'session start time', VALUE_DEFAULT, null),
                    'lastdate' => new external_value(PARAM_INT, 'session end time', VALUE_DEFAULT, null),
                    'class' => new external_value(PARAM_INT, 'lesson number', VALUE_DEFAULT, null),
                    'room' => new external_value(PARAM_TEXT, 'room ID', VALUE_DEFAULT, null),
                    'students' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                                'name' => new external_value(PARAM_TEXT, 'student name', VALUE_DEFAULT, null),
                                'timein' => new external_value(PARAM_INT, 'checkin time', VALUE_DEFAULT, null),
                                'timeout' => new external_value(PARAM_INT, 'checkout time', VALUE_DEFAULT, null),
                                'status' => new external_value(PARAM_INT, 'status number', VALUE_DEFAULT, null),
                            )
                        )
                    ),
                )
            );
    }


    /**
     * Parameter description for update_log().
     *
     * @return external_function_parameters.
     */


    public static function update_log_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'studentid' => new external_value(PARAM_INT, 'Student ID parameter'),
                'sessionid'  => new external_value(PARAM_INT, 'Session ID parameter'),
                'timein' => new external_value(PARAM_INT, 'Checkin timestamp',VALUE_DEFAULT,0),
                'timeout' => new external_value(PARAM_INT, 'Checkout timestamp',VALUE_DEFAULT,0),
                'status'  => new external_value(PARAM_INT, 'New status number',VALUE_DEFAULT,-1),
            )
        );
    }

    /**
     *
     *
     * This function will update (optionally) status, timein and timeout. If this student's record
     * is not found, it will create a new record in the log table.
     *
     * @param string $studentid Student ID (required).
     * @param int $sessionid Session ID (required).
     * @param int $timein Datetime when the student checkin (can be null if not need to update).
     * @param int $timeout Datetime when the student out (can be null if not need to update).
     * @param int $status New status number (can be null if not need to update).
     * @return array
     * @throws invalid_parameter_exception|dml_exception
     */

    public static function update_log(string $studentid, int $sessionid, int $timein, int $timeout, int $status): array
    {
        $params = self::validate_parameters(self::update_log_parameters(), array(
                'studentid' => $studentid,
                'sessionid' => $sessionid,
                'timein' => $timein,
                'timeout' => $timeout,
                'status' => $status
            )
        );

        global $DB;


        $sql = "SELECT r.*
                FROM {attendance_log} r 
                WHERE r.studentid = :studentid AND r.sessionid = :sessionid";
        $result = $DB->get_record_sql($sql,array('studentid'=>$studentid,'sessionid'=>$sessionid),IGNORE_MISSING);

        $return = array('message' => '');
        if ($result == false)
        {

            $data = (object) array('studentid'=>$studentid,'sessionid'=>$sessionid,
                'timein'=>null,'timeout'=>null, 'status'=>null);
            if ($timein)
            {
                $data->timein = $timein;
            }
            if ($timeout)
            {
                $data->timeout = $timeout;
            }
            if ($status!=-1)
            {
                $data->status = $status;
            }
            if ($DB->insert_record('attendance_log',$data))
            {
                $return['message'] = "Inserted new record into the database successfully";
            }
            else {
                $return['message'] = "Couldn't insert new record into the database";
            }
        }
        else {
            $data = (object) array('id'=>$result->id,'status'=>$result->status,'timein'=>$result->timein,
                'timeout'=>$result->timeout);
            if ($timein)
            {
                $data->timein = $timein;
            }
            if ($timeout)
            {
                $data->timeout = $timeout;
            }
            if ($status!=-1)
            {
                $data->status = $status;
            }
            if ($DB->update_record('attendance_log',$data)) {
                $return['message'] = "Updated the log successfully";
            }
            else {
                $return['message'] = "Couldn't update the log";
            }
        }
        return $return;
    }

    public static function update_log_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
            'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }


    // Functionset for get_roles() ************************************************************************************************.

    public static function get_roles_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'Username'),
            )
        );
    }

    /**
     * Return roleinformation.
     *
     * This function returns roleid, rolename and roleshortname for all roles or for given roles.
     *
     * @param array $ids List of roleids.
     * @param array $shortnames List of role shortnames.
     * @param array $names List of role names.
     * @return array Array of arrays with role informations.
     */
    public static function get_roles($username) {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_roles_parameters(), array(
            'username' => $username));

            global $DB;

            $sql = "SELECT u.id AS id, username, firstname, lastname, roleid, r.name AS role, shortname
            FROM {user} u
            JOIN {role_assignments} ra on ra.userid = u.id
            JOIN {role} r on r.id = ra.roleid
            WHERE u.username = :username";
    
            return $DB->get_records_sql($sql,array('username'=>$username));
    }

    /**
     * Parameter description for create_sections().
     *
     * @return external_description
     */
    public static function get_roles_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'role id', VALUE_DEFAULT, null),
                    'username' => new external_value(PARAM_TEXT, 'username', VALUE_DEFAULT, null),
                    'firstname' => new external_value(PARAM_TEXT, 'firstname', VALUE_DEFAULT, null),
                    'lastname' => new external_value(PARAM_TEXT, 'lastname', VALUE_DEFAULT, null),
                    'roleid' => new external_value(PARAM_INT, 'id of role', VALUE_DEFAULT, null),
                    'role' => new external_value(PARAM_TEXT, 'name of role', VALUE_DEFAULT, null),
                    'shortname' => new external_value(PARAM_TEXT, 'shortname of role', VALUE_DEFAULT, null),
                )
            )
        );
    }




////////////
public static function get_room_schedules_parameters() {
    return new external_function_parameters(
        array(
            'roomid' => new external_value(PARAM_TEXT, 'room ID parameter',VALUE_OPTIONAL),
            'date'  => new external_value(PARAM_INT, 'Date of room schedules',VALUE_OPTIONAL)
        )
    );
}

/**
 * Return roleinformation.
 *
 * This function returns roleid, rolename and roleshortname for all roles or for given roles.
 *
 * @param string $studentid Student ID.
 * @param string $classid Class ID.
 * @param string $scheduleid Schedule ID.
 * @return object The student's report.
 * @throws invalid_parameter_exception
 */
public static function get_room_schedules(string $roomid,int $date)
{
    // Validate parameters passed from web service.
    $params = self::validate_parameters(self::get_room_schedules_parameters(), array(
        'roomid' => $roomid,
        'date' => $date,
    ));
    global $DB;

    $sql = "SELECT  s.id as id, attendanceid,course as courseid, roomid, sessdate, lastdate, fullname, shortname,startdate
            FROM {attendance_session} s
            LEFT JOIN {attendance} a ON s.attendanceid = a.id 
            LEFT JOIN {course} c ON course = c.id
            WHERE (s.roomid = :roomid AND s.sessdate > ( :date div 86400000)*86400000 AND s.lastdate < (( :date2 div 86400000) + 1 )*86400000)
    ";
    $res=$DB->get_records_sql($sql,array('roomid'=>$roomid,'date'=>$date,'date2'=>$date));
    return $res;
    }

    /**
     * Parameter description for create_sections().
     *
     * @return external_description
     */
    public static function get_room_schedules_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'attendanceid' => new external_value(PARAM_INT, 'attendance ID', VALUE_DEFAULT, null),
                    'roomid' => new external_value(PARAM_TEXT, 'room ID', VALUE_DEFAULT, null),
                    'courseid' => new external_value(PARAM_TEXT, 'course ID', VALUE_DEFAULT, null),
                    'sessdate' => new external_value(PARAM_INT, 'start time of session', VALUE_DEFAULT, null),
                    'lastdate' => new external_value(PARAM_INT, 'end time of session', VALUE_DEFAULT, null),
                    'startdate' => new external_value(PARAM_INT, 'startdate of course', VALUE_DEFAULT, null),
                    'fullname' => new external_value(PARAM_TEXT, 'fullname of course', VALUE_DEFAULT, null),
                    'shortname' => new external_value(PARAM_TEXT, 'shortname of course', VALUE_DEFAULT, null),
                )
            )
        );
    }

}
