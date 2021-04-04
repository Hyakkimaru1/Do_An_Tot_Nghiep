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

class student_log {
    public $studentid;
    public $name;
    public $reports;
}

class local_webservices_external extends external_api {

    public static function get_courses_pagination_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'page' => new external_value(PARAM_INT, 'page number'),
                'pagesize'  => new external_value(PARAM_INT, 'page size'),
                'value' => new external_value(PARAM_TEXT,'search value',VALUE_DEFAULT,''),
                'filter' => new external_value(PARAM_TEXT, 'filter criteria',VALUE_DEFAULT,''),
                'order'  => new external_value(PARAM_TEXT, 'order criteria',VALUE_DEFAULT,''),
            )
        );
    }

    public static function get_courses_pagination(int $page, int $pagesize,
                                                  string $value, string $filter, string $order): array
    {
        $params = self::validate_parameters(self::get_courses_pagination_parameters(), array(
            'page' => $page,
            'pagesize' => $pagesize,
            'value' => $value,
            'filter' => $filter,
            'order' => $order
        ));

        global $DB;
        $result = null;
        if ($value != '') {
            $filter = `course` . $filter;
            $sql = "SELECT course.*
                FROM {attendance} a
                LEFT JOIN {course} course ON course.id = a.course
                WHERE course.fullname LIKE :string1 OR course.shortname LIKE :string2
                ORDER BY $filter $order";
            $result = $DB->get_records_sql($sql, array('string1' => '%' . $value . '%',
                'string2' => '%' . $value . '%'));
        }
        else {
            $sql = "SELECT course.*
                FROM {attendance} a
                LEFT JOIN {course} course ON course.id = a.course
                 ";
            $result = $DB->get_records_sql($sql, array());

        }

        $courses = array();
        $index = 0;
        foreach ($result as $item => $value) {
            if (($page-1)*$pagesize<=$index && $page*$pagesize>$index) {
                $courses[] = $value;
                $index++;
            }
            else if ($index<($page-1)*$pagesize) {
                $index++;
            }
            else break;

        }
        return array('totalrecords' => count($result), 'courses' => $courses);
    }

    public static function get_courses_pagination_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'totalrecords' => new external_value(PARAM_INT,'total records',VALUE_DEFAULT,null),
                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, null),
                            'fullname' => new external_value(PARAM_TEXT, "course's full name", VALUE_DEFAULT, null),
                            'shortname' => new external_value(PARAM_TEXT, "course's short name", VALUE_DEFAULT, null),
                        )
                    )
                )
            )
        );
    }

    /**
     * Parameter description for get_a_log().
     *
     * @return external_function_parameters.
     */
    public static function get_a_log_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'studentid' => new external_value(PARAM_INT, 'student ID parameter'),
                'sessionid'  => new external_value(PARAM_INT, 'session ID parameter'),
            )
        );
    }

    /**
     * Return roleinformation.
     *
     * This function returns roleid, rolename and roleshortname for all roles or for given roles.
     *
     * @param int $studentid Student ID.
     * @param int $sessionid session ID.
     * @return array The student's log.
     * @throws invalid_parameter_exception
     */
    public static function get_a_log(int $studentid, int $sessionid): array
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_a_log_parameters(), array(
            'studentid' => $studentid,
            'sessionid' => $sessionid));
        global $DB;

        $sql = "SELECT l.*, a.course,r.name as room,r.campus, s.lesson
                  FROM {attendance_log} l
            LEFT JOIN {attendance_sessions} s ON s.id = l.sessionid
            LEFT JOIN {room} r ON r.id = s.roomid
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
                    'studentid' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                    'course' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, null),
                    'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'lesson' => new external_value(PARAM_INT, 'lesson number', VALUE_DEFAULT, null),
                    'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                    'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                    'timein' => new external_value(PARAM_INT, 'timestamp when the student checkin', VALUE_DEFAULT, null),
                    'timeout' => new external_value(PARAM_INT, 'timestamp when the student go out', VALUE_DEFAULT, null),
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
        $sql = "SELECT l.*, a.course,r.name as room, r.campus, s.lesson
                FROM {attendance_log} l
                LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                LEFT JOIN {room} r ON r.id = s.roomid
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
                    'studentid' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                    'course' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, null),
                    'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'lesson' => new external_value(PARAM_INT, 'lesson number', VALUE_DEFAULT, null),
                    'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                    'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                    'timein' => new external_value(PARAM_INT, 'time when the student checkin', VALUE_DEFAULT, null),
                    'timeout' => new external_value(PARAM_INT, 'time when the student go out', VALUE_DEFAULT, null),
                    'status' => new external_value(PARAM_INT, 'status of the student', VALUE_DEFAULT, null),
                )
            )
        );
    }


    public static function get_student_logs_by_course_id_parameters() {
        return new external_function_parameters(
            array(
                'studentid' => new external_value(PARAM_INT, 'student ID parameter'),
                'courseid'  => new external_value(PARAM_INT, 'course ID parameter'),
            )
        );
    }

    /**

     *
     * This function returns student's logs in this course.
     *
     * @param int $studentid Student ID.
     * @param int $courseid Course ID.
     * @return array The student's logs.
     * @throws invalid_parameter_exception|dml_exception
     */
    public static function get_student_logs_by_course_id(int $studentid, int $courseid): array
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_student_logs_by_course_id_parameters(), array(
            'studentid' => $studentid,
            'courseid' => $courseid));
        global $DB;

        $sql = "SELECT l.*, a.course,r.name as room,r.campus, s.lesson
                  FROM {attendance_log} l
            LEFT JOIN {attendance_sessions} s ON s.id = l.sessionid
            LEFT JOIN {room} r ON r.id = s.roomid
            LEFT JOIN {attendance} a ON a.id = s.attendanceid
             WHERE (l.studentid = :studentid AND a.course = :courseid)
              ORDER BY l.id ASC";

        return $DB->get_records_sql($sql,array('studentid'=>$studentid,'courseid'=>$courseid));
    }

    /**
     * Parameter description for create_sections().
     *
     * @return external_description
     */
    public static function get_student_logs_by_course_id_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'log ID', VALUE_DEFAULT, null),
                    'studentid' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                    'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'lesson' => new external_value(PARAM_INT, 'lesson number', VALUE_DEFAULT, null),
                    'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                    'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                    'timein' => new external_value(PARAM_INT, 'timestamp when the student checkin', VALUE_DEFAULT, null),
                    'timeout' => new external_value(PARAM_INT, 'timestamp when the student go out', VALUE_DEFAULT, null),
                    'status' => new external_value(PARAM_INT, 'status of the student', VALUE_DEFAULT, null),
                )
            )
        );
    }



    public static function get_logs_by_course_id_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            )
        );
    }
    public static function get_logs_by_course_id(int $courseid): array
    {
        $params = self::validate_parameters(self::get_logs_by_course_id_parameters(), array(
                'courseid' => $courseid
            )
        );
        global $DB;

        $sql1 = "SELECT u.*
                FROM {user_enrolments} ue
                LEFT JOIN {enrol} e ON ue.enrolid = e.id
                LEFT JOIN {user} u ON u.id = ue.userid
                WHERE e.courseid = :courseid";

        $students =  $DB->get_records_sql($sql1,array('courseid'=>$courseid));
        $return = array();
        foreach ($students as $student) {
            if (!empty($student)) {
                //var_dump($student);
                $student_log = new student_log();
                $student_log->studentid = $student->id;

                $student_log->name = $student->lastname . ' ' . $student->firstname;
                $sql2 = "SELECT l.*, r.name as room, r.campus, s.lesson
                    FROM {attendance_log} l
                    LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                    LEFT JOIN {room} r ON r.id = s.roomid
                    LEFT JOIN {attendance} a ON s.attendanceid = a.id
                    WHERE a.course = :courseid AND l.studentid = :studentid";
                $datas = $DB->get_records_sql($sql2, array('courseid' => $courseid,
                    'studentid' => $student_log->studentid));

                $student_log->reports = $datas;
                //var_dump($datas);
                $return[] = $student_log;
            }
        }
        //var_dump($return);
        return $return;
    }

    public static function get_logs_by_course_id_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'studentid' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                    'name' => new external_value(PARAM_TEXT,"student's name", VALUE_DEFAULT,null),
                    'reports' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                                'lesson' => new external_value(PARAM_INT, 'lesson number', VALUE_DEFAULT, null),
                                'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                                'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                                'timein' => new external_value(PARAM_INT, 'timestamp when the student checkin', VALUE_DEFAULT, null),
                                'timeout' => new external_value(PARAM_INT, 'timestamp when the student checkout', VALUE_DEFAULT, null),
                                'status' => new external_value(PARAM_INT, 'status of the student', VALUE_DEFAULT, null),
                            )
                        ),'checkin infomation of a student in a course'
                    )
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
        $sql1 = "SELECT s.*, course.id as courseid, course.fullname, r.name, r.campus
                FROM {attendance_sessions} s
                LEFT JOIN {attendance} a ON s.attendanceid = a.id
                LEFT JOIN {room} r ON r.id = s.roomid
                LEFT JOIN {course} course ON a.course = course.id
                WHERE s.id = :sessionid";
        $data = $DB->get_record_sql($sql1,array('sessionid' => $sessionid));


        $sql2 = "SELECT l.*, u.lastname, u.firstname
                FROM {attendance_log} l
                LEFT JOIN {user} u ON l.studentid = u.id
                LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
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
        return array('courseid'=>$data->courseid,'name'=>$data->fullname,'sessdate'=>
            $data->sessdate,'duration'=>$data->duration,'lesson'=>$data->lesson,
            'room'=>$data->name, 'campus'=>$data->campus, 'students'=>$students_array);
    }
    public static function get_session_detail_returns() {
           return new external_single_structure(
                array(
                    'courseid' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, null),
                    'name' => new external_value(PARAM_TEXT, 'course name', VALUE_DEFAULT, null),
                    'sessdate' => new external_value(PARAM_INT, 'session start timestamp', VALUE_DEFAULT, null),
                    'duration' => new external_value(PARAM_INT, 'session duration', VALUE_DEFAULT, null),
                    'lesson' => new external_value(PARAM_INT, 'lesson number', VALUE_DEFAULT, null),
                    'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                    'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
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
                'roomid' => new external_value(PARAM_TEXT, 'room ID parameter',VALUE_DEFAULT, null),
                'date'  => new external_value(PARAM_INT, 'Date of room schedules',VALUE_DEFAULT, null),
            )
        );
    }

    public static function get_room_schedules(string $roomid,int $date)
    {
        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_room_schedules_parameters(), array(
            'roomid' => $roomid,
            'date' => $date,
        ));
        global $DB;

        $sql = "SELECT  s.id as id, attendanceid,course as courseid, roomid, sessdate, duration, fullname, shortname,startdate
        FROM {attendance_sessions} s
        LEFT JOIN {attendance} a ON s.attendanceid = a.id 
        LEFT JOIN {course} c ON course = c.id
        WHERE (s.roomid = :roomid AND s.sessdate > ( :date div 86400)*86400 AND s.sessdate < (( :date2 div 86400) + 1 )*86400)
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
                    'roomid' => new external_value(PARAM_INT, 'room ID', VALUE_DEFAULT, null),
                    'courseid' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, null),
                    'sessdate' => new external_value(PARAM_INT, 'start time of session', VALUE_DEFAULT, null),
                    'duration' => new external_value(PARAM_INT, 'duration of session', VALUE_DEFAULT, null),
                    'startdate' => new external_value(PARAM_INT, 'startdate of course', VALUE_DEFAULT, null),
                    'fullname' => new external_value(PARAM_TEXT, 'fullname of course', VALUE_DEFAULT, null),
                    'shortname' => new external_value(PARAM_TEXT, 'shortname of course', VALUE_DEFAULT, null),
                )
            )
        );
    }


    ////////////get_teacher_courses
    public static function get_teacher_courses_parameters() {
        return new external_function_parameters(
            array(
                'roomid' => new external_value(PARAM_INT, 'room ID parameter',VALUE_DEFAULT, null),
                'date'  => new external_value(PARAM_INT, 'Date of room schedules',VALUE_DEFAULT, null),
            )
        );
    }

    public static function get_teacher_courses(string $roomid,int $date){
        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_room_schedules_parameters(), array(
            'roomid' => $roomid,
            'date' => $date,
        ));
        global $DB;

        $sql = "SELECT  s.id as id, attendanceid,course as courseid, roomid, sessdate, duration, fullname, shortname,startdate
                FROM {attendance_sessions} s
                LEFT JOIN {attendance} a ON s.attendanceid = a.id 
                LEFT JOIN {course} c ON course = c.id
                WHERE (s.roomid = :roomid AND s.sessdate > ( :date div 86400)*86400 AND s.sessdate < (( :date2 div 86400) + 1 )*86400)
        ";
        $res=$DB->get_records_sql($sql,array('roomid'=>$roomid,'date'=>$date,'date2'=>$date));
        return $res;
    }

    public static function get_teacher_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'attendanceid' => new external_value(PARAM_INT, 'attendance ID', VALUE_DEFAULT, null),
                    'roomid' => new external_value(PARAM_TEXT, 'room ID', VALUE_DEFAULT, null),
                    'courseid' => new external_value(PARAM_TEXT, 'course ID', VALUE_DEFAULT, null),
                    'sessdate' => new external_value(PARAM_INT, 'start time of session', VALUE_DEFAULT, null),
                    'duration' => new external_value(PARAM_INT, 'Duration of session', VALUE_DEFAULT, null),
                    'startdate' => new external_value(PARAM_INT, 'startdate of course', VALUE_DEFAULT, null),
                    'fullname' => new external_value(PARAM_TEXT, 'fullname of course', VALUE_DEFAULT, null),
                    'shortname' => new external_value(PARAM_TEXT, 'shortname of course', VALUE_DEFAULT, null),
                )
            )
        );
    }

    ////////////get_rooms
    public static function get_rooms_parameters() {
        return new external_function_parameters(
            array(
                'campus' => new external_value(PARAM_TEXT, 'Which campus',VALUE_DEFAULT, null),
            )
        );
    }

    public static function get_rooms(string $campus){
        $params = self::validate_parameters(self::get_rooms_parameters(), array(
            'campus' => $campus,
        ));
        global $DB;

        $sql = "SELECT *
                FROM {room}
                WHERE campus = :campus
                ";
        $res=$DB->get_records_sql($sql,array('campus'=>$campus));
        return $res;
    }

    public static function get_rooms_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'name' => new external_value(PARAM_TEXT, 'name of room', VALUE_DEFAULT, null),
                    'campus' => new external_value(PARAM_TEXT, 'campus', VALUE_DEFAULT, null),

                )
            )
        );
    }    

}
