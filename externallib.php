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
global $CFG;
require_once(_DIR_ . '/../../lib/externallib.php');
/**
 * Web service API definition.
 *
 * @package local_webservices
 * @copyright Copyleft
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class student_object {
    public $id;
    public $username;
    public $name;
    public $timein;
    public $timeout;
    public $statusid;

}

class student_log {
    public $studentid;
    public $username;
    public $name;
    public $email;
    public $count;
    public $c = 0;
    public $b = 0;
    public $t = 0;
    public $v = 0;
    public $presentStatusid = null;
    public $reports;
}

class local_webservices_external extends external_api {

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
                    'statusid' => new external_value(PARAM_INT, 'statusid of the student', VALUE_DEFAULT, null),
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
                    'statusid' => new external_value(PARAM_INT, 'statusid of the student', VALUE_DEFAULT, null),
                )
            )
        );
    }

    public static function get_sessions_by_course_id_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid'  => new external_value(PARAM_INT, 'course ID parameter'),
            )
        );
    }

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_sessions_by_course_id(int $courseid): array
    {
        $params = self::validate_parameters(self::get_sessions_by_course_id_parameters(), array('courseid' => $courseid));
        global $DB;

        $sql = "SELECT s.*, r.name as room, r.campus
                FROM {attendance_sessions} s 
                LEFT JOIN {attendance} a ON s.attendanceid = a.id
                LEFT JOIN {room} r ON r.id = s.roomid
                WHERE a.course = $courseid
                ORDER BY s.sessdate ASC";
        return $DB->get_records_sql($sql);
    }

    public static function get_sessions_by_course_id_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                    'sessdate' => new external_value(PARAM_INT, 'session start timestamp', VALUE_DEFAULT, null),
                    'duration' => new external_value(PARAM_INT, 'session duration', VALUE_DEFAULT, null),
                    'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                    'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                )
            )
        );
    }

    public static function get_student_logs_by_course_id_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, "student's username parameter"),
                'courseid'  => new external_value(PARAM_INT, 'course ID parameter'),
            )
        );

    }

    /**
     *
     * This function returns student's logs in this course.
     *
     * @param string $username Student's username.
     * @param int $courseid Course ID.
     * @throws invalid_parameter_exception|dml_exception
     */
    public static function get_student_logs_by_course_id(string $username, int $courseid) :array
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_student_logs_by_course_id_parameters(), array(
            'username' => $username,
            'courseid' => $courseid));
        global $DB;

        $sql = "SELECT s.*, r.name as room, r.campus
                FROM {attendance_sessions} s 
                LEFT JOIN {attendance} a ON s.attendanceid = a.id
                LEFT JOIN {room} r ON r.id = s.roomid
                WHERE a.course = $courseid
                ORDER BY s.sessdate ASC";
        $sessions = $DB->get_records_sql($sql);


        $sql1 = "SELECT u.*
                FROM {user_enrolments} ue
                LEFT JOIN {enrol} e ON ue.enrolid = e.id
                LEFT JOIN {user} u ON u.id = ue.userid
                WHERE e.courseid = :courseid AND u.username = :username";

        $student =  $DB->get_record_sql($sql1,array('courseid'=>$courseid,'username'=>$username));
        $return = array();
        if ($student != false) {

            $student_log = array('studentid'=>null,'username'=>null,'name'=>null,'email'=>null,'count'=>null,'c'=>0,'b'=>0,'t'=>0,'v'=>0,
                'reports'=>array());
            $student_log['studentid'] = $student->id;
            $student_log["username"] = $student->username;
            $student_log['name'] = $student->lastname . ' ' . $student->firstname;
            $student_log['email'] = $student->email;
            $sql2 = "SELECT l.*, r.name as room, r.campus, s.lesson, s.sessdate
                FROM {attendance_log} l
                LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                LEFT JOIN {room} r ON r.id = s.roomid
                LEFT JOIN {attendance} a ON s.attendanceid = a.id
                WHERE a.course = :courseid AND l.studentid = :studentid";
            $datas = $DB->get_records_sql($sql2, array('courseid' => $courseid,
                'studentid' => $student_log['studentid']));

            $student_log['count'] = count($datas);
            foreach ($datas as $log) {
                if ($log->statusid == 1) {
                    $student_log['c']++;
                } else if ($log->statusid == 2) {
                    $student_log['b']++;
                } else if ($log->statusid == 3) {
                    $student_log['t']++;
                } else if ($log->statusid == 4) {
                    $student_log['v']++;
                }
            }
            $reports = array();
            foreach ($sessions as $session) {
                $flag = false;
                foreach ($datas as $log) {
                    if ($session->id == $log->sessionid) {
                        $flag = true;
                        $reports[] = $log;
                        break;
                    }
                }
                if ($flag == false) {
                    $data = (object) array('sessionid' => $session->id, 'sessdate' => $session->sessdate,
                        'lesson' => $session->lesson, 'room' => $session->room, 'campus' => $session->campus,
                        'timein' => null, 'timeout' => null, 'statusid' => null);
                    $reports[] = $data;
                }
            }

            $student_log['reports'] = $reports;
            $return[] = $student_log;
        }
        return $return;
    }

    public static function get_student_logs_by_course_id_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'studentid' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                    'username' => new external_value(PARAM_TEXT, "student's username", VALUE_DEFAULT, null),
                    'name' => new external_value(PARAM_TEXT,"student's name", VALUE_DEFAULT,null),
                    'email' => new external_value(PARAM_TEXT,"student's email", VALUE_DEFAULT,null),
                    'count' => new external_value(PARAM_INT,"number of logs", VALUE_DEFAULT,null),
                    'c' => new external_value(PARAM_INT, 'active count', VALUE_DEFAULT, null),
                    'b' => new external_value(PARAM_INT, 'passive count', VALUE_DEFAULT, null),
                    't' => new external_value(PARAM_INT, 'late count', VALUE_DEFAULT, null),
                    'v' => new external_value(PARAM_INT, 'absent count', VALUE_DEFAULT, null),
                    'reports' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                                'sessdate' => new external_value(PARAM_INT,'timestamp when the class start',VALUE_DEFAULT,null),
                                'lesson' => new external_value(PARAM_INT, 'lesson number', VALUE_DEFAULT, null),
                                'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                                'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                                'timein' => new external_value(PARAM_INT, 'timestamp when the student checkin', VALUE_DEFAULT, null),
                                'timeout' => new external_value(PARAM_INT, 'timestamp when the student checkout', VALUE_DEFAULT, null),
                                'statusid' => new external_value(PARAM_INT, 'statusid of the student', VALUE_DEFAULT, null),
                            )
                        ),'all reports of a student in a course'
                    )
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

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_logs_by_course_id(int $courseid): array
    {
        $params = self::validate_parameters(self::get_logs_by_course_id_parameters(), array(
                'courseid' => $courseid
            )
        );
        global $DB;

        $sql = "SELECT s.*, r.name as room, r.campus
                FROM {attendance_sessions} s 
                LEFT JOIN {attendance} a ON s.attendanceid = a.id
                LEFT JOIN {room} r ON r.id = s.roomid
                WHERE a.course = $courseid
                ORDER BY s.sessdate ASC";
        $sessions = $DB->get_records_sql($sql);


        $sql1 = "SELECT u.*
                FROM {role_assignments} ra
                LEFT JOIN {context} con ON con.id = ra.contextid
                LEFT JOIN {course} c ON c.id = con.instanceid
                LEFT JOIN {role} r ON ra.roleid = r.id
                LEFT JOIN {user} u ON u.id = ra.userid
                WHERE c.id = :courseid AND r.shortname = 'student'
                ORDER BY u.id ASC";

        $students =  $DB->get_records_sql($sql1,array('courseid'=>$courseid));
        $return = array();
        $time = time();
        foreach ($students as $student) {
            if (!empty($student)) {

                $student_log = new student_log();
                $student_log->studentid = $student->id;
                $student_log->username = $student->username;
                $student_log->name = $student->lastname . ' ' . $student->firstname;
                $student_log->email = $student->email;
                $sql2 = "SELECT l.*, r.name as room, r.campus, s.sessdate, s.duration
                    FROM {attendance_log} l
                    LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                    LEFT JOIN {room} r ON r.id = s.roomid
                    LEFT JOIN {attendance} a ON s.attendanceid = a.id
                    WHERE a.course = :courseid AND l.studentid = :studentid
                    ORDER BY s.sessdate ASC";

                $datas = $DB->get_records_sql($sql2, array('courseid' => $courseid,
                    'studentid' => $student_log->studentid));

                $student_log->count = count($datas);
                foreach ($datas as $log) {

                    if ($log->statusid == 1) {
                        $student_log->c++;
                    }
                    else if ($log->statusid == 2) {
                        $student_log->b++;
                    }
                    else if ($log->statusid == 3) {
                        $student_log->t++;
                    }
                    else if ($log->statusid == 4) {
                        $student_log->v++;
                    }
                }
                $reports = array();
                $now = false;
                foreach ($sessions as $session) {
                    if ($session->sessdate - 3600 <= $time && $session->sessdate + $session->duration + 3600 >= $time) {
                        $now = true;
                    }

                    $flag = false;
                    foreach ($datas as $log) {
                        if ($session->id == $log->sessionid)
                        {
                            $student_log->presentStatusid = $log->statusid;
                            $flag = true;
                            $reports[] = $log;
                            break;
                        }
                    }
                    if ($flag == false) {
                        if ($now == true) {
                            $student_log->presentStatusid = 0;
                        }
                        $data = (object) array('sessionid'=>$session->id,'sessdate'=>$session->sessdate,
                            'room'=>$session->room,'campus'=>$session->campus,
                            'timein'=>null,'timeout'=>null, 'statusid'=>null);
                        $reports[] = $data;
                    }
                }

                $student_log->reports = $reports;
                $return[] = $student_log;
            }
        }

        return $return;
    }

    public static function get_logs_by_course_id_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'studentid' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                    'username' => new external_value(PARAM_TEXT, "student's username", VALUE_DEFAULT, null),
                    'name' => new external_value(PARAM_TEXT,"student's name", VALUE_DEFAULT,null),
                    'email' => new external_value(PARAM_TEXT,"student's email", VALUE_DEFAULT,null),
                    'count' => new external_value(PARAM_INT,"number of logs", VALUE_DEFAULT,null),
                    'c' => new external_value(PARAM_INT, 'active count', VALUE_DEFAULT, null),
                    'b' => new external_value(PARAM_INT, 'passive count', VALUE_DEFAULT, null),
                    't' => new external_value(PARAM_INT, 'late count', VALUE_DEFAULT, null),
                    'v' => new external_value(PARAM_INT, 'absent count', VALUE_DEFAULT, null),
                    'presentStatusid' => new external_value(PARAM_INT, 'present status ID', VALUE_DEFAULT, null),
                    'reports' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'sessionid' => new external_value(PARAM_INT, 'session ID', VALUE_DEFAULT, null),
                                'sessdate' => new external_value(PARAM_INT,'timestamp when the class start',VALUE_DEFAULT,null),
                                'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                                'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                                'timein' => new external_value(PARAM_INT, 'timestamp when the student checkin', VALUE_DEFAULT, null),
                                'timeout' => new external_value(PARAM_INT, 'timestamp when the student checkout', VALUE_DEFAULT, null),
                                'statusid' => new external_value(PARAM_INT, 'statusid of the student', VALUE_DEFAULT, null),
                            )
                        ),'all reports of a student in a course'
                    )
                )
            )
        );
    }

    public static function get_images_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, "Student's username"),
            )
        );
    }

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_images(string $username): array
    {
        $params = self::validate_parameters(self::get_images_parameters(), array(
                'username' => $username,
            )
        );

        global $DB;
        $sql1 = "SELECT u.*
                FROM {user} u
                WHERE u.username = :username";
        $student = $DB->get_record_sql($sql1,array('username'=>$username));
        if ($student == false) {
            return array();
        }

        $sql2 = "SELECT i.*
                FROM {attendance_images} i
                WHERE i.studentid = $student->id";

        return $DB->get_records_sql($sql2);
    }

    public static function get_images_returns(): external_multiple_structure
    {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'studentid' => new external_value(PARAM_INT, "Student's id", VALUE_DEFAULT, null),
                    'image_front' => new external_value(PARAM_TEXT,"Front image's url",VALUE_DEFAULT,''),
                    'image_left' => new external_value(PARAM_TEXT,"Left image's url",VALUE_DEFAULT,''),
                    'image_right' => new external_value(PARAM_TEXT,"Right image's url",VALUE_DEFAULT,''),
                )
            )
        );
    }

    public static function get_session_detail_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'sessionid' => new external_value(PARAM_INT, 'Session ID'),
            )
        );
    }

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_session_detail(int $sessionid): array
    {
        $params = self::validate_parameters(self::get_session_detail_parameters(), array(
                'sessionid' => $sessionid,
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


        $sql2 = "SELECT l.*, u.lastname, u.firstname, u.username
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
                $student_object->username = $student->username;
                $student_object->name = $student->lastname. ' ' .$student->firstname;
                $student_object->timein = $student->timein;
                $student_object->timeout = $student->timeout;
                $student_object->statusid = $student->statusid;
                $students_array[] = $student_object;
            }
        }
        return array('courseid'=>$data->courseid,'name'=>$data->fullname,'sessdate'=>
            $data->sessdate,'duration'=>$data->duration, 'room'=>$data->name, 'campus'=>$data->campus, 'students'=>$students_array);
    }
    public static function get_session_detail_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'courseid' => new external_value(PARAM_INT, 'course ID', VALUE_DEFAULT, null),
                'name' => new external_value(PARAM_TEXT, 'course name', VALUE_DEFAULT, null),
                'sessdate' => new external_value(PARAM_INT, 'session start timestamp', VALUE_DEFAULT, null),
                'duration' => new external_value(PARAM_INT, 'session duration', VALUE_DEFAULT, null),
                'room' => new external_value(PARAM_TEXT, 'room name', VALUE_DEFAULT, null),
                'campus' => new external_value(PARAM_TEXT, 'campus location', VALUE_DEFAULT, null),
                'students' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'student ID', VALUE_DEFAULT, null),
                            'username' => new external_value(PARAM_TEXT, "student's username", VALUE_DEFAULT, null),
                            'name' => new external_value(PARAM_TEXT, 'student name', VALUE_DEFAULT, null),
                            'timein' => new external_value(PARAM_INT, 'checkin time', VALUE_DEFAULT, null),
                            'timeout' => new external_value(PARAM_INT, 'checkout time', VALUE_DEFAULT, null),
                            'statusid' => new external_value(PARAM_INT, 'statusid number', VALUE_DEFAULT, null),
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
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws coding_exception
     */
    public static function get_roles(string $username): array
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_roles_parameters(), array(
            'username' => $username,
        ));

        global $DB, $PAGE;

        $sql1 = "SELECT u.*
                    FROM {user} u
                    WHERE u.username = :username";

        $user = $DB->get_records_sql($sql1,array('username'=>$username));
        if ($user == false) {
            return array();
        }

        $sql2 = "SELECT ra.*
                    FROM {role_assignments} ra
                    LEFT JOIN {user} u ON u.id = ra.userid
                    WHERE u.username = :username";
        $roles = $DB->get_records_sql($sql2,array('username'=>$username));
        $min = PHP_INT_MAX;

        foreach ($roles as $role) {
            if ($role->roleid < $min)
            {
                $min = $role->roleid;
            }
        }


        $sql1 = "SELECT u.id AS id, username, firstname, lastname, r.id as roleid, r.name AS role, shortname, i.image_front
            FROM {user} u
            LEFT JOIN {role} r on r.id = $min
            LEFT JOIN {attendance_images} i ON i.studentid = u.id
            WHERE u.username = :username";

        $return = array();

        $info =  $DB->get_record_sql($sql1,array('username'=>$username));
        $isadmin = is_siteadmin($info->id);

//        $alternative_user = (object) array('id'=>$info->id);
//        $userpicture = new user_picture($alternative_user);
//        $userpicture->size = 1;
//        $profileimageurl = $userpicture->get_url($PAGE);


        $element = array('id'=>$info->id,'username'=>$info->username,'firstname'=>$info->firstname,'lastname'=>$info->lastname,
            'roleid'=>$info->roleid,'role'=>$info->role,'shortname'=>$info->shortname,'isadmin'=> $isadmin,
            'userpictureurl'=>$info->image_front);

        $return[] = $element;
        return $return;
    }

    public static function get_roles_returns(): external_multiple_structure
    {
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
                    'isadmin' => new external_value(PARAM_BOOL, 'this is an admin or not', VALUE_DEFAULT, null),
                    'userpictureurl' => new external_value(PARAM_TEXT, "user's front image url", VALUE_DEFAULT, null),
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

    public static function get_campus_parameters() {
         return new external_function_parameters(
                array()
         );
    }

    public static function get_campus(){
        $params = self::validate_parameters(self::get_campus_parameters(), array(
        ));
        global $DB;

        $sql = "SELECT DISTINCT r.campus
                FROM {room} r";
        return $DB->get_records_sql($sql);
    }

    public static function get_campus_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'campus' => new external_value(PARAM_TEXT, 'campus', VALUE_DEFAULT, null),
                )
            )
        );
    }
}

