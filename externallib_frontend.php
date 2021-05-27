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

class local_webservices_frontend extends external_api {

    public static function get_student_logs_by_course_id(int $studentid, int $courseid) :array
    {
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
                WHERE e.courseid = :courseid AND u.id = :studentid";

        $student =  $DB->get_record_sql($sql1,array('courseid'=>$courseid,'studentid'=>$studentid));
        $return = array();
        if ($student != false) {

            $student_log = array('studentid'=>null,'name'=>null,'email'=>null,'count'=>null,'c'=>0,'b'=>0,'t'=>0,'v'=>0,'reports'=>array());
            $student_log['studentid'] = $student->id;

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

    public static function get_image(int $studentid): array
    {

        global $DB;

        $sql = "SELECT i.studentid, i.image_front
                FROM {attendance_images} i
                WHERE i.studentid = $studentid";

        return $DB->get_records_sql($sql);
    }

    public static function get_images_by_course_id(int $courseid): array
    {

        global $DB;
        $sql = "SELECT i.studentid, i.image_front
                FROM {attendance_images} i
                LEFT JOIN {role_assignments} ra ON i.studentid = ra.userid
                LEFT JOIN {context} con ON con.id = ra.contextid
                LEFT JOIN {course} c ON c.id = con.instanceid
                LEFT JOIN {role} r ON ra.roleid = r.id
                WHERE c.id = :courseid AND r.shortname = 'student'
                ORDER BY i.studentid ASC";
        return $DB->get_records_sql($sql,array('courseid'=>$courseid));
    }



    /**
     * @throws dml_exception
     */
    public static function get_feedbacks(int $sessionid): array
    {
        global $DB;
        $sql = "SELECT f.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name, s.sessdate
                FROM {attendance_feedback} f
                LEFT JOIN {attendance_sessions} s ON f.sessionid = s.id
                LEFT JOIN {user} usertaken ON f.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON f.userbetaken = userbetaken.id
                WHERE f.sessionid = $sessionid";
        return $DB->get_records_sql($sql);
    }

    public static function get_feedbacks_by_ids(int $userid, int $sessionid): array
    {
        global $DB;
        $sql = "SELECT f.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name, s.sessdate
                FROM {attendance_feedback} f
                LEFT JOIN {attendance_sessions} s ON f.sessionid = s.id
                LEFT JOIN {user} usertaken ON f.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON f.userbetaken = userbetaken.id
                WHERE f.usertaken = $userid AND f.sessionid = $sessionid";
        return $DB->get_records_sql($sql);
    }

    public static function get_action_logs(int $sessionid): array
    {
        global $DB;
        $sql = "SELECT l.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name, s.sessdate
                FROM {attendance_action_log} l
                LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                LEFT JOIN {user} usertaken ON l.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON l.userbetaken = userbetaken.id
                WHERE (l.eventname LIKE 'Update status student' OR l.eventname LIKE 'Add status student')
                AND l.sessionid = $sessionid";
        return $DB->get_records_sql($sql);
    }

    public static function get_action_logs_pagination_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'attendanceid' => new external_value(PARAM_INT, 'attendance ID'),
                'page' => new external_value(PARAM_INT, 'page number'),
                'pagesize'  => new external_value(PARAM_INT, 'page size'),
                'value' => new external_value(PARAM_TEXT,'search value',VALUE_DEFAULT,''),
                'filter' => new external_value(PARAM_TEXT, 'filter criteria',VALUE_DEFAULT,''),
                'order'  => new external_value(PARAM_TEXT, 'order criteria',VALUE_DEFAULT,''),
            )
        );
    }

    /**
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function get_action_logs_pagination(int $attendanceid, int $page, int $pagesize,
                                                      string $value, string $filter, string $order): array
    {
        $params = self::validate_parameters(self::get_action_logs_pagination_parameters(), array(
            'attendanceid' => $attendanceid,
            'page' => $page,
            'pagesize' => $pagesize,
            'value' => $value,
            'filter' => $filter,
            'order' => $order
        ));


        global $DB;
        $result = null;
        if ($value != '') {
            $sql = "SELECT l.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name, s.sessdate
                FROM {attendance_action_log} l
                LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                LEFT JOIN {user} usertaken ON l.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON l.userbetaken = userbetaken.id
                WHERE (usertaken.firstname LIKE :string1 OR usertaken.lastname LIKE :string2
                OR userbetaken.firstname LIKE :string3 OR userbetaken.lastname LIKE :string4) AND l.attendanceid = $attendanceid
                ORDER BY $filter $order";
            $result = $DB->get_records_sql($sql,array('string1' => '%' . $value . '%','string2' => '%' . $value . '%',
                'string3' => '%' . $value . '%','string4' => '%' . $value . '%',));
        }
        else {
            $sql = "SELECT l.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name, s.sessdate
                FROM {attendance_action_log} l
                LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                LEFT JOIN {user} usertaken ON l.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON l.userbetaken = userbetaken.id
                WHERE l.attendanceid = $attendanceid
                ORDER BY $filter $order";
            $result = $DB->get_records_sql($sql);
        }

        $logs = array();
        $index = 0;
        foreach ($result as $item => $value) {
            if (($page-1)*$pagesize<=$index && $page*$pagesize>$index) {
                $logs[] = $value;
                $index++;
            }
            else if ($index<($page-1)*$pagesize) {
                $index++;
            }
            else break;

        }
        return array('totalrecords' => count($result), 'logs' => $logs);
    }

    public static function get_action_logs_pagination_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'totalrecords' => new external_value(PARAM_INT,'total records',VALUE_DEFAULT,null),
                'logs' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'log ID', VALUE_DEFAULT, null),
                            'attendanceid' => new external_value(PARAM_INT,'attendance ID',VALUE_DEFAULT,null),
                            'sessionid' => new external_value(PARAM_INT,'session ID',VALUE_DEFAULT,null),
                            'usertaken' => new external_value(PARAM_INT, "user taken's ID", VALUE_DEFAULT, null),
                            'usertaken_name' => new external_value(PARAM_TEXT, "user taken's full name", VALUE_DEFAULT, null),
                            'userbetaken' => new external_value(PARAM_INT, "user be taken's ID", VALUE_DEFAULT, null),
                            'userbetaken_name' => new external_value(PARAM_TEXT, "user be taken's full name", VALUE_DEFAULT, null),
                            'description' => new external_value(PARAM_TEXT, "Description", VALUE_DEFAULT, null),
                            'timetaken' => new external_value(PARAM_INT, "Time taken timestamp", VALUE_DEFAULT, null),
                        )
                    )
                )
            )
        );
    }

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

//        $d = DateTime::createFromFormat(
//            'd/m/Y',
//            '22/04/2021',
//            new DateTimeZone('Asia/Ho_Chi_Minh')
//        );
//
//        if ($d === false) {
//            die("Incorrect date string");
//        } else {
//            echo $d->getTimestamp();
//        }


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

}
