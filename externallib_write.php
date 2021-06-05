<?php

defined('MOODLE_INTERNAL') || die();

require_once('../../lib/filelib.php');
require_once('../../lib/weblib.php');

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

        if ($image_usertaken == false) {
            $return['errorcode'] = '404';
            $return['message'] = "This user didn't have any registered images";
            return $return;
        }

        $domain = $CFG->wwwroot;

        $sql4 = "SELECT t.userid, t.token
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

        $url = self::upload_image($domain,$image,'image_feedback.jpg',$student->id,
            $auth->userid,$auth->token,'image_feedback',false, false);

        if ($url == '') {
            $return['errorcode'] = '400';
            $return['message'] = "Couldn't upload the images";
            return $return;
        }

        if ($userbetaken == '') {
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

            $data = (object)array('timetaken' => $time, 'usertaken' => $student->id, 'userbetaken' => $student2->id,
                'attendanceid' => $attendance->id, 'sessionid' => $attendance->sessionid,
                'description' => $description, 'image_register' => $image_usertaken->image_front, 'image' => $url);
        }

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
        global $CFG;
        global $DB;

        $return = array('errorcode' => '', 'message' => '');
        $domain = $CFG->wwwroot;
        var_dump($domain);
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

        $sql3 = "SELECT t.userid, t.token
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

        $left_url = self::upload_image($domain,$image_left,'image_left.jpg',$student->id,
            $auth->userid,$auth->token,'image_left',false, true);
        $right_url = self::upload_image($domain,$image_right,'image_right.jpg',$student->id,
            $auth->userid,$auth->token,'image_right',false, true);
        $front_url = self::upload_image($domain,$image_front,'image_front.jpg',$student->id,
            $auth->userid,$auth->token,'image_front', $replace, true);

        if ($front_url == '' || $left_url == '' || $right_url == '') {
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
    public static function upload_image(string $domain, string $image, string $filename, int $userid, int $auth_user,
                                        string $token, string $filearea, bool $replace, bool $delete): string
    {

        $curl = new curl;
        $params = array(
            'wstoken' => $token,
            'wsfunction' => 'core_files_upload',
            'moodlewsrestformat' => 'json',
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
            'filecontent' => $image,
            'contextlevel' => 'user',
            'instanceid' => $auth_user,
        );
        $functionname = 'core_files_upload';
        $restformat = 'json';
        $serverurl = $domain . '/webservice/rest/server.php';

        $json = $curl->post($serverurl . $restformat, $params);

        $res = json_decode($json);
        var_dump('Called core_files_upload');
        var_dump($res);
        if ($replace == true) {
            self::update_avatar($domain, $token, $userid, $res->itemid);
        }
        $url = '';

        $ch = curl_init();
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
    public static function update_avatar(string $domain, string $token, string $userid, int $itemid) {
        $curl = new curl;

        $params = array(
            'userid' => $userid,
            'draftitemid' => $itemid
        );
        $functionname = 'core_user_update_picture';
        $restformat = 'json';
        $serverurl = $domain .'/webservice/rest/server.php' . '?wstoken=' . $token .'&wsfunction=' . $functionname;
        $restformat = ($restformat == 'json') ?
            '&moodlewsrestformat=' . $restformat : '';
        $json = $curl->post($serverurl . $restformat, $params);
        $res = json_decode($json);
        if ($res->success == false) {
            return '';
        }
        else {
            return $res->profileimageurl;
        }
    }
}