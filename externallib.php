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
 * @package    local_wsgetroles
 * @copyright  2020 corvus albus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/externallib.php');

/**
 * Web service API definition.
 *
 * @package local_wsgetroles
 * @copyright 2020 corvus albus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_wsgetreports_external extends external_api {

    // Functionset for get_roles() ************************************************************************************************.

    /**
     * Parameter description for get_roles().
     *
     * @return external_function_parameters.
     */
    public static function get_a_report_parameters() {
        return new external_function_parameters(
            array(
                'studentid' => new external_value(PARAM_TEXT, 'student ID parameter'),
                'scheduleid'  => new external_value(PARAM_INT, 'schedule ID parameter'),
            )
        );
    }

    /**
     * Return roleinformation.
     *
     * This function returns roleid, rolename and roleshortname for all roles or for given roles.
     *
     * @param string $studentid Student ID.
     * @param string $scheduleid Schedule ID.
     * @return object The student's report.
     * @throws invalid_parameter_exception
     */
    public static function get_a_report(string $studentid, string $scheduleid)
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_a_report_parameters(), array(
            'studentid' => $studentid,
            'scheduleid' => $scheduleid));
        global $DB;

        $sql = "SELECT r.*, a.classid
                  FROM {report} r
            LEFT JOIN {schedule} s ON s.id = r.scheduleid
            LEFT JOIN {attendance} a ON a.id = s.attendanceid
             WHERE (r.studentid = :studentid AND r.scheduleid = :scheduleid)
              ORDER BY r.id ASC";

        return $DB->get_records_sql($sql,array('studentid'=>$studentid,'scheduleid'=>$scheduleid));
    }

    /**
     * Parameter description for create_sections().
     *
     * @return external_description
     */
    public static function get_a_report_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'report ID', VALUE_DEFAULT, null),
                    'studentid' => new external_value(PARAM_TEXT, 'student ID', VALUE_DEFAULT, null),
                    'classid' => new external_value(PARAM_TEXT, 'class ID', VALUE_DEFAULT, null),
                    'scheduleid' => new external_value(PARAM_INT, 'schedule ID', VALUE_DEFAULT, null),
                    'timein' => new external_value(PARAM_TEXT, 'time when the student checkin', VALUE_DEFAULT, null),
                    'timeout' => new external_value(PARAM_TEXT, 'time when the student go out', VALUE_DEFAULT, null),
                    'status' => new external_value(PARAM_INT, 'status of the student', VALUE_DEFAULT, null),
                )
            )
        );
    }

    public static function get_reports_parameters() {
        return new external_function_parameters(
            array(
                'attendanceid' => new external_value(PARAM_INT, 'Attendance ID'),
            )
        );
    }
    public static function get_reports($attendanceid) {
        $params = self::validate_parameters(self::get_reports_parameters(), array(
            'attendanceid' => $attendanceid
            )
        );
        global $DB;
        $sql = "SELECT r.*, a.classid
                FROM {report} r
                LEFT JOIN {schedule} s ON r.scheduleid = s.id 
                LEFT JOIN {attendance} a 
                ON s.attendanceid = a.id 
                WHERE a.id = :attendanceid
                ORDER BY r.id ASC";

        return $DB->get_records_sql($sql,array('attendanceid' => $attendanceid));
    }

    public static function get_reports_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'report ID', VALUE_DEFAULT, null),
                    'studentid' => new external_value(PARAM_TEXT, 'student ID', VALUE_DEFAULT, null),
                    'classid' => new external_value(PARAM_TEXT, 'class ID', VALUE_DEFAULT, null),
                    'scheduleid' => new external_value(PARAM_INT, 'schedule ID', VALUE_DEFAULT, null),
                    'timein' => new external_value(PARAM_TEXT, 'time when the student checkin', VALUE_DEFAULT, null),
                    'timeout' => new external_value(PARAM_TEXT, 'time when the student go out', VALUE_DEFAULT, null),
                    'status' => new external_value(PARAM_INT, 'status of the student', VALUE_DEFAULT, null),
                )
            )
        );
    }

    /**
     * Parameter description for update_report().
     *
     * @return external_function_parameters.
     */

    public static function update_report_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'studentid' => new external_value(PARAM_TEXT, 'Student ID parameter'),
                'scheduleid'  => new external_value(PARAM_INT, 'Schedule ID parameter'),
                'timein' => new external_value(PARAM_TEXT, 'Checkin datetime',VALUE_OPTIONAL),
                'timeout' => new external_value(PARAM_TEXT, 'Datetime when the student out',VALUE_OPTIONAL),
                'status'  => new external_value(PARAM_INT, 'New status number',VALUE_OPTIONAL),
            )
        );
    }

    /**
     *
     *
     * This function will update (optionally) status, timein and timeout. If this student's record
     * is not found, it will create a new record in the report table.
     *
     * @param string $studentid Student ID (required).
     * @param int $scheduleid Schedule ID (required).
     * @param string $timein Datetime when the student checkin (can be null if not need to update).
     * @param string $timeout Datetime when the student out (can be null if not need to update).
     * @param int $status New status number (can be null if not need to update).
     * @return array
     * @throws invalid_parameter_exception|dml_exception
     */

    public static function update_report(string $studentid, int $scheduleid, string $timein, string $timeout, int $status): array
    {
        $params = self::validate_parameters(self::update_report_parameters(), array(
                'studentid' => $studentid,
                'scheduleid' => $scheduleid,
                'timein' => $timein,
                'timeout' => $timeout,
                'status' => $status
            )
        );

        global $DB;


        $sql = "SELECT r.*
                FROM {report} r 
                WHERE r.studentid = :studentid AND r.scheduleid = :scheduleid";
        $result = $DB->get_record_sql($sql,array('studentid'=>$studentid,'scheduleid'=>$scheduleid),IGNORE_MISSING);

        $return = array('message' => '');
        if ($result == false)
        {

            $data = (object) array('studentid'=>$studentid,'scheduleid'=>$scheduleid,
                'timein'=>null,'timeout'=>null, 'status'=>null);
            if ($timein)
            {
                $data->timein = $timein;
            }
            if ($timeout)
            {
                $data->timeout = $timeout;
            }
            if ($status)
            {
                $data->status = $status;
            }
            if ($DB->insert_record('report',$data))
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
            if ($status)
            {
                $data->status = $status;
            }
            if ($DB->update_record('report',$data)) {
                $return['message'] = "Updated the report successfully";
            }
            else {
                $return['message'] = "Couldn't update the report";
            }
        }
        return $return;
    }

    public static function update_report_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
            'message' => new external_value(PARAM_TEXT, 'Message to the back-end'),
            )
        );
    }


    // Functionset for get_roles() ************************************************************************************************.

    /**
     * Parameter description for get_roles().
     *
     * @return external_function_parameters.
     */
    public static function get_roles_parameters() {
        return new external_function_parameters(
            array(
                'ids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'roleid')
                    , 'List of roleids. Wrong ids will return an array with "null" for the other role settings.
                                If all three lists (ids, shortnames, names) are empty, return all roles.',
                    VALUE_DEFAULT, array()),
                'shortnames' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'shortname')
                    , 'List of role shortnames. Wrong strings will return an array with "null" for the other role settings.
                                If all three lists (ids, shortnames, names) are empty, return all roles.',
                    VALUE_DEFAULT, array()),
                'names' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'name')
                    , 'List of role names. Wrong strings will return an array with "null" for the other role settings.
                                If all three lists (ids, shortnames, names) are empty, return all roles.',
                    VALUE_DEFAULT, array()),
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
    public static function get_roles($ids = [], $shortnames = [], $names = []) {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_roles_parameters(), array(
            'ids' => $ids,
            'shortnames' => $shortnames,
            'names' => $names));

        $allroles = get_all_roles();
        $idsfound = array();
        $entriesnotfound = array();

        // Search for appropriate roles. If role found put id to idsfound. If not remember entry in entriesnotfound.
        $allentries = array('id' => $ids, 'name' => $names, 'shortname' => $shortnames);
        foreach ($allentries as $key => $entries) {
            if (!empty($entries)) {
                foreach ($entries as $entry) {
                    $entriesnotfound[] = array('name' => $key, 'value' => $entry);
                    foreach ($allroles as $r) {
                        if ($r->$key == $entry) {
                            $idsfound[] = $r->id;
                            // Entry found. Remove it from $entriesnotfound.
                            array_pop($entriesnotfound);
                            break;
                        }
                    }
                }
            }
        }

        // If all input arrays are empty, return all roles. Collect all role ids in $idsfound.
        if (empty(array_merge($ids, $names, $shortnames))) {
            $idsfound = array_column($allroles, 'id');
        }

        // Collect information of all found roles.
        foreach ($allroles as $r) {
            if (in_array($r->id, $idsfound)) {
                $roles[] = get_object_vars($r);
            }
        }

        // Add entries not found. All array elements despite of the given will be null.
        foreach ($entriesnotfound as $entry) {
            $roles[] = array($entry['name'] => $entry['value']);
        }

        return $roles;
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
                    'name' => new external_value(PARAM_TEXT, 'role name', VALUE_DEFAULT, null),
                    'shortname' => new external_value(PARAM_TEXT, 'role shortname', VALUE_DEFAULT, null),
                    'description' => new external_value(PARAM_TEXT, 'role description', VALUE_DEFAULT, null),
                    'sortorder' => new external_value(PARAM_INT, 'role sort order', VALUE_DEFAULT, null),
                    'archetype' => new external_value(PARAM_TEXT, 'role archetype', VALUE_DEFAULT, null),
                )
            )
        );
    }




////////////
    public static function get_schedules_parameters() {
        return new external_function_parameters(
            array(
                'roomid' => new external_value(PARAM_TEXT, 'student ID parameter'),
                'session'  => new external_value(PARAM_INT, 'class ID parameter'),
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
    public static function get_schedules($roomid,$session)
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_schedules_parameters(), array(
            'roomid' => $roomid,
            'session' => $session,
        ));
        global $DB;

        $sql = "SELECT *
                      FROM {attendance} a
                    LEFT JOIN {schedule} s ON a.id = s.attendanceid
                 WHERE (a.room = :roomid AND s.session = :session)
                  ORDER BY s.id ASC";

        return $DB->get_records_sql($sql,array('roomid'=>$roomid,'session'=>$session));
    }

    /**
     * Parameter description for create_sections().
     *
     * @return external_description
     */
    public static function get_schedules_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'report ID', VALUE_DEFAULT, null),
                    'attendanceid' => new external_value(PARAM_INT, 'report ID', VALUE_DEFAULT, null),
                    'room' => new external_value(PARAM_TEXT, 'room ID', VALUE_DEFAULT, null),
                    'classid' => new external_value(PARAM_TEXT, 'class ID', VALUE_DEFAULT, null),
                    'session' => new external_value(PARAM_INT, 'session of class', VALUE_DEFAULT, null),

                )
            )
        );
    }

     // Functionset for get_roles() ************************************************************************************************.

    /**
     * Parameter description for get_roles().
     *
     * @return external_function_parameters.
     */
    public static function get_roles_parameters() {
        return new external_function_parameters(
            array(
                'ids' => new external_multiple_structure(
                         new external_value(PARAM_INT, 'roleid')
                            , 'List of roleids. Wrong ids will return an array with "null" for the other role settings.
                                If all three lists (ids, shortnames, names) are empty, return all roles.',
                                        VALUE_DEFAULT, array()),
                'shortnames' => new external_multiple_structure(
                         new external_value(PARAM_TEXT, 'shortname')
                            , 'List of role shortnames. Wrong strings will return an array with "null" for the other role settings.
                                If all three lists (ids, shortnames, names) are empty, return all roles.',
                                        VALUE_DEFAULT, array()),
                'names' => new external_multiple_structure(
                         new external_value(PARAM_TEXT, 'name')
                            , 'List of role names. Wrong strings will return an array with "null" for the other role settings.
                                If all three lists (ids, shortnames, names) are empty, return all roles.',
                                        VALUE_DEFAULT, array()),
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
    public static function get_roles($ids = [], $shortnames = [], $names = []) {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_roles_parameters(), array(
            'ids' => $ids,
            'shortnames' => $shortnames,
            'names' => $names));

        $allroles = get_all_roles();
        $idsfound = array();
        $entriesnotfound = array();

        // Search for appropriate roles. If role found put id to idsfound. If not remember entry in entriesnotfound.
        $allentries = array('id' => $ids, 'name' => $names, 'shortname' => $shortnames);
        foreach ($allentries as $key => $entries) {
            if (!empty($entries)) {
                foreach ($entries as $entry) {
                    $entriesnotfound[] = array('name' => $key, 'value' => $entry);
                    foreach ($allroles as $r) {
                        if ($r->$key == $entry) {
                            $idsfound[] = $r->id;
                            // Entry found. Remove it from $entriesnotfound.
                            array_pop($entriesnotfound);
                            break;
                        }
                    }
                }
            }
        }

        // If all input arrays are empty, return all roles. Collect all role ids in $idsfound.
        if (empty(array_merge($ids, $names, $shortnames))) {
            $idsfound = array_column($allroles, 'id');
        }

        // Collect information of all found roles.
        foreach ($allroles as $r) {
            if (in_array($r->id, $idsfound)) {
                $roles[] = get_object_vars($r);
            }
        }

        // Add entries not found. All array elements despite of the given will be null.
        foreach ($entriesnotfound as $entry) {
            $roles[] = array($entry['name'] => $entry['value']);
        }

        return $roles;
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
                            'name' => new external_value(PARAM_TEXT, 'role name', VALUE_DEFAULT, null),
                            'shortname' => new external_value(PARAM_TEXT, 'role shortname', VALUE_DEFAULT, null),
                            'description' => new external_value(PARAM_TEXT, 'role description', VALUE_DEFAULT, null),
                            'sortorder' => new external_value(PARAM_INT, 'role sort order', VALUE_DEFAULT, null),
                            'archetype' => new external_value(PARAM_TEXT, 'role archetype', VALUE_DEFAULT, null),
                        )
                )
        );
    }




////////////
    public static function get_schedules_parameters() {
            return new external_function_parameters(
                array(
                    'roomid' => new external_value(PARAM_TEXT, 'student ID parameter'),
                    'time'  => new external_value(PARAM_TEXT, 'class ID parameter'),
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
        public static function get_schedules($roomid,$time)
        {

            // Validate parameters passed from web service.
            $params = self::validate_parameters(self::get_schedules_parameters(), array(
                'roomid' => $roomid,
                'time' => $time,
                ));
            global $DB;

            $sql = "SELECT *
                      FROM {attendance} a
                    LEFT JOIN {schedule} s ON a.id = s.attendanceid
                 WHERE (a.room = :roomid AND s.time = :time)
                  ORDER BY s.id ASC";

            return $DB->get_records_sql($sql,array('roomid'=>$roomid,'time'=>$time
           ));
        }

        /**
         * Parameter description for create_sections().
         *
         * @return external_description
         */
        public static function get_schedules_returns() {
            return new external_multiple_structure(
                new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'report ID', VALUE_DEFAULT, null),
                        'attendanceid' => new external_value(PARAM_INT, 'report ID', VALUE_DEFAULT, null),
                        'room' => new external_value(PARAM_TEXT, 'room ID', VALUE_DEFAULT, null),
                        'classid' => new external_value(PARAM_TEXT, 'class ID', VALUE_DEFAULT, null),
                        'time' => new external_value(PARAM_INT, 'time of class', VALUE_DEFAULT, null),

                    )
                )
            );
        }
}
