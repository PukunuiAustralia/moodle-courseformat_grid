<?php // $Id: format.php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/completionlib.php');

// Horrible backwards compatible parameter aliasing..
if ($topic = optional_param('topics', 0, PARAM_INT)) {
    $url = $PAGE->url;
    $url->param('section', $topic);
    debugging('Outdated topic param passed to course/view.php', DEBUG_DEVELOPER);
    redirect($url);
}
// End backwards-compatible aliasing..

$renderer = $PAGE->get_renderer('format_grid');
if (!empty($displaysection) && $course->coursedisplay == COURSE_DISPLAY_MULTIPAGE) {
    $renderer->print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection);
} else {
    echo html_writer::script('',$CFG->wwwroot.'/course/format/grid/lib.js');
    $renderer->print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused);
}
// Include course format js module
$PAGE->requires->js('/course/format/grid/format.js');
