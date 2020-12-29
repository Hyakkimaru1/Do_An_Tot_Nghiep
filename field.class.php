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
 * @package    profilefield_myprofilefield
 * @category   profilefield
 * @copyright  2012 Rajesh Taneja
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class profile_field_myprofilefield extends profile_field_base {


    /**
     * Adds the profile field to the moodle form class
     *
     * @param moodleform $mform instance of the moodleform class
     */
    function edit_field_add($mform) {
        // Create the form field.
        global $DB,$USER,$PAGE;
        $myvalue = $DB->get_records('user');
        $PAGE->requires->css("/user/profile/field/myprofilefield/style.css");
        $text = $mform->addElement('html', ' 
 <link rel="stylesheet" href="/user/profile/field/myprofilefield/style.css">
 <div id="videoCanvas" style="margin-bottom: 1rem">
    <p style="margin-right: 90px">Hình xác minh của bạn:  </p><div id="button-snap" class="button" onclick="handleClickOpenCam()">Mở camera của bạn</div>
    <div id="dontshow">
        <video width="500" style="display: none" id="camera" autoplay="false"></video>
        <img width="500" id="loading" src="https://quynhon.flchotelsresorts.com/wp-content/themes/flchotel/assets/loading.gif">
        <div style="margin-top: 15px">
            <div class="button-container">
                <canvas style="border:1px solid" width="100" height="100" id="photoLeft"></canvas>
                <p id="text-left" class="text">Ảnh trái</p>
                <div id="recapture-left" onclick="handleResetLeftPicture()" class="button-recapture button-recapture-disable">Chụp lại</div>
            </div>
            <div class="button-container">
                <canvas style="border:1px solid" width="100" height="100" id="photoCenter"></canvas>
                <p id="text-center" class="text">Ảnh giữa</p>
                <div id="recapture-center" onclick="handleResetCenterPicture()" class="button-recapture button-recapture-disable">Chụp lại</div>
            </div>
            <div class="button-container">
                <canvas style="border:1px solid" width="100" height="100" id="photoRight"></canvas>
                <p id="text-right" class="text">Ảnh phải</p>
                <div id="recapture-right" onclick="handleResetRightPicture()" class="button-recapture button-recapture-disable">Chụp lại</div>
            </div>
            <div style="display: flex; margin-top: 10px">
                <div id="button-submit" class="button button-disable" onclick="handleSubmitPicture()">Lưu ảnh</div>
                <div class="button" onclick="handleResetPicture()">Chụp lại ảnh khác</div>
            </div>
           
        </div>
    </div>
  <script src="https://webrtc.github.io/adapter/adapter-latest.js"></script>
  <script src="/user/profile/field/myprofilefield/face-api.js"></script>
</script>');
        $PAGE->requires->js('/user/profile/field/myprofilefield/mytest.js');
        $PAGE->requires->js_init_call('init', array($myvalue));
        $PAGE->requires->js_init_call('myuser', array($USER));
       /* $text = $mform->addElement('text', $this->inputname, format_string($this->field->name));
        $user = new stdClass();
        $user->skype = $this->data;
        $user->id = $USER->id;
        $text->setValue($this->data);
        $DB->update_record('user',$user);

        $mform->setType($this->inputname, PARAM_EMAIL); */
        if ($this->is_required() and !has_capability('moodle/user:update', context_system::instance())) {
            $mform->addRule($this->inputname, get_string('required'), 'nonzero', null, 'client');
        }
    }

    /**
     * Display the data for this field
     *
     * @return string data for custom profile field.
     */
    function display_data() {
        global $DB,$USER;
        return '<h1>'.$this->data.'</h1>';
    }

    /**
     * Sets the default data for the field in the form object
     *
     * @param moodleform $mform instance of the moodleform class
     */
    function edit_field_set_default($mform) {
        if (!empty($default)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }
    }



    /**
     * Process the data before it gets saved in database
     *
     * @param stdClass $data from the add/edit profile field form
     * @param stdClass $datarecord The object that will be used to save the record
     * @return stdClass
     */
    function edit_save_data_preprocess($data, $datarecord) {
        return $data;
    }

    /**
     * HardFreeze the field if locked.
     *
     * @param moodleform $mform instance of the moodleform class
     */
    function edit_field_set_locked($mform) {
        /* if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked() and !has_capability('moodle/user:update', get_context_instance(CONTEXT_SYSTEM))) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->data);
        } */
    }
    public function get_field_properties() {
        return array(PARAM_EMAIL, NULL_NOT_ALLOWED);
    }
}
