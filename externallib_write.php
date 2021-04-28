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

        $return = array('errorcode' => '', 'message' => '');
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
                    $return['errorcode'] = '400';
                    $return['message'] = "Couldn't create attedance";
                }
            }
            else {
                $return['errorcode'] = '400';
                $return['message'] = "There is already an attendance record for this course";
            }
        }
        return $return;
    }

    public static function create_attendance_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT, 'Error code'),
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

        $return = array('errorcode' => '', 'message' => '');
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
                    $return['errorcode'] = '404';
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
                        $return['errorcode'] = '400';
                        $return['message'] = "Couldn't create sessions";
                    }
                }
            }
            else {
                $return['errorcode'] = '404';
                $return['message'] = "There isn't any room with this ID";
            }
        }
        return $return;
    }

    public static function create_sessions_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT, 'Error code'),
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
                'statusid' => new external_value(PARAM_INT,'statusid number',VALUE_DEFAULT,0),
            )

        );
    }

    public static function create_logs(int $studentid, int $courseid, int $statusid): array
    {
        $params = self::validate_parameters(self::create_logs_parameters(), array(
                'studentid' => $studentid,
                'courseid' => $courseid,
                'statusid' => $statusid
            )
        );

        global $DB;

        // Check if this student was assigned to this course or not

        $sql1 = "SELECT ue.*
                FROM {user_enrolments} ue
                LEFT JOIN {enrol} e ON e.id = ue.enrolid
                WHERE e.courseid = :courseid AND ue.userid = :studentid";
        $result1 = $DB->get_record_sql($sql1,array('courseid'=>$courseid,'studentid'=>$studentid));

        $return = array('errorcode' => '', 'message' => '');
        if ($result1 == false)
        {
            $return['errorcode'] = '404';
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
                            'statusid' => $statusid);
                    }
                    try {
                        $DB->insert_records('attendance_log', $data);
                        $return['message'] = "Created logs successfully";
                    } catch (coding_exception | dml_exception $e) {
                        $return['errorcode'] = '400';
                        $return['message'] = "Couldn't create logs";
                    }
                }
                else {
                    $return['errorcode'] = '404';
                    $return['message'] = "There are already log records for this student in this course";
                }
            }
            else {
                $return['errorcode'] = '404';
                $return['message'] = "There isn't any session records with this course";
            }
        }
        return $return;
    }

    public static function create_logs_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT, 'Error code'),
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
                'roomid'  => new external_value(PARAM_INT, 'New statusid number',VALUE_DEFAULT,0),
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

        $return = array('errorcode' => '', 'message' => '');
        if ($result == false)
        {
            $return['errorcode'] = '404';
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
                    $return['errorcode'] = '404';
                    $return['message'] = "There isn't any room with this room ID";
                    return $return;
                }
                $data->roomid = $roomid;
            }
            if ($DB->update_record('attendance_sessions',$data)) {
                $return['message'] = "Updated the record successfully";
            }
            else {
                $return['errorcode'] = '400';
                $return['message'] = "Couldn't update the record";
            }
        }
        return $return;
    }

    public static function update_session_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT,'Error code'),
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
                'studentid' => new external_value(PARAM_INT, 'Student ID parameter',VALUE_DEFAULT,-1),
                'username' => new external_value(PARAM_TEXT,"Student's username",VALUE_DEFAULT,''),
                'sessionid'  => new external_value(PARAM_INT, 'Session ID parameter'),
                'timein' => new external_value(PARAM_INT, 'Checkin timestamp',VALUE_DEFAULT,0),
                'timeout' => new external_value(PARAM_INT, 'Checkout timestamp',VALUE_DEFAULT,0),
                'statusid'  => new external_value(PARAM_INT, 'New statusid number',VALUE_DEFAULT,-1),
            )
        );
    }



    /**
     *
     *
     * This function will update (optionally) statusid, timein and timeout.
     *
     * @param int $studentid Student ID .
     * @param string $username Student's username. (Student ID or username required)
     * @param int $sessionid Session ID (required).
     * @param int $timein Timestamp in millisecond when the student checkin (can be null if not need to update).
     * @param int $timeout Timestamp in millisecond when the student out (can be null if not need to update).
     * @param int $statusid New statusid number (can be null if not need to update).
     * @return array
     * @throws invalid_parameter_exception|dml_exception
     */

    public static function update_log(int $studentid, string $username, int $sessionid, int $timein, int $timeout, int $statusid): array
    {
        $params = self::validate_parameters(self::update_log_parameters(), array(
                'studentid' => $studentid,
                'username' => $username,
                'sessionid' => $sessionid,
                'timein' => $timein,
                'timeout' => $timeout,
                'statusid' => $statusid
            )
        );

        global $DB;
        $return = array('errorcode' => '', 'message' => '');
        if ($studentid != -1) {
            $sql = "SELECT l.*
                FROM {attendance_log} l 
                WHERE l.studentid = $studentid AND l.sessionid = $sessionid";
        }
        else if ($username != '') {
            $sql = "SELECT l.*
                FROM {attendance_log} l 
                LEFT JOIN {user} u ON l.studentid = u.id
                WHERE u.username = $username AND l.sessionid = $sessionid";
        }
        else {
            $return['errorcode'] = '400';
            $return['message'] = "The student ID and username are all missing";
            return $return;
        }
        $result = $DB->get_record_sql($sql);


        if ($result == false)
        {
            $sql1= "SELECT s.*
                FROM {attendance_sessions} s 
                WHERE s.id = :sessionid";
            $result1 = $DB->get_record_sql($sql1,array('sessionid'=>$sessionid),
                IGNORE_MISSING);
            if ($result1 == false) {
                $return['errorcode'] = '404';
                $return['message'] = "This session doesn't exist";
                return $return;
            }
            else {
                $sql2 = null;
                if ($studentid != -1) {
                    $sql2 = "SELECT u.*
                            FROM {user_enrolments} ue
                            LEFT JOIN {enrol} e ON ue.enrolid = e.id
                            LEFT JOIN {user} u ON u.id = ue.userid
                            LEFT JOIN {attendance} a ON a.course = e.courseid
                            LEFT JOIN {attendance_sessions} s ON s.attendanceid = a.id
                            WHERE ue.userid = $studentid AND s.id = $sessionid";
                }
                else if ($username != '') {
                    $username = '"'.$username.'"';
                    $sql2 = "SELECT u.*
                            FROM {user_enrolments} ue
                            LEFT JOIN {enrol} e ON ue.enrolid = e.id
                            LEFT JOIN {user} u ON u.id = ue.userid
                            LEFT JOIN {attendance} a ON a.course = e.courseid
                            LEFT JOIN {attendance_sessions} s ON s.attendanceid = a.id
                            WHERE u.username = $username AND s.id = $sessionid";
                }
                $result2 = $DB->get_record_sql($sql2);
                if ($result2 == false) {
                    $return['errorcode'] = '404';
                    $return['message'] = "This student isn't in this course";
                    return $return;
                }
            }


            $data = (object) array('studentid'=>$studentid,'sessionid'=>$sessionid,
                'statusid'=> 1,'timein'=>time(), 'timeout'=>null);
            if ($timein) {
                if ($result1->sessdate > $timein || $result1->sessdate + $result1->duration < $timein) {
                    $return['errorcode'] = '400';
                    $return['message'] = "The time is not allowed";
                    return $return;
                }
                $data->timein = $timein;
            }
            else {
                if ($result1->sessdate > $data->timein || $result1->sessdate + $result1->duration < $timein) {
                    $return['errorcode'] = '400';
                    $return['message'] = "The time is not allowed";
                    return $return;
                }
            }
            if ($timeout)
            {
                $data->timeout = $timeout;
            }
            if ($statusid!=-1)
            {
                $data->statusid = $statusid;
            }
            if ($DB->insert_record('attendance_log',$data)) {
                $update_session = (object)array('id' => $result1->id, 'lasttaken' => time(), 'lasttakenby' => 1);
                $DB->update_record('attendance_sessions',$update_session);
                $return['message'] = "Created the log successfully";
            }
            else {
                $return['errorcode'] = '400';
                $return['message'] = "Couldn't create the log";
            }
        }
        else {
            $data = (object) array('id'=>$result->id,'statusid'=>$result->statusid,'timein'=>$result->timein,
                'timeout'=>$result->timeout);
            $sql1= "SELECT s.*
                FROM {attendance_sessions} s 
                WHERE s.id = :sessionid";
            $result1 = $DB->get_record_sql($sql1,array('sessionid'=>$sessionid),
                IGNORE_MISSING);
            if ($timein)
            {
                if ($result1->sessdate > $timein || $result1->sessdate + $result1->duration < $timein) {
                    $return['errorcode'] = '400';
                    $return['message'] = "The time is not allowed";
                    return $return;
                }
                $data->timein = $timein;
            }
            if ($timeout)
            {
                $data->timeout = $timeout;
            }
            if ($statusid!=-1)
            {
                $data->statusid = $statusid;
            }
            if ($DB->update_record('attendance_log',$data)) {
                $update_session = (object)array('id' => $result1->id, 'lasttaken' => time(), 'lasttakenby' => 1);
                $DB->update_record('attendance_sessions',$update_session);
                $return['message'] = "Updated the log successfully";
            }
            else {
                $return['errorcode'] = '400';
                $return['message'] = "Couldn't update the log";
            }
        }
        return $return;
    }

    public static function update_log_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT,'Error code'),
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }

    public static function checkin_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, "Student's username parameter",VALUE_DEFAULT,''),
                'roomid' => new external_value(PARAM_INT,"Room ID parameter",VALUE_DEFAULT,-1),
            )
        );
    }

    public static function checkin(string $username, int $roomid) {
        $params = self::validate_parameters(self::checkin_parameters(), array(
                'username' => $username,
                'roomid' => $roomid
            )
        );
        $return = array('errorcode' => '', 'message' => '');
        if ($username == '') {
            $return['errorcode'] = '400';
            $return['message'] = "The student's username is missing";
            return $return;

        }

        if ($roomid == -1) {
            $return['errorcode'] = '400';
            $return['message'] = "The room ID is missing";
            return $return;

        }
        global $DB;
        $time = time();
        $sql1 = "SELECT s.*
                    FROM {attendance_sessions} s 
                    LEFT JOIN {room} r ON s.roomid = r.id
                    WHERE r.id = $roomid AND (s.sessdate + s.duration) >= $time AND s.sessdate <= $time";

        $session = $DB->get_record_sql($sql1);
        if ($session == false) {
            $return['errorcode'] = '404';
            $return['message'] = "There are not any sessions in this time at this room/There are not any rooms with this id";
            return $return;
        }

        $username = '"'.$username.'"';
        //var_dump($username);
        $sql2 = "SELECT u.*
                FROM {user_enrolments} ue
                LEFT JOIN {enrol} e ON ue.enrolid = e.id
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {attendance} a ON a.course = e.courseid
                LEFT JOIN {attendance_sessions} s ON s.attendanceid = a.id
                WHERE u.username = $username AND s.id = :sessionid";

        //var_dump($sql2);

        $user = $DB->get_record_sql($sql2,array('sessionid'=>$session->id));
        if ($user == false) {
            $return['errorcode'] = '404';
            $return['message'] = "This student isn't in this course";
            return $return;
        }

        $sql3 = "SELECT l.*
                FROM {attendance_log} l 
                LEFT JOIN {user} u ON l.studentid = u.id
                WHERE u.username = $username AND l.sessionid = :sessionid";
        $log = $DB->get_record_sql($sql3,array('sessionid'=>$session->id));
        if ($log) {
            $return['errorcode'] = '400';
            $return['message'] = "This student already checked in";
            return $return;
        }
        $data = (object) array('studentid'=>$user->id,'sessionid'=>$session->id,
            'statusid'=> 1,'timein'=>$time, 'timeout'=>null);
        if ($DB->insert_record('attendance_log',$data)) {
            $update_session = (object)array('id' => $session->id, 'lasttaken' => $time, 'lasttakenby' => 1);
            $DB->update_record('attendance_sessions',$update_session);
            $return['message'] = "Checkin successfully";
        }
        else {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't create the log";
        }
        return $return;
    }

    public static function checkin_returns() {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT,'Error code'),
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }

    public static function create_feedback_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'usertaken' => new external_value(PARAM_INT, "User's ID that sent the feedback",VALUE_DEFAULT,-1),
                'roomid' => new external_value(PARAM_INT,"Room ID parameter",VALUE_DEFAULT,-1),
                'description' => new external_value(PARAM_TEXT,"Description",VALUE_DEFAULT,''),
                'userbetaken' => new external_value(PARAM_INT,"User's ID that was mistaken",VALUE_DEFAULT,-1),
                'image' => new external_value(PARAM_TEXT,"Image's link",VALUE_DEFAULT,''),
            )
        );
    }

    public static function create_feedback(int $usertaken, int $roomid, string $description, int $userbetaken, string $image) {
        $params = self::validate_parameters(self::create_feedback_parameters(), array(
                'usertaken' => $usertaken,
                'roomid' => $roomid,
                'description'=>$description,
                'userbetaken'=>$userbetaken,
                'image'=>$image
            )
        );
        $return = array('errorcode' => '', 'message' => '');
        if ($usertaken == -1) {
            $return['errorcode'] = '400';
            $return['message'] = "The usertaken's ID is missing";
            return $return;
        }
        if ($roomid == -1) {
            $return['errorcode'] = '400';
            $return['message'] = "The room's ID is missing";
            return $return;
        }

        if ($description == '') {
            $return['errorcode'] = '400';
            $return['message'] = "The description is missing";
            return $return;
        }
        global $DB;

        $time = time();
        $sql1 = "SELECT a.*
                FROM {attendance} a
                LEFT JOIN {attendance_sessions} s ON a.id = s.attendanceid
                LEFT JOIN {room} r ON s.roomid = r.id
                WHERE r.id = $roomid AND (s.sessdate + s.duration) >= $time AND s.sessdate <= $time";
        $attendance = $DB->get_record_sql($sql1);
        //var_dump($attendance);
        if ($attendance == false) {
            $return['errorcode'] = '404';
            $return['message'] = "There are not any classes in this room now/There is not any room with this ID";
            return $return;
        }
        if ($userbetaken == -1)
            $data = (object) array('timetaken'=>$time,'usertaken'=>$usertaken,'userbetaken' => null,
                'attendanceid'=> $attendance->id,'description'=>$description, 'image'=> $image);
        else
            $data = (object) array('timetaken'=>$time,'usertaken'=>$usertaken,'userbetaken' => $userbetaken,
                'attendanceid'=> $attendance->id,'description'=>$description, 'image'=> $image);

        if ($DB->insert_record('attendance_feedback',$data)) {
            $return['message'] = "Created the record successfully";
        }
        else {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't create the record";
        }
        return $return;
    }
    public static function create_feedback_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT,'Error code'),
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }
}