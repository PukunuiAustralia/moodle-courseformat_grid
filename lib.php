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
 * Used to display the course structure for a course where format=topic
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = weeks.
 *
 * @param array $path An array of keys to the course node in the navigation
 * @param stdClass $modinfo The mod info object for the current course
 * @return bool Returns true
 */
function callback_grid_load_content(&$navigation, $course, $coursenode) {
    return $navigation->load_generic_course_sections(
        $course, $coursenode, 'grid');
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

/**
 * The GET argument variable that is used to identify the section being
 * viewed by the user (if there is one)
 *
 * @return string
 */
function callback_grid_request_key() {
    return 'topic';
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
 * Deletes the settings entry for the given course upon course deletion.
 */
function format_grid_delete_course($courseid) {
    global $DB;

    $DB->delete_records("format_grid_icon", array("courseid" => $courseid));
    $DB->delete_records("format_grid_summary", array("courseid" => $courseid));
}

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

//Checks whether there has been new activity in section $section
function _new_activity($section, $course) {
    global $CFG, $USER, $DB;

    if (isset($USER->lastcourseaccess[$course->id])) {
        $course->lastaccess = $USER->lastcourseaccess[$course->id];
    } else {
        $course->lastaccess = 0;
    }

    $sql = "SELECT id, url FROM {$CFG->prefix}log ".
        'WHERE course = :courseid AND time > :lastaccess AND action = :edit';

    $params = array(
        'courseid' => $course->id,
        'lastaccess'=> $course->lastaccess,
        'edit'=>'editsection');

    $activity = $DB->get_records_sql($sql, $params);
    foreach($activity as $url_obj) {
        $list = explode('=', $url_obj->url);

        if($section->id == $list[1])
            return true;
    }
    return false;
}

//Attempts to return a 40 character title for the section icon.
//If section names are set, they are used. Otherwise it scans 
//the summary for what looks like the first line.
function _get_title($section) {
    $title = is_object($section) && isset($section->name) &&
        is_string($section->name)?trim($section->name):'';

    if (!empty($title)) {
        // Apply filters and clean tags
        $title = trim(format_string($section->name, true));
    }

    if (empty($title)) {
        $title = trim(format_text($section->summary));

        // Finds first header content. If it doesn't found,
        // trying to find first paragraph. 
        foreach(array('h[1-6]', 'p') as $tag) {
            if (preg_match('#<('.$tag.')\b[^>]*>(?P<text>.*?)</\1>#si', $title, $m)) {
                if (!_is_empty_text($m['text'])) {
                    $title = $m['text'];
                    break;
                }
            }
        }
        $title = trim(clean_param($title, PARAM_NOTAGS));
    }

    if (strlen($title) > 40) {
        $title = _text_limit($title, 40);
    }

    return $title;
}

// Cutes long texts up to certain length without breaking words
function _text_limit($text, $length, $replacer = '...') {
    if(strlen($text) > $length) {
        $text = wordwrap($text, $length, "\n", true);
        $pos = strpos($text, "\n");
        if ($pos === false)
            $pos = $length;
        $text = trim(substr($text, 0, $pos)) . $replacer;
    }
    return $text; 
}

function _make_block_topic0($section, $top) {
    global $OUTPUT, $context,
        $sections, $course,
        $editing, $has_cap_update,
        $mods, $modnames, $modnamesused,
        $url_pic_edit, $str_edit_summary;

    if (!is_numeric($section) || !array_key_exists($section, $sections))
        return false;

    $thissection = $sections[$section];
    if (!is_object($thissection))
        return false;

    $summaryformatoptions = new stdClass();
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;

    if ($top) {
        echo html_writer::start_tag('ul', array('class'=>'topicscss'));
    }
    echo html_writer::start_tag('li', array(
        'id' =>'section-0',
        'class'=>'section main' . ($top ? '' :' grid_section')));

    echo html_writer::tag('div', '&nbsp;', array('class'=>'right side'));

    echo html_writer::start_tag('div', array('class'=>'content'));
    echo html_writer::start_tag('div', array('class'=>'summary'));

    if ($top) {
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
		$summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
		echo format_text($summarytext, FORMAT_HTML, $summaryformatoptions);
    } else {
        $summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);

        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
		$summarytext = file_rewrite_pluginfile_urls($thissection->summary, 'pluginfile.php', $coursecontext->id, 'course', 'section', $thissection->id);
		echo format_text($summarytext, FORMAT_HTML, $summaryformatoptions);        
    }


    if ($editing && $has_cap_update) {
        $link = html_writer::link(
            new moodle_url('editsection.php', array('id' => $thissection->id)),
            html_writer::empty_tag('img', array(
                'src'   => $url_pic_edit,
                'alt'   => $str_edit_summary,
                'class' => 'icon edit')),
            array('title' => $str_edit_summary));
        echo $top ? html_writer::tag('p', $link) : $link;
    }
    echo html_writer::end_tag('div');

    print_section($course, $thissection, $mods, $modnamesused);

    if ($editing) {
        print_section_add_menus($course, $section, $modnames);
        
        if ($top) {
            $str_hide_summary = get_string('hide_summary', 'format_grid');
            $str_hide_summary_alt = get_string('hide_summary_alt', 'format_grid');
            
            echo html_writer::link(
                _grid_moodle_url('mod_summary.php', array(
                    'sesskey' => sesskey(),
                    'course' => $course->id,
                    'showsummary' => 0)),
                html_writer::empty_tag('img', array(
                    'src' => $OUTPUT->pix_url('into_grid', 'format_grid'),
                    'alt' => $str_hide_summary_alt)) . '&nbsp;' . $str_hide_summary,
                array('title' => $str_hide_summary_alt));
        }
    }
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('li');
    
    if ($top) {
        echo html_writer::end_tag('ul');
    }
    return true;
}

function _make_block_icon_topics($without_topic0) {
    global $OUTPUT, $USER, $DB, $context,
        $sections, $course, $editing,
        $has_cap_update, $has_cap_vishidsect, $url_pic_edit;

    $str_topic = get_string('topic', 'format_grid');    
    $url_pic_new_activity = $OUTPUT->pix_url('new_activity', 'format_grid');

    if ($editing) {
        $str_edit_image = get_string('editimage', 'format_grid');
        $str_edit_image_alt = get_string('editimage_alt', 'format_grid');
    }

    //start at 1 to skip the summary block
    //or include the summary block if it's in the grid display
    for($section = $without_topic0 ? 1 : 0;
        $section <= $course->numsections; $section++) {

        if (!empty($sections[$section])) {
            $thissection = $sections[$section];
        } else {
                // This will create a course section if it doesn't exist..
                $thissection = get_course_section($section, $course->id);

                // The returned section is only a bare database object rather than
                // a section_info object - we will need at least the uservisible
                // field in it.
                $thissection->uservisible = true;
                $thissection->availableinfo = null;
                $thissection->showavailability = 0;
            $sections[$section] = $thissection;
        }

        //check if course is visible to user, if so show course
        if ($has_cap_vishidsect || $thissection->visible || !$course->hiddensections) {
            $str_title = _get_title($thissection);
            if($section == 0 && _is_empty_text($str_title))  {
                $str_title = get_string('general_information', 'format_grid');
            }

            //Get the module icon
            if ($editing && $has_cap_update) {
                $onclickevent = "select_topic_edit(event, {$thissection->section})";
            } else {
                $onclickevent = "select_topic(event, {$thissection->section})";
            }

            echo html_writer::start_tag('li');
            echo html_writer::start_tag('a', array(
                'href' => '#section-' . $thissection->section,
                'class' => 'icon_link',
                'onclick' => $onclickevent));

            echo html_writer::tag('p', $str_title, array('class' => 'icon_content'));

            if(_new_activity($thissection, $course)) {
                echo html_writer::empty_tag('img', array(
                    'class' => 'new_activity',
                    'src' => $url_pic_new_activity,
                    'alt' => ''));
            }

            echo html_writer::start_tag('div', array('class'=>'image_holder'));

            $sectionicon = _grid_get_icon(
                $course->id, $thissection->id);

            if(is_object($sectionicon) && !empty($sectionicon->imagepath)) {
                echo html_writer::empty_tag('img', array(
                    'src' => moodle_url::make_pluginfile_url(
                        $context->id, 'course', 'section', $thissection->id,
                        '/', $sectionicon->imagepath), 'alt' => ''));                
            } else if($section == 0) {
                echo html_writer::empty_tag('img', array(
                    'src' => $OUTPUT->pix_url('info', 'format_grid'),
                    'alt' => ''));
            }

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('a');

            if ($editing && $has_cap_update) {
                echo html_writer::link(
                    _grid_moodle_url('editimage.php', array(
                        'sectionid' => $thissection->id,
                        'contextid' => $context->id,
                        'userid' => $USER->id)),
                    html_writer::empty_tag('img', array(
                        'src' => $url_pic_edit,
                        'alt' => $str_edit_image_alt)) . '&nbsp;' . $str_edit_image,
                    array('title' => $str_edit_image_alt));

                if($section == 0) {
                    $str_display_summary = get_string('display_summary', 'format_grid');
                    $str_display_summary_alt = get_string('display_summary_alt', 'format_grid');

                    echo html_writer::empty_tag('br') . html_writer::link(
                        _grid_moodle_url('mod_summary.php', array(
                            'sesskey' => sesskey(),
                            'course' => $course->id, 
                            'showsummary' => 1)),
                        html_writer::empty_tag('img', array(
                            'src' => $OUTPUT->pix_url('out_of_grid', 'format_grid'),
                            'alt' => $str_display_summary_alt)) . '&nbsp;' . $str_display_summary,
                        array('title' => $str_display_summary_alt));
                }
            }
            echo html_writer::end_tag('li');
        }
    }
}

/// If currently moving a file then show the current clipboard
function _make_block_show_clipboard_if_file_moving() {
    global $USER, $course;

    if (is_object($course) && ismoving($course->id)) {
        $str_cancel= get_string('cancel');
        
        $str_activity_clipboard = clean_param(format_string(
            get_string('activityclipboard', '', $USER->activitycopyname)),
            PARAM_NOTAGS);
        $stractivityclipboard .= '&nbsp;&nbsp;(' 
            .html_writer::link(new moodle_url('/mod.php', array(
                'cancelcopy' => 'true',
                'sesskey' => sesskey())), $str_cancel);

        echo html_writer::tag('li', $stractivityclipboard,
            array('class' => 'clipboard'));
    }
}

function _section_edit_controls($course, $section, $onsectionpage = false) {
        global $PAGE, $OUTPUT;

        if (!$PAGE->user_is_editing()) {
            return array();
        }

        if (!has_capability('moodle/course:update', context_course::instance($course->id))) {
            return array();
        }

        if ($onsectionpage) {
            $baseurl = course_get_url($course, $section->section);
        } else {
            $baseurl = course_get_url($course);
        }
        $baseurl->param('sesskey', sesskey());

        $controls = array();

        $url = clone($baseurl);
        if ($section->visible) { // Show the hide/show eye.
            $strhidefromothers = get_string('hidefromothers', 'format_'.$course->format);
            $url->param('hide', $section->section);
            $controls[] = html_writer::link($url,
                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/hide'),
                'class' => 'icon hide', 'alt' => $strhidefromothers)),
                array('title' => $strhidefromothers, 'class' => 'editing_showhide'));
        } else {
            $strshowfromothers = get_string('showfromothers', 'format_'.$course->format);
            $url->param('show',  $section->section);
            $controls[] = html_writer::link($url,
                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/show'),
                'class' => 'icon hide', 'alt' => $strshowfromothers)),
                array('title' => $strshowfromothers, 'class' => 'editing_showhide'));
        }

        if (!$onsectionpage) {
            $url = clone($baseurl);
            if ($section->section > 1) { // Add a arrow to move section up.
                $url->param('section', $section->section);
                $url->param('move', -1);
                $strmoveup = get_string('moveup');

                $controls[] = html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/up'),
                    'class' => 'icon up', 'alt' => $strmoveup)),
                    array('title' => $strmoveup, 'class' => 'moveup'));
            }

            $url = clone($baseurl);
            if ($section->section < $course->numsections) { // Add a arrow to move section down.
                $url->param('section', $section->section);
                $url->param('move', 1);
                $strmovedown =  get_string('movedown');

                $controls[] = html_writer::link($url,
                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/down'),
                    'class' => 'icon down', 'alt' => $strmovedown)),
                    array('title' => $strmovedown, 'class' => 'movedown'));
            }
        }

        return $controls;
    }

function _section_right_content($section, $course, $onsectionpage = false) {
        $o = '&nbsp;';

        if ($section->section != 0) {
            $controls = _section_edit_controls($course, $section, $onsectionpage);
            if (!empty($controls)) {
                $o = implode('<br />', $controls);
            }
        }

        return $o;
    }

function _make_block_topics() {
    global $OUTPUT,
        $course, $sections, $editing,
        $has_cap_update, $has_cap_vishidsect,
        $mods, $modnames, $modnamesused,
        $str_edit_summary, $url_pic_edit;

    $summaryformatoptions = new stdClass();
    $summaryformatoptions->noclean = true;
    $summaryformatoptions->overflowdiv = true;

    $str_hidden_topic = get_string('hidden_topic', 'format_grid');

    if ($editing && $has_cap_update) {
        $str_move_up    = get_string('moveup');
        $str_move_down  = get_string('movedown');        
        $str_topic_hide = get_string('hidetopicfromothers');
        $str_topic_show = get_string('showtopicfromothers');

        $url_pic_move_up    = $OUTPUT->pix_url('t/up');
        $url_pic_move_down  = $OUTPUT->pix_url('t/down');
        $url_pic_topic_hide = $OUTPUT->pix_url('t/hide');
        $url_pic_topic_show = $OUTPUT->pix_url('t/show');        
    }
    for($section = 1; $section <= $course->numsections; $section++) {
        if (empty($sections[$section])) {
            //Section should have been created in the icons section above. If it's empty then its an error.
            throw new coding_exception('Error, section ' . $section . ' not found!');
        }

        $thissection = $sections[$section];

        if (!$has_cap_vishidsect && !$thissection->visible && $course->hiddensections) {
            continue;
        }

        $sectionstyle = 'section main';
        if (!$thissection->visible) {
            $sectionstyle .= ' hidden';
        }
        $sectionstyle .= ' grid_section';

        echo html_writer::start_tag('li', array(
            'id' => 'section-' . $section,
            'class' => $sectionstyle));

        // Note, 'left side' is BEFORE content.
		echo html_writer::tag('div', html_writer::tag('span', $section), array('class' => 'left side'));	
        // Note, 'right side' is BEFORE content.
		$rightcontent = _section_right_content($thissection, $course);
        echo html_writer::tag('div', $rightcontent, array('class' => 'right side'));

        echo html_writer::start_tag('div', array('class' => 'content'));
        if ($has_cap_vishidsect || $thissection->visible) {
            //if visible
            if (!empty($thissection->name)) {
                echo format_text($OUTPUT->heading(
                    $thissection->name, 3, 'sectionname'), FORMAT_HTML);
            }

            echo html_writer::start_tag('div', array('class' => 'summary'));

            echo format_text($thissection->summary,
                FORMAT_HTML, $summaryformatoptions);

            if ($editing && $has_cap_update) {
                echo html_writer::link(
                    new moodle_url('editsection.php', array('id' => $thissection->id)),
                    html_writer::empty_tag('img', array(
                        'src'   => $url_pic_edit,
                        'alt'   => $str_edit_summary,
                        'class' => 'icon edit')),
                    array('title' => $str_edit_summary));
            }
            echo html_writer::end_tag('div');

            print_section($course, $thissection, $mods, $modnamesused);

            if ($editing) {
                print_section_add_menus($course, $section, $modnames);
            }
        } else {
            $str_title = _get_title($thissection->summary);

            echo html_writer::tag('h2', $str_title);
            echo html_writer::tag('p', $str_hidden_topic);
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('li');
    }    
}

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