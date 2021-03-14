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
                'classid'  => new external_value(PARAM_TEXT, 'class ID parameter'),
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
     * @param string $classid Class ID.
     * @param string $scheduleid Schedule ID.
     * @return object The student's report.
     * @throws invalid_parameter_exception
     */
    public static function get_a_report(string $studentid, string $classid, string $scheduleid)
    {

        // Validate parameters passed from web service.
        $params = self::validate_parameters(self::get_a_report_parameters(), array(
            'studentid' => $studentid,
            'classid' => $classid,
            'scheduleid' => $scheduleid));
        global $DB;

        $sql = "SELECT r.*
                  FROM {report} r
             WHERE (r.studentid = :studentid AND r.classid = :classid AND r.scheduleid = :scheduleid)
              ORDER BY r.id ASC";

        return $DB->get_records_sql($sql,array('studentid'=>$studentid,'classid'=>$classid
        ,'scheduleid'=>$scheduleid));
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
                    'method' => new external_value(PARAM_INT, 'method that the student checkin', VALUE_DEFAULT, null),
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
        $sql = "SELECT r.*
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
                    'method' => new external_value(PARAM_INT, 'method that the student checkin', VALUE_DEFAULT, null),
                )
            )
        );
    }
}
