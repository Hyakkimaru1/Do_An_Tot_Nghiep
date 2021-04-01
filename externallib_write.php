<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');

class local_webservices_external_write extends external_api {

    public static function create_attendance_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid'  => new external_value(PARAM_INT, 'Course ID'),
            )
        );
    }

    public static function create_attendance(int $courseid): array
    {
        $params = self::validate_parameters(self::create_attendance_parameters(), array(
                'courseid' => $courseid,
            )
        );

        global $DB;

        // Check if this course was created or not

        $sql1 = "SELECT course.*
                FROM {course} course 
                WHERE course.id = :courseid";
        $result1 = $DB->get_record_sql($sql1,array('courseid'=>$courseid),IGNORE_MISSING);

        $return = array('message' => '');
        if ($result1 == false)
        {
            $return['message'] = "There isn't any course with this ID";
        }
        else {
            $sql2 = "SELECT a.*
                FROM {attendance} a
                WHERE a.course = :courseid";
            $result2 = $DB->get_record_sql($sql2,array('courseid'=>$courseid),IGNORE_MISSING);
            if ($result2 == false) {
                $data = (object)array('course' => $courseid);

                if ($DB->insert_record('attendance', $data)) {
                    $return['message'] = "Created attendance successfully";
                } else {
                    $return['message'] = "Couldn't create attedance";
                }
            }
            else {
                $return['message'] = "There is already an attendance record for this course";
            }
        }
        return $return;
    }

    public static function create_attendance_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }

    public static function create_sessions_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid'  => new external_value(PARAM_INT, 'Course ID'),
                'roomid' => new external_value(PARAM_INT,'Room ID'),
                'lesson' => new external_value(PARAM_INT,'Number of lessons or weeks'),
            )

        );
    }

    public static function create_sessions(int $courseid,int $roomid, int $lesson): array
    {
        $params = self::validate_parameters(self::create_sessions_parameters(), array(
                'courseid' => $courseid,
                'roomid' => $roomid,
                'lesson' => $lesson
            )
        );

        global $DB;

        // Check if this course has an attendance record or not

        $sql1 = "SELECT a.*
                FROM {attendance} a
                WHERE a.course = :courseid";
        $result1 = $DB->get_record_sql($sql1,array('courseid'=>$courseid));

        $return = array('message' => '');
        if ($result1 == false)
        {
            $return['message'] = "This course doesn't have an attendance record";
        }
        else {
            // Check if this course has session records or not
            $sql2 = "SELECT s.*
                FROM {attendance_sessions} s
                LEFT JOIN {attendance} a ON a.id = s.attendanceid
                WHERE a.course = :courseid";
            $result2 = $DB->get_record_sql($sql2,array('courseid'=>$courseid),IGNORE_MULTIPLE);
            if ($result2 == false) {
                $sql3 = "SELECT r.*
                FROM {room} r WHERE r.id = :roomid";
                $result3 = $DB->get_record_sql($sql3,array('roomid'=>$roomid));
                if ($result3 == false) {
                    $return['message'] = "There isn't any room with this ID";
                }
                else {
                    $data = array();
                    for ($i = 1;$i<=$lesson;$i++) {
                        $data[] = (object)array('attendanceid' => $result1->id,'roomid'=>$roomid,
                            'lesson'=>$i);
                    }
                    try {
                        $DB->insert_records('attendance_sessions', $data);
                        $return['message'] = "Created sessions successfully";
                    } catch (coding_exception | dml_exception $e) {
                        $return['message'] = "Couldn't create sessions";
                    }
                }
            }
            else {
                $return['message'] = "There isn't any room with this ID";
            }
        }
        return $return;
    }

    public static function create_sessions_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }


    public static function create_logs_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'studentid'  => new external_value(PARAM_INT, 'Student ID'),
                'courseid' => new external_value(PARAM_INT,'Course ID'),
                'status' => new external_value(PARAM_INT,'Status number',VALUE_DEFAULT,0),
            )

        );
    }

    public static function create_logs(int $studentid, int $courseid, int $status): array
    {
        $params = self::validate_parameters(self::create_logs_parameters(), array(
                'studentid' => $studentid,
                'courseid' => $courseid,
                'status' => $status
            )
        );

        global $DB;

        // Check if this student was assigned to this course or not

        $sql1 = "SELECT ue.*
                FROM {user_enrolments} ue
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid = :courseid AND ue.userid = :studentid";
        $result1 = $DB->get_record_sql($sql1,array('courseid'=>$courseid,'studentid'=>$studentid));

        $return = array('message' => '');
        if ($result1 == false)
        {
            $return['message'] = "This student wasn't assigned to this course / This course or student does not exist";
        }
        else {
            // Check if this course has session records or not
            $sql2 = "SELECT s.*
                FROM {attendance_sessions} s
                LEFT JOIN {attendance} a ON a.id = s.attendanceid
                WHERE a.course = :courseid
                ORDER BY s.id ASC";
            $result2 = $DB->get_records_sql($sql2,array('courseid'=>$courseid));
            if ($result2 != []) {
                // Check if this student has logs or not
                $sql3 = "SELECT l.*
                    FROM {attendance_log} l
                    LEFT JOIN {attendance_sessions} s ON l.sessionid = s.id
                    LEFT JOIN {attendance} a ON a.id = s.attendanceid
                    WHERE l.studentid = :studentid AND a.course = :courseid";

                $result3 = $DB->get_record_sql($sql3,array('studentid'=>$studentid,
                    'courseid'=>$courseid));
                if ($result3 == false) {
                    $data = array();
                    foreach ($result2 as $session) {
                        $data[] = (object)array('studentid' => $studentid, 'sessionid' => $session->id,
                            'status' => $status);
                    }
                    try {
                        $DB->insert_records('attendance_log', $data);
                        $return['message'] = "Created logs successfully";
                    } catch (coding_exception | dml_exception $e) {
                        $return['message'] = "Couldn't create logs";
                    }
                }
                else {
                    $return['message'] = "There are already log records for this student in this course";
                }
            }
            else {
                $return['message'] = "There isn't any session records with this course";
            }
        }
        return $return;
    }

    public static function create_logs_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }


    /**
     * Parameter description for update_session().
     *
     * @return external_function_parameters.
     */


    public static function update_session_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'sessionid'  => new external_value(PARAM_INT, 'Session ID parameter'),
                'sessdate' => new external_value(PARAM_INT, 'Class start timestamp',VALUE_DEFAULT,0),
                'duration' => new external_value(PARAM_INT, 'Class duration',VALUE_DEFAULT,0),
                'roomid'  => new external_value(PARAM_INT, 'New status number',VALUE_DEFAULT,0),
            )
        );
    }



    /**
     *
     *
     * This function will update (optionally) session date, last date and room ID.
     *
     * @param int $sessionid Session ID (required).
     * @param int $sessdate Timestamp in millisecond when the class start (can be null if not need to update).
     * @param int $duration Class duration (can be null if not need to update).
     * @param int $roomid Room ID (can be null if not need to update).
     * @return array
     * @throws invalid_parameter_exception|dml_exception
     */

    public static function update_session(int $sessionid, int $sessdate, int $duration, int $roomid): array
    {
        $params = self::validate_parameters(self::update_session_parameters(), array(
                'sessionid' => $sessionid,
                'sessdate' => $sessdate,
                'duration' => $duration,
                'roomid' => $roomid
            )
        );

        global $DB;


        $sql = "SELECT s.*
                FROM {attendance_sessions} s 
                WHERE s.id = :sessionid";
        $result = $DB->get_record_sql($sql,array('sessionid'=>$sessionid));

        $return = array('message' => '');
        if ($result == false)
        {
            $return['message'] = "There isn't any record with this session ID";
        }
        else {
            $data = (object) array('id'=>$result->id,'sessdate'=>$result->sessdate,'duration'=>$result->duration,
                'roomid'=>$result->roomid);
            if ($sessdate)
            {
                $data->sessdate = $sessdate;
            }
            if ($duration)
            {
                $data->duration = $duration;
            }
            if ($roomid)
            {
                $sql1 = "SELECT r.*
                FROM {room} r 
                WHERE r.id = :roomid";

                $checkroom = $DB->get_record_sql($sql1,array('roomid'=>$roomid));
                if ($checkroom == false)
                {
                    $return['message'] = "There isn't any room with this room ID";
                    return $return;
                }
                $data->roomid = $roomid;
            }
            if ($DB->update_record('attendance_sessions',$data)) {
                $return['message'] = "Updated the record successfully";
            }
            else {
                $return['message'] = "Couldn't update the record";
            }
        }
        return $return;
    }

    public static function update_session_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
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
     * This function will update (optionally) status, timein and timeout.
     *
     * @param int $studentid Student ID (required).
     * @param int $sessionid Session ID (required).
     * @param int $timein Timestamp in millisecond when the student checkin (can be null if not need to update).
     * @param int $timeout Timestamp in millisecond when the student out (can be null if not need to update).
     * @param int $status New status number (can be null if not need to update).
     * @return array
     * @throws invalid_parameter_exception|dml_exception
     */

    public static function update_log(int $studentid, int $sessionid, int $timein, int $timeout, int $status): array
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


        $sql = "SELECT l.*
                FROM {attendance_log} l 
                WHERE l.studentid = :studentid AND l.sessionid = :sessionid";
        $result = $DB->get_record_sql($sql,array('studentid'=>$studentid,'sessionid'=>$sessionid),IGNORE_MISSING);

        $return = array('message' => '');
        if ($result == false)
        {
            $return['message'] = "There isn't any log with suitable conditions";
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

}