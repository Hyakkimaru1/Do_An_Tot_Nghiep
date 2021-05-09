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
    /**
     * @throws dml_exception
     */
    public static function get_feedbacks(int $sessionid) {
        global $DB;
        $sql = "SELECT f.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name
                FROM {attendance_feedback} f
                LEFT JOIN {user} usertaken ON f.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON f.userbetaken = userbetaken.id
                WHERE f.sessionid = $sessionid";
        return $DB->get_records_sql($sql);
    }

    public static function get_feedbacks_pagination_parameters(): external_function_parameters
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
    public static function get_feedbacks_pagination(int $attendanceid, int $page, int $pagesize,
                                                    string $value, string $filter, string $order): array
    {
        $params = self::validate_parameters(self::get_feedbacks_pagination_parameters(), array(
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
            $sql = "SELECT f.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name,r.name as room_name,r.campus
                FROM {attendance_sessions} s
                LEFT JOIN {attendance_feedback} f ON f.attendanceid = s.attendanceid
                LEFT JOIN {room} r ON r.id = s.roomid
                LEFT JOIN {user} usertaken ON f.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON f.userbetaken = userbetaken.id
                WHERE (usertaken.firstname LIKE :string1 OR usertaken.lastname LIKE :string2
                OR userbetaken.firstname LIKE :string3 OR userbetaken.lastname LIKE :string4
                OR f.description LIKE :string5 OR r.name LIKE :string6) AND f.attendanceid = $attendanceid
                ORDER BY $filter $order";
            $result = $DB->get_records_sql($sql,array('string1' => '%' . $value . '%','string2' => '%' . $value . '%',
                'string3' => '%' . $value . '%','string4' => '%' . $value . '%','string5' => '%' . $value . '%',
                'string6'=>'%' . $value . '%'));
        }
        else {
            $sql = "SELECT f.*, CONCAT(usertaken.lastname,' ',usertaken.firstname) as usertaken_name,
                CONCAT(userbetaken.lastname,' ',userbetaken.firstname) as userbetaken_name,r.name as room_name,r.campus
                FROM {attendance_sessions} s
                LEFT JOIN {attendance_feedback} f ON f.attendanceid = s.attendanceid
                LEFT JOIN {room} r ON r.id = s.roomid
                LEFT JOIN {user} usertaken ON f.usertaken = usertaken.id
                LEFT JOIN {user} userbetaken ON f.userbetaken = userbetaken.id
                WHERE f.attendanceid = $attendanceid
                ORDER BY $filter $order";
            $result = $DB->get_records_sql($sql);
        }

        $feedbacks = array();
        $index = 0;
        foreach ($result as $item => $value) {
            if (($page-1)*$pagesize<=$index && $page*$pagesize>$index) {
                $feedbacks[] = $value;
                $index++;
            }
            else if ($index<($page-1)*$pagesize) {
                $index++;
            }
            else break;

        }
        return array('totalrecords' => count($result), 'feedbacks' => $feedbacks);
    }

    public static function get_feedbacks_pagination_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'totalrecords' => new external_value(PARAM_INT,'total records',VALUE_DEFAULT,null),
                'feedbacks' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'feedback ID', VALUE_DEFAULT, null),
                            'attendanceid' => new external_value(PARAM_INT,'attendance ID',VALUE_DEFAULT,null),
                            'timetaken' => new external_value(PARAM_INT,'Time taken timestamp',VALUE_DEFAULT,null),
                            'usertaken' => new external_value(PARAM_INT, "user taken's ID", VALUE_DEFAULT, null),
                            'usertaken_name' => new external_value(PARAM_TEXT, "user taken's full name", VALUE_DEFAULT, null),
                            'userbetaken' => new external_value(PARAM_INT, "user be taken's ID", VALUE_DEFAULT, null),
                            'userbetaken_name' => new external_value(PARAM_TEXT, "user be taken's full name", VALUE_DEFAULT, null),
                            'description' => new external_value(PARAM_TEXT, "Description", VALUE_DEFAULT, null),
                            'image' => new external_value(PARAM_TEXT, "Image's link", VALUE_DEFAULT, null),
                            'room_name'=> new external_value(PARAM_TEXT, "Room's name", VALUE_DEFAULT, null),
                            'campus'=> new external_value(PARAM_TEXT, "Campus", VALUE_DEFAULT, null),
                        )
                    )
                )
            )
        );
    }

    public static function get_action_logs(int $sessionid) {
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
