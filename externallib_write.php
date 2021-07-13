<?php

defined('MOODLE_INTERNAL') || die();

require_once('lib/filelib.php');
require_once('lib/weblib.php');
require_once('files/externallib.php');
require_once('user/externallib.php');
require_once('mod/attendance/locallib.php');
require_once('externallib.php');
require_once('externallib_frontend.php');

class local_webservices_external_write extends external_api {

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

    public static function checkin_online_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, "Student's username parameter",VALUE_DEFAULT,''),
                'sessionid' => new external_value(PARAM_INT,"Session ID",VALUE_DEFAULT,-1),
                'image_front' => new external_value(PARAM_TEXT,"Front image's base64 string",VALUE_DEFAULT,''),
                'image_left' => new external_value(PARAM_TEXT,"Left image's base64 string",VALUE_DEFAULT,''),
                'image_right' => new external_value(PARAM_TEXT,"Right image's base64 string",VALUE_DEFAULT,''),
                'result' => new external_value(PARAM_INT,"Detect result number",VALUE_DEFAULT,-1),
            )
        );
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public static function checkin_online(string $username, int $sessionid, string $image_front, string $image_left, string $image_right,
    int $result): array
    {
        $params = self::validate_parameters(self::checkin_online_parameters(), array(
                'username' => $username,
                'sessionid' => $sessionid,
                'image_front' => $image_front,
                'image_left' => $image_left,
                'image_right' => $image_right,
                'result' => $result
            )
        );
        $return = array('errorcode' => '', 'message' => '');
        if ($username == '') {
            $return['errorcode'] = '400';
            $return['message'] = "The student's username is missing";
            return $return;
        }

        if ($sessionid == -1) {
            $return['errorcode'] = '400';
            $return['message'] = "The session ID is missing";
            return $return;
        }
        if ($image_front == '' || $image_left == '' || $image_right == '') {
            $return['errorcode'] = '400';
            $return['message'] = "The images are missing";
            return $return;
        }
        if ($result == -1) {
            $return['errorcode'] = '400';
            $return['message'] = "Result is missing";
            return $return;
        }
        global $DB;

        $time = time();

        $sql1 = "SELECT s.*
                    FROM {attendance_sessions} s 
                    WHERE s.id = $sessionid";

        $session = $DB->get_record_sql($sql1);
        if ($session == false) {
            $return['errorcode'] = '404';
            $return['message'] = "There are not any sessions with this ID";
            return $return;
        }
        if ($session->onlinetime == null || $session->onlineduration == null) {
            $return['errorcode'] = '400';
            $return['message'] = "This session is not available online";
            return $return;
        }
        if ($session->onlinetime > $time || $session->onlinetime + $session->onlineduration < $time ||
        $session->sessdate > $time || $session->sessdate + $session->duration < $time) {
            $return['errorcode'] = '400';
            $return['message'] = "Checkin outside the time allowed";
            return $return;
        }
        $sql2 = "SELECT u.*
                FROM {user} u
                LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                LEFT JOIN {context} con ON con.id = ra.contextid
                LEFT JOIN {role} r ON r.id = ra.roleid
                LEFT JOIN {attendance} a ON a.course = con.instanceid
                LEFT JOIN {attendance_sessions} s ON s.attendanceid = a.id
                WHERE u.username = :username AND s.id = $sessionid AND r.shortname = 'student'";

        $user = $DB->get_record_sql($sql2,array('username'=>$username));
        if ($user == false) {
            $return['errorcode'] = '404';
            $return['message'] = "This student isn't in this course";
            return $return;
        }

        $sql3 = "SELECT l.*
                FROM {attendance_log} l 
                LEFT JOIN {user} u ON l.studentid = u.id
                WHERE u.username = :username AND l.sessionid = $sessionid";
        $log = $DB->get_record_sql($sql3,array('username'=>$username));
        $data = null;
        if ($log) {
            if ($result == 1)
                $data = (object)array('id' => $log->id, 'timeout' => $time, 'timetaken' => $time);
            else {
                $data = (object)array('id' =>$log->id,'statusid' => 4, 'timeout' => null, 'timetaken' => $time);
            }
            $DB->update_record('attendance_log',$data);
        }
        else {
            if ($result == 1)
                $data = (object)array('studentid' => $user->id, 'sessionid' => $session->id,
                    'statusid' => 1, 'timein' => $time, 'timeout' => null, 'isonlinecheckin' => 1, 'timetaken' => $time);
            else {
                $data = (object)array('studentid' => $user->id, 'sessionid' => $session->id,
                    'statusid' => 4, 'timein' => $time, 'timeout' => null, 'isonlinecheckin' => 1, 'timetaken' => $time);
            }
            $DB->insert_record('attendance_log',$data);
        }
        $update_session = (object)array('id' => $session->id, 'lasttaken' => $time, 'lasttakenby' => 1);
        $DB->update_record('attendance_sessions',$update_session);

        $sql4 = "SELECT t.userid
            FROM {external_tokens} t
            LEFT JOIN {external_services} s ON s.id = t.externalserviceid
            WHERE s.name LIKE :string1 OR s.name LIKE :string2 OR s.name LIKE :string3 OR s.name LIKE :string4";


        $auth = $DB->get_record_sql($sql4,array('string1'=>'%local_webservices%','string2'=>'%Local_webservices%',
            'string3'=>'%localWebservices%','string4'=>'%LocalWebservices%'));

        if ($auth == false) {
            $return['errorcode'] = '404';
            $return['message'] = "The external service was named incorrectly";
            return $return;
        }
        $url_front = self::upload_image($image_front,'image_front_checkin.jpg',
            $user->id,$auth->userid,'checkin',false,false);

        $url_left = self::upload_image($image_left,'image_left_checkin.jpg',
            $user->id,$auth->userid,'checkin',false,false);

        $url_right = self::upload_image($image_right,'image_right_checkin.jpg',
            $user->id,$auth->userid,'checkin',false,false);

        if ($url_front == '' || $url_left == '' || $url_right == '')
        {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't upload the images";
            return $return;
        }
        $data_url = (object) array('studentid'=>$user->id,'sessionid'=>$session->id,'image_front'=>$url_front,
            'image_left'=>$url_left,'image_right'=>$url_right,'timetaken'=>$time);
        $DB->insert_record('attendance_checkin_images',$data_url);
        $return['message'] = "Checkin successfully";

        return $return;
    }

    public static function checkin_online_returns(): external_single_structure
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

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function checkin(string $username, int $roomid): array
    {
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

        $sql2 = "SELECT u.*
                FROM {user_enrolments} ue
                LEFT JOIN {enrol} e ON ue.enrolid = e.id
                LEFT JOIN {user} u ON u.id = ue.userid
                LEFT JOIN {attendance} a ON a.course = e.courseid
                LEFT JOIN {attendance_sessions} s ON s.attendanceid = a.id
                WHERE u.username = $username AND s.id = :sessionid";

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

        $max_time = 60*30;

        if ($session->sessdate + $max_time >= $time)
            $data = (object) array('studentid'=>$user->id,'sessionid'=>$session->id,
                'statusid'=> 1,'timein'=>$time, 'timeout'=>null);
        else
            $data = (object) array('studentid'=>$user->id,'sessionid'=>$session->id,
                'statusid'=> 3,'timein'=>$time, 'timeout'=>null);

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

    public static function checkin_returns(): external_single_structure
    {
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
                'usertaken' => new external_value(PARAM_TEXT, "User's username that sent the feedback",VALUE_DEFAULT,''),
                'roomid' => new external_value(PARAM_INT,"Room ID parameter",VALUE_DEFAULT,-1),
                'description' => new external_value(PARAM_TEXT,"Description",VALUE_DEFAULT,''),
                'userbetaken' => new external_value(PARAM_TEXT,"User's username that was mistaken",VALUE_DEFAULT,''),
                'image' => new external_value(PARAM_TEXT,"Feedback image's base64 string",VALUE_DEFAULT,''),
            )
        );
    }

    /**
     * @param string $usertaken
     * @param int $roomid
     * @param string $description
     * @param string $userbetaken
     * @param string $image
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function create_feedback(string $usertaken, int $roomid, string $description, string $userbetaken,
                                           string $image): array
    {
        $params = self::validate_parameters(self::create_feedback_parameters(), array(
                'usertaken' => $usertaken,
                'roomid' => $roomid,
                'description'=> $description,
                'userbetaken'=> $userbetaken,
                'image'=> $image
            )
        );
        $return = array('errorcode' => '', 'message' => '');
        if ($usertaken == '') {
            $return['errorcode'] = '400';
            $return['message'] = "The usertaken's username is missing";
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
        global $CFG;
        $time = time();
        $sql1 = "SELECT a.*,s.id as sessionid
                FROM {attendance} a
                LEFT JOIN {attendance_sessions} s ON a.id = s.attendanceid
                LEFT JOIN {room} r ON s.roomid = r.id
                WHERE r.id = $roomid AND (s.sessdate + s.duration) >= $time AND s.sessdate <= $time";
        $attendance = $DB->get_record_sql($sql1);

        if ($attendance == false) {
            $return['errorcode'] = '404';
            $return['message'] = "There are not any classes in this room now/There is not any room with this ID";
            return $return;
        }

        $sql2 = "SELECT u.*
                FROM {user} u
                LEFT JOIN {role_assignments} ra ON ra.userid = u.id
                LEFT JOIN {context} con ON con.id = ra.contextid
                LEFT JOIN {role} r ON r.id = ra.roleid
                LEFT JOIN {attendance} a ON a.course = con.instanceid
                WHERE a.id = $attendance->id AND u.username = :usertaken AND r.shortname = 'student'";
        $student = $DB->get_record_sql($sql2,array('usertaken'=>$usertaken));

        if ($student == false) {
            $return['errorcode'] = '404';
            $return['message'] = "This student isn't in this class";
            return $return;
        }

        $sql3 = "SELECT i.*
                FROM {attendance_images} i
                LEFT JOIN {user} u ON i.studentid = u.id
                WHERE u.username = :usertaken";

        $image_usertaken = $DB->get_record_sql($sql3,array('usertaken'=>$usertaken));

//        if ($image_usertaken == false) {
//            $return['errorcode'] = '404';
//            $return['message'] = "This user didn't have any registered images";
//            return $return;
//        }

        $sql4 = "SELECT t.userid
                FROM {external_tokens} t
                LEFT JOIN {external_services} s ON s.id = t.externalserviceid
                WHERE s.name LIKE :string1 OR s.name LIKE :string2 OR s.name LIKE :string3 OR s.name LIKE :string4";


        $auth = $DB->get_record_sql($sql4,array('string1'=>'%local_webservices%','string2'=>'%Local_webservices%',
            'string3'=>'%localWebservices%','string4'=>'%LocalWebservices%'));

        if ($auth == false) {
            $return['errorcode'] = '404';
            $return['message'] = "The external service was named incorrectly";
            return $return;
        }

        $url = self::upload_image($image,'image_feedback.jpg',$student->id,
            $auth->userid,'image_feedback',false, false);

        if ($url == '') {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't upload the images";
            return $return;
        }
        if ($userbetaken == '') {
            if ($image_usertaken == false)
                $data = (object)array('timetaken' => $time, 'usertaken' => $student->id, 'userbetaken' => null,
                'attendanceid' => $attendance->id, 'sessionid' => $attendance->sessionid,
                'description' => $description, 'image_register' => null, 'image' => $url);
            else
                $data = (object)array('timetaken' => $time, 'usertaken' => $student->id, 'userbetaken' => null,
                    'attendanceid' => $attendance->id, 'sessionid' => $attendance->sessionid,
                    'description' => $description, 'image_register' => $image_usertaken->image_front, 'image' => $url);
        }
        else {

            $sql4 = "SELECT u.*
                FROM {user} u
                WHERE u.username = :userbetaken";

            $student2 = $DB->get_record_sql($sql4,array('userbetaken' => $userbetaken));

            if ($student2 == false) {
                $return['errorcode'] = '404';
                $return['message'] = "The userbetaken's username is wrong";
                return $return;
            }
            if ($image_usertaken == false)
                $data = (object)array('timetaken' => $time, 'usertaken' => $student->id, 'userbetaken' => $student2->id,
                    'attendanceid' => $attendance->id, 'sessionid' => $attendance->sessionid,
                    'description' => $description, 'image_register' => null, 'image' => $url);
            else
                $data = (object)array('timetaken' => $time, 'usertaken' => $student->id, 'userbetaken' => $student2->id,
                    'attendanceid' => $attendance->id, 'sessionid' => $attendance->sessionid,
                    'description' => $description, 'image_register' => $image_usertaken->image_front, 'image' => $url);
        }

        if ($DB->insert_record('attendance_feedback',$data)) {

            $sql5 = "SELECT cm.id
                FROM {course_modules} cm
                LEFT JOIN {attendance} a ON a.course = cm.course
                LEFT JOIN {modules} m ON cm.module = m.id
                WHERE a.id = $attendance->id AND m.name = 'attendance'";

            $id = $DB->get_record_sql($sql5);
            $userfrom = get_admin();
            $b = new local_webservices_frontend();
            $teachers = $b->get_teachers_by_course_id((int)$attendance->course);
            $url_t = new moodle_url('/mod/attendance/take.php?id='.$id->id.'&sessionid='.$attendance->sessionid.'&grouptype=0');
            $course = (object) array('id'=> null);
            $course->id = $attendance->course;
            foreach ($teachers as $teacher){
                    send_notification($userfrom, $teacher, $id->id, $course, 'attendance', $description, $url_t);
            }

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

    public static function create_images_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, "Student's username",VALUE_DEFAULT,''),
                'image_front' => new external_value(PARAM_TEXT,"Front image's base64 string",VALUE_DEFAULT,''),
                'image_left' => new external_value(PARAM_TEXT,"Left image's base64 string",VALUE_DEFAULT,''),
                'image_right' => new external_value(PARAM_TEXT,"Right image's base64 string",VALUE_DEFAULT,''),
                'replace' => new external_value(PARAM_BOOL, "Decided if this user want to replace to avatar or not",
                    VALUE_DEFAULT,0),
            )
        );
    }

    /**
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function create_images(string $username, string $image_front, string $image_left, string $image_right, bool $replace): array
    {
        $params = self::validate_parameters(self::create_images_parameters(), array(
                'username' => $username,
                'image_front' => $image_front,
                'image_left' => $image_left,
                'image_right' => $image_right,
                'replace' => $replace
            )
        );
        global $DB;

        $return = array('errorcode' => '', 'message' => '');


        $sql1 = "SELECT u.*
                FROM {user} u
                WHERE u.username = :username";
        $student = $DB->get_record_sql($sql1,array('username'=>$username));
        if ($student == false) {
            $return['errorcode'] = '404';
            $return['message'] = "There are not any students with this username";
            return $return;
        }
        $sql2 = "SELECT i.id
                FROM {attendance_images} i
                WHERE i.studentid = $student->id";
        $record = $DB->get_record_sql($sql2);


        $sql3 = "SELECT t.userid
                FROM {external_tokens} t
                LEFT JOIN {external_services} s ON s.id = t.externalserviceid
                WHERE s.name LIKE :string1 OR s.name LIKE :string2 OR s.name LIKE :string3 OR s.name LIKE :string4";


        $auth = $DB->get_record_sql($sql3,array('string1'=>'%local_webservices%','string2'=>'%Local_webservices%',
            'string3'=>'%localWebservices%','string4'=>'%LocalWebservices%'));

        if ($auth == false) {
            $return['errorcode'] = '404';
            $return['message'] = "The external service was named incorrectly";
            return $return;
        }

        $left_url = self::upload_image($image_left,'image_left.jpg',$student->id,$auth->userid, 'image_left',false, true);

        if ($left_url == '') {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't upload the images";
            return $return;
        }

        $right_url = self::upload_image($image_right,'image_right.jpg',$student->id,$auth->userid, 'image_right',false, true);

        if ($right_url == '') {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't upload the images";
            return $return;
        }

        $front_url = self::upload_image($image_front,'image_front.jpg',$student->id,$auth->userid, 'image_front', $replace, true);

        if ($front_url == '') {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't upload the images";
            return $return;
        }

        if ($record == false) {

            $data = (object) array('studentid'=>$student->id,'image_front'=>$front_url,'image_left'=>$left_url,'image_right'=>$right_url);
            if ($DB->insert_record('attendance_images',$data)) {
                $return['message'] = "Created the record successfully";
            }
            else {
                $return['errorcode'] = '400';
                $return['message'] = "Couldn't create the record";
            }

        }
        else {

            $data = (object) array('id'=> $record->id,'studentid'=>$student->id,
                'image_front'=>$front_url,'image_left'=>$left_url,'image_right'=>$right_url);
            if ($DB->update_record('attendance_images',$data)) {
                $return['message'] = "Updated the record successfully";
            }
            else {
                $return['errorcode'] = '400';
                $return['message'] = "Couldn't update the record";
            }
        }
        return $return;
    }

    public static function create_images_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'errorcode' => new external_value(PARAM_TEXT,'Error code'),
                'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }

    /**
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function upload_image(string $image, string $filename, int $userid, int $auth_user,
                                        string $filearea, bool $replace, bool $delete): string
    {

        $res = core_files_external::upload(null ,'user','draft',0,'/',$filename,
            $image,'user',$auth_user);

        $res = (object) $res;

        if ($replace == true) {
            self::update_avatar($userid, $res->itemid);
        }
        $url = '';

        global $DB;

        $sql = "SELECT c.*
                FROM {context} c 
                WHERE c.contextlevel = 30 AND c.instanceid = $userid ";

        $context = $DB->get_record_sql($sql);

        if ($context == false) {
            return '';
        }
        $fs = get_file_storage();
        $itemid = rand(100000000,999999999);

        if ($delete == true) {

            //delete files in filearea
            $fs->delete_area_files($context->id, 'local_webservices', $filearea);

        }
        else {
            $files = $fs->get_area_files($context->id, 'local_webservices', $filearea);

            for (;; $itemid = rand(100000000,999999999)) {
                $flag = true;
                foreach ($files as $file) {
                    if ($file->is_directory())
                        continue;
                    if ($file->get_itemid() == $itemid) {
                        $flag = false;
                        break;
                    }
                }
                if ($flag == true)
                    break;
            }
        }

        file_save_draft_area_files($res->itemid, $context->id, 'local_webservices', $filearea, $itemid);

        $files = $fs->get_area_files($context->id, 'local_webservices', $filearea, $itemid);

        foreach ($files as $file) {
            if ($file->is_directory())
                continue;
            $object = new moodle_url(moodle_url::make_pluginfile_url($file->get_contextid(),$file->get_component(),$file->get_filearea(),
                $file->get_itemid(),$file->get_filepath(),$file->get_filename()));
            $url = $object->out();

        }

        return $url;
    }

    /**
     * @throws moodle_exception
     */
    public static function update_avatar(string $userid, int $itemid) {

        $res = core_user_external::update_picture($itemid,false,$userid);

        if ($res->success == false) {
            return '';
        }
        else {
            return $res->profileimageurl;
        }
    }
}