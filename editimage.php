<?php

/* Imports */
require_once("../../../config.php");
require_once($CFG->dirroot.'/repository/lib.php');
require_once('./editimage_form.php');

/* Script settings */
define('GRID_ITEM_IMAGE_WIDTH', 210);
define('GRID_ITEM_IMAGE_HEIGHT', 140);

/* Page parameters */
$contextid = required_param('contextid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
$id = optional_param('id', null, PARAM_INT);

/* No idea, copied this from an example. Sets form data options but I don't know what they all do exactly */
$formdata = new stdClass();
$formdata->userid = required_param('userid', PARAM_INT);
$formdata->offset = optional_param('offset', null, PARAM_INT);
$formdata->forcerefresh = optional_param('forcerefresh', null, PARAM_INT);
$formdata->mode = optional_param('mode', null, PARAM_ALPHA);

$url = new moodle_url('/course/format/grid/editimage.php', array(
    'contextid' => $contextid,
    'id' => $id,
    'offset' => $formdata->offset,
    'forcerefresh' => $formdata->forcerefresh,
    'userid' => $formdata->userid,
    'mode' => $formdata->mode));

/* No exactly sure what this stuff does, but it seems fairly straightforward */
list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, true, $cm);
if (isguestuser()) {
    die();
}

$PAGE->set_url($url);
$PAGE->set_context($context);

/* Functional part. Create the form and display it, handle results, etc */
$options = array(
    'subdirs' => 0,
    'maxfiles' => 1,
    'accepted_types' => array('web_image'),
    'return_types' => FILE_INTERNAL);

$mform = new image_form(null, array(
    'contextid' => $contextid,
    'userid' => $formdata->userid,
    'sectionid' => $sectionid,
    'options' => $options));

if ($mform->is_cancelled()) {
    //Someone has hit the 'cancel' button
    redirect(new moodle_url($CFG->wwwroot . '/course/view.php?id='.$course->id));
} else if ($formdata = $mform->get_data()) { //Form has been submitted    
    /* Delete old images associated with this course section id */
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'course', 'section', $sectionid);
    
    if ($newfilename = $mform->get_new_filename('icon_file')) {
        /* Resize the new image and save it */

        $created = time();
        $storedfile_record = array(
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'section',
            'itemid' => $sectionid,
            'filepath' => '/',
            'filename' => $newfilename,
            'timecreated' => $created,
            'timemodified' => $created);

        $temp_file = $mform->save_stored_file(
            'icon_file',
            $storedfile_record['contextid'],
            $storedfile_record['component'],
            $storedfile_record['filearea'],
            $storedfile_record['itemid'],
            $storedfile_record['filepath'],
            'temp.' . $storedfile_record['filename'], true);

        try {
            $fs->convert_image($storedfile_record, $temp_file,
                GRID_ITEM_IMAGE_WIDTH,
                GRID_ITEM_IMAGE_HEIGHT, true);

            $temp_file->delete();
            unset($temp_file);

            $DB->set_field('format_grid_icon', 'imagepath',
                $newfilename, array('sectionid' => $sectionid));
        } catch (Exception $e) {
            if (isset($temp_file)) {
                $temp_file->delete();
                unset($temp_file);
            }
            debugging($e->getMessage());
        }
        redirect($CFG->wwwroot . "/course/view.php?id=".$course->id);
    } 
}

/* Draw the form */
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();