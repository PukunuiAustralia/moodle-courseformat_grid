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
 * This file contains general functions for the course format Topic
 *
 * @since 2.0
 * @package moodlecore
 * @copyright 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Indicates this format uses sections.
 *
 * @return bool Returns true
 */
function callback_grid_uses_sections() {
    return true;
}

/**
 * Used to display the course structure for a course where format=grid
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = weeks.
 *
 * @param array $path An array of keys to the course node in the navigation
 * @param stdClass $modinfo The mod info object for the current course
 * @return bool Returns true
 */
function callback_grid_load_content(&$navigation, $course, $coursenode) {
    return $navigation->load_generic_course_sections($course, $coursenode, 'grid');
}

/**
 * The string that is used to describe a section of the course
 * e.g. Topic, Week...
 *
 * @return string
 */
function callback_grid_definition() {
    return get_string('topic', 'format_grid');
}

function callback_grid_get_section_name($course, $section) {
    // We can't add a node without any text
    if (!empty($section->name)) {
        return format_string($section->name, true,
            array('context' => get_context_instance(CONTEXT_COURSE, $course->id)));  
    } if ($section->section == 0) {
        return get_string('topic0', 'format_grid');
    } else {
        return get_string('topic', 'format_grid').' '.$section->section;
    }
}

/**
 * Declares support for course AJAX features
 *
 * @see course_format_ajax_support()
 * @return stdClass
 */
function callback_grid_ajax_support() {
    $ajaxsupport = new stdClass();
    $ajaxsupport->capable = true;
    $ajaxsupport->testedbrowsers = array(
        'MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
    return $ajaxsupport;
}

/**
 * Returns a URL to arrive directly at a section.
 *
 * @param int $courseid The id of the course to get the link for.
 * @param int $sectionnum The section number to jump to.
 * @return moodle_url.
 */
function callback_grid_get_section_url($courseid, $sectionnum) {
    return new moodle_url('/course/view.php', array('id' => $courseid, 'topic' => $sectionnum));
}

/**
 * Callback function to do some action after section move.
 *
 * @param stdClass $course The course entry from DB.
 * @return array This will be passed in ajax respose.
 */
function callback_grid_ajax_section_move($course) {
    global $COURSE, $PAGE;

    $titles = array();
    rebuild_course_cache($course->id);
    $modinfo = get_fast_modinfo($COURSE);
    $renderer = $PAGE->get_renderer('format_grid');
    if ($renderer && ($sections = $modinfo->get_section_info_all())) {
        foreach ($sections as $number => $section) {
            $titles[$number] = $renderer->section_title($section, $course);
        }
    }
    return array('sectiontitles' => $titles, 'action' => 'move');
}

/**
 * Deletes the settings entry for the given course upon course deletion.
 */
function format_grid_delete_course($courseid) {
    global $DB;

    $DB->delete_records("format_grid_icon", array("courseid" => $courseid));
    $DB->delete_records("format_grid_summary", array("courseid" => $courseid));
}

// Grid specific functions...
function _grid_moodle_url($url, array $params = null) {
    return new moodle_url('/course/format/grid/'.$url, $params);
}

function _is_empty_text($text) {
    return empty($text) || 
        preg_match('/^(?:\s|&nbsp;)*$/si', 
            htmlentities($text, 0 /*ENT_HTML401*/, 'UTF-8', true));
}

function _grid_get_icon($courseid, $sectionid) {
    global $CFG, $DB;

    if ((!$courseid) || (!$sectionid))
        return false;

    if (!$sectionicon = $DB->get_record('format_grid_icon',
        array('sectionid' => $sectionid))) {

        $newicon                = new stdClass();
        $newicon->sectionid     = $sectionid;
        $newicon->courseid      = $courseid;

        if (!$newicon->id = $DB->insert_record('format_grid_icon', $newicon, true)) {
            throw new moodle_exception('invalidrecordid', 'format_grid', '',
                'Could not create icon. Grid format database is not ready. An admin must visit the notifications section.');
        }
        $sectionicon = false;
    }
    return $sectionicon;
}

//get section icon, if it doesnt exist create it.
function _get_summary_visibility($course) {
    global $CFG, $DB;
    if (!$summary_status = $DB->get_record('format_grid_summary', array('courseid' => $course))) {
        $new_status                = new stdClass();
        $new_status->courseid     = $course;
        $new_status->showsummary  = 1;

        if (!$new_status->id = $DB->insert_record('format_grid_summary', $new_status)) {
            throw new moodle_exception('invalidrecordid', 'format_grid', '',
                'Could not set summary status. Grid format database is not ready. An admin must visit the notifications section.');
        }
        $summary_status = $new_status;
    }
    return $summary_status;
}

// Is this needed???
function _move_section_arrow($section, $course, 
    $op_move_delta, $op_move_style, 
    $str_move_text, $url_pic_move) {

    $url = new moodle_url('/course/view.php#section-'.($section + $op_move_delta), array(
        'id'      => $course->id,
        'random'  => rand(1, 10000),
        'section' => $section,
        'move'    => $op_move_delta,
        'sesskey' => sesskey()));

    $img = html_writer::empty_tag('img', array(
                'src'   => $url_pic_move,
                'alt'   => $str_move_text,
                'class' => 'icon ' . $op_move_style));   

    return html_writer::link($url, $img, array(
        'title' => $str_move_text));       
}