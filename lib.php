<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @throws require_login_exception
 * @throws moodle_exception
 * @throws coding_exception
 */
function local_webservices_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()): bool
{

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'image_left' && $filearea !== 'image_right' && $filearea !== 'image_front' && $filearea !== 'image_feedback'
        && $filearea !== 'checkin') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    //require_login($course, true, $cm);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
//    if (!has_capability('mod/MYPLUGIN:view', $context)) {
//        return false;
//    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_webservices', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false; // The file does not exist.
    }


    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 0, 0, $forcedownload, $options);
}