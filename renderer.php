<?php

/**
 * Grid Information
 *
 * @package    course/format
 * @subpackage grid
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Dan Poltawski.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/renderer.php');
require_once($CFG->dirroot . '/course/format/grid/lib.php');

class format_grid_renderer extends format_section_renderer_base {

    private $topic0_at_top;

    /**
     * Generate the starting container html for a list of sections
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', array('class' => 'topics'));
    }

    /**
     * Generate the closing container html for a list of sections
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page
     * @return string the page title
     */
    protected function page_title() {
        return get_string('sectionname', 'format_grid');
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param array $mods used for print_section()
     * @param array $modnames used for print_section()
     * @param array $modnamesused used for print_section()
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {
        global $PAGE;

        $summary_status = _get_summary_visibility($course->id);
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $editing = $PAGE->user_is_editing();
        $has_cap_update = has_capability('moodle/course:update', $context);
        $has_cap_vishidsect = has_capability('moodle/course:viewhiddensections', $context);

        if ($editing) {
            $str_edit_summary = get_string('editsummary');
            $url_pic_edit = $this->output->pix_url('t/edit');
        }
        echo html_writer::start_tag('div', array('class' => 'topicscss-format'));
        echo html_writer::start_tag('div', array('id' => 'middle-column'));
        echo $this->output->skip_link_target();

        //start at 1 to skip the summary block
        //or include the summary block if it's in the grid display
        $this->topic0_at_top = $summary_status->showsummary == 1;
        if ($this->topic0_at_top) {
            $this->topic0_at_top = $this->make_block_topic0(0, $course, $sections, $mods, $modnames, $modnamesused, $editing, $has_cap_update, $url_pic_edit, $str_edit_summary, false);
        }
        echo html_writer::start_tag('div', array('id' => 'iconContainer'));
        echo html_writer::start_tag('ul', array('class' => 'icons'));
        /// Print all of the icons. 
        $this->make_block_icon_topics($context, $sections, $course, $editing, $has_cap_update, $has_cap_vishidsect, $url_pic_edit);
        echo html_writer::end_tag('ul');
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', array('id' => 'shadebox'));
        echo html_writer::tag('div', '', array('id' => 'shadebox_overlay', 'style' => 'display:none;', 'onclick' => 'toggle_shadebox();'));
        echo html_writer::start_tag('div', array('id' => 'shadebox_content'));

        echo html_writer::tag('img', '', array('id' => 'shadebox_close', 'style' => 'display:none;', 'src' => $this->output->pix_url('close', 'format_grid'), 'onclick' => 'toggle_shadebox();'));
        echo $this->start_section_list();
        /// If currently moving a file then show the current clipboard
        $this->make_block_show_clipboard_if_file_moving($course);

        /// Print Section 0 with general activities
        if (!$this->topic0_at_top) {
            $this->make_block_topic0(0, $course, $sections, $mods, $modnames, $modnamesused, $editing, $has_cap_update, $url_pic_edit, $str_edit_summary, false);
        }

        /// Now all the normal modules by topic
        /// Everything below uses "section" terminology - each "section" is a topic/module.
        $this->make_block_topics($course, $sections, $editing, $has_cap_update, $has_cap_vishidsect, $mods, $modnames, $modnamesused, $str_edit_summary, $url_pic_edit, false);

        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');
        echo html_writer::tag('div', '&nbsp;', array('class' => 'clearer'));
        echo html_writer::end_tag('div');
        if (!$editing || !$has_cap_update) {
            echo html_writer::script('hide_sections();');
        }
        echo html_writer::end_tag('div');
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage) {
        global $PAGE;

        $o = '';
        $sectionstyle = '';

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            } else if ($this->is_section_current($section, $course)) {
                $sectionstyle = ' current';
            }
        }
        if ($section->section == 0) {
            $sectionstyle.= ($this->topic0_at_top ? '' : ' grid_section');
        } else {
            $sectionstyle .= ' grid_section';
        }
        $o.= html_writer::start_tag('li', array('id' => 'section-' . $section->section,
                    'class' => 'section main clearfix' . $sectionstyle));

        $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        if (!$onsectionpage) {
            $o.= $this->output->heading($this->section_title($section, $course), 3, 'sectionname');
        }

        $o.= html_writer::start_tag('div', array('class' => 'summary'));
        $o.= $this->format_summary_text($section);

        $context = context_course::instance($course->id);
        if ($PAGE->user_is_editing() && has_capability('moodle/course:update', $context)) {
            $url = new moodle_url('/course/editsection.php', array('id' => $section->id));

            if ($onsectionpage) {
                $url->param('sectionreturn', 1);
            }

            $o.= html_writer::link($url, html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/edit'), 'class' => 'iconsmall edit')), array('title' => get_string('editsummary')));
        }
        $o.= html_writer::end_tag('div');

        $o .= $this->section_availability_message($section);

        return $o;
    }

    // Grid format specific code
    private function make_block_topic0($section, $course, $sections, $mods, $modnames, $modnamesused, $editing, $has_cap_update, $url_pic_edit, $str_edit_summary, $onsectionpage) {

        if (!is_numeric($section) || !array_key_exists($section, $sections))
            return false;

        $thissection = $sections[$section];
        if (!is_object($thissection))
            return false;

        if ($this->topic0_at_top) {
            echo html_writer::start_tag('ul', array('class' => 'topicscss'));
        }
        echo html_writer::start_tag('li', array(
            'id' => 'section-0',
            'class' => 'section main' . ($this->topic0_at_top ? '' : ' grid_section')));

        echo html_writer::tag('div', '&nbsp;', array('class' => 'right side'));

        echo html_writer::start_tag('div', array('class' => 'content'));
        echo html_writer::start_tag('div', array('class' => 'summary'));

        echo $this->format_summary_text($thissection);

        if ($editing && $has_cap_update) {
            $link = html_writer::link(
                            new moodle_url('editsection.php', array('id' => $thissection->id)), html_writer::empty_tag('img', array(
                                'src' => $url_pic_edit,
                                'alt' => $str_edit_summary,
                                'class' => 'icon edit')), array('title' => $str_edit_summary));
            echo $this->topic0_at_top ? html_writer::tag('p', $link) : $link;
        }
        echo html_writer::end_tag('div');

        //$this->section_header($thissection, $course, $onsectionpage);

        print_section($course, $thissection, $mods, $modnamesused);

        if ($editing) {
            print_section_add_menus($course, $section, $modnames);

            if ($this->topic0_at_top) {
                $str_hide_summary = get_string('hide_summary', 'format_grid');
                $str_hide_summary_alt = get_string('hide_summary_alt', 'format_grid');

                echo html_writer::link(
                        _grid_moodle_url('mod_summary.php', array(
                            'sesskey' => sesskey(),
                            'course' => $course->id,
                            'showsummary' => 0)), html_writer::empty_tag('img', array(
                            'src' => $this->output->pix_url('into_grid', 'format_grid'),
                            'alt' => $str_hide_summary_alt)) . '&nbsp;' . $str_hide_summary, array('title' => $str_hide_summary_alt));
            }
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('li');

        if ($this->topic0_at_top) {
            echo html_writer::end_tag('ul');
        }
        return true;
    }

    private function make_block_icon_topics($context, $sections, $course, $editing, $has_cap_update, $has_cap_vishidsect, $url_pic_edit) {
        global $USER;

        $url_pic_new_activity = $this->output->pix_url('new_activity', 'format_grid');

        if ($editing) {
            $str_edit_image = get_string('editimage', 'format_grid');
            $str_edit_image_alt = get_string('editimage_alt', 'format_grid');
        }

        //start at 1 to skip the summary block
        //or include the summary block if it's in the grid display
        for ($section = $this->topic0_at_top ? 1 : 0; $section <= $course->numsections; $section++) {

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

                echo html_writer::tag('p', $this->section_title($thissection, $course), array('class' => 'icon_content'));

                if ($this->new_activity($thissection, $course)) {
                    echo html_writer::empty_tag('img', array(
                        'class' => 'new_activity',
                        'src' => $url_pic_new_activity,
                        'alt' => ''));
                }

                echo html_writer::start_tag('div', array('class' => 'image_holder'));

                $sectionicon = _grid_get_icon(
                        $course->id, $thissection->id);

                if (is_object($sectionicon) && !empty($sectionicon->imagepath)) {
                    echo html_writer::empty_tag('img', array(
                        'src' => moodle_url::make_pluginfile_url(
                                $context->id, 'course', 'section', $thissection->id, '/', $sectionicon->imagepath), 'alt' => ''));
                } else if ($section == 0) {
                    echo html_writer::empty_tag('img', array(
                        'src' => $this->output->pix_url('info', 'format_grid'),
                        'alt' => ''));
                }

                echo html_writer::end_tag('div');
                echo html_writer::end_tag('a');

                if ($editing && $has_cap_update) {
                    echo html_writer::link(
                            _grid_moodle_url('editimage.php', array(
                                'sectionid' => $thissection->id,
                                'contextid' => $context->id,
                                'userid' => $USER->id)), html_writer::empty_tag('img', array(
                                'src' => $url_pic_edit,
                                'alt' => $str_edit_image_alt)) . '&nbsp;' . $str_edit_image, array('title' => $str_edit_image_alt));

                    if ($section == 0) {
                        $str_display_summary = get_string('display_summary', 'format_grid');
                        $str_display_summary_alt = get_string('display_summary_alt', 'format_grid');

                        echo html_writer::empty_tag('br') . html_writer::link(
                                _grid_moodle_url('mod_summary.php', array(
                                    'sesskey' => sesskey(),
                                    'course' => $course->id,
                                    'showsummary' => 1)), html_writer::empty_tag('img', array(
                                    'src' => $this->output->pix_url('out_of_grid', 'format_grid'),
                                    'alt' => $str_display_summary_alt)) . '&nbsp;' . $str_display_summary, array('title' => $str_display_summary_alt));
                    }
                }
                echo html_writer::end_tag('li');
            }
        }
    }

/// If currently moving a file then show the current clipboard
    private function make_block_show_clipboard_if_file_moving($course) {
        global $USER;

        if (is_object($course) && ismoving($course->id)) {
            $str_cancel = get_string('cancel');

            $stractivityclipboard = clean_param(format_string(
                            get_string('activityclipboard', '', $USER->activitycopyname)), PARAM_NOTAGS);
            $stractivityclipboard .= '&nbsp;&nbsp;('
                    . html_writer::link(new moodle_url('/mod.php', array(
                                'cancelcopy' => 'true',
                                'sesskey' => sesskey())), $str_cancel);

            echo html_writer::tag('li', $stractivityclipboard, array('class' => 'clipboard'));
        }
    }

    private function make_block_topics($course, $sections, $editing, $has_cap_update, $has_cap_vishidsect, $mods, $modnames, $modnamesused, $str_edit_summary, $url_pic_edit, $onsectionpage) {

        // Section 0 already displayed, so not an orphan for code below.
        unset($sections[0]);
        for ($section = 1; $section <= $course->numsections; $section++) {
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
            }

            if (!$has_cap_vishidsect && !$thissection->visible && $course->hiddensections) {
                unset($sections[$section]);
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
            $rightcontent = $this->section_right_content($thissection, $course);
            echo html_writer::tag('div', $rightcontent, array('class' => 'right side'));

            echo html_writer::start_tag('div', array('class' => 'content'));
            if ($has_cap_vishidsect || $thissection->visible) {
                //if visible
                if (!empty($thissection->name)) {
                    echo format_text($this->output->heading(
                                    $thissection->name, 3, 'sectionname'), FORMAT_HTML);
                }

                echo html_writer::start_tag('div', array('class' => 'summary'));

                echo $this->format_summary_text($thissection);

                if ($editing && $has_cap_update) {
                    echo html_writer::link(
                            new moodle_url('editsection.php', array('id' => $thissection->id)), html_writer::empty_tag('img', array(
                                'src' => $url_pic_edit,
                                'alt' => $str_edit_summary,
                                'class' => 'icon edit')), array('title' => $str_edit_summary));
                }
                echo html_writer::end_tag('div');

                print_section($course, $thissection, $mods, $modnamesused);

                if ($editing) {
                    print_section_add_menus($course, $section, $modnames);
                }
            } else {
                echo html_writer::tag('h2', $this->get_title($thissection));
                echo html_writer::tag('p', get_string('hidden_topic', 'format_grid'));
            }

            /* $this->section_header($thissection, $course, $onsectionpage);
              print_section($course, $thissection, $mods, $modnamesused);

              if ($editing) {
              print_section_add_menus($course, $section, $modnames);
              } */

            echo html_writer::end_tag('div');
            echo html_writer::end_tag('li');

            unset($sections[$section]);
        }

        if ($editing and $has_cap_update) {
            // Print stealth sections if present.
            $modinfo = get_fast_modinfo($course);
            foreach ($sections as $section => $thissection) {
                if (empty($modinfo->sections[$section])) {
                    continue;
                }
                echo $this->stealth_section_header($section);
                print_section($course, $thissection, $mods, $modnamesused);
                echo $this->stealth_section_footer();
            }

            echo $this->end_section_list();

            echo html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            $straddsection = get_string('increasesections', 'moodle');
            $url = new moodle_url('/course/changenumsections.php',
                            array('courseid' => $course->id,
                                'increase' => true,
                                'sesskey' => sesskey()));
            $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
            echo html_writer::link($url, $icon . get_accesshide($straddsection), array('class' => 'increase-sections'));

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                                array('courseid' => $course->id,
                                    'increase' => false,
                                    'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                echo html_writer::link($url, $icon . get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            echo html_writer::end_tag('div');
        } else {
            echo $this->end_section_list();
        }
    }

//Attempts to return a 40 character title for the section icon.
//If section names are set, they are used. Otherwise it scans 
//the summary for what looks like the first line.
    private function get_title($section) {
        $title = is_object($section) && isset($section->name) &&
                is_string($section->name) ? trim($section->name) : '';

        if (!empty($title)) {
            // Apply filters and clean tags
            $title = trim(format_string($section->name, true));
        }

        if (empty($title)) {
            $title = trim(format_text($section->summary));

            // Finds first header content. If it doesn't found,
            // trying to find first paragraph. 
            foreach (array('h[1-6]', 'p') as $tag) {
                if (preg_match('#<(' . $tag . ')\b[^>]*>(?P<text>.*?)</\1>#si', $title, $m)) {
                    if (!_is_empty_text($m['text'])) {
                        $title = $m['text'];
                        break;
                    }
                }
            }
            $title = trim(clean_param($title, PARAM_NOTAGS));
        }

        if (strlen($title) > 40) {
            $title = $this->text_limit($title, 40);
        }

        return $title;
    }

// Cutes long texts up to certain length without breaking words
    private function text_limit($text, $length, $replacer = '...') {
        if (strlen($text) > $length) {
            $text = wordwrap($text, $length, "\n", true);
            $pos = strpos($text, "\n");
            if ($pos === false)
                $pos = $length;
            $text = trim(substr($text, 0, $pos)) . $replacer;
        }
        return $text;
    }

//Checks whether there has been new activity in section $section
    private function new_activity($section, $course) {
        global $CFG, $USER, $DB;

        if (isset($USER->lastcourseaccess[$course->id])) {
            $course->lastaccess = $USER->lastcourseaccess[$course->id];
        } else {
            $course->lastaccess = 0;
        }

        $sql = "SELECT id, url FROM {$CFG->prefix}log " .
                'WHERE course = :courseid AND time > :lastaccess AND action = :edit';

        $params = array(
            'courseid' => $course->id,
            'lastaccess' => $course->lastaccess,
            'edit' => 'editsection');

        $activity = $DB->get_records_sql($sql, $params);
        foreach ($activity as $url_obj) {
            $list = explode('=', $url_obj->url);

            if ($section->id == $list[1])
                return true;
        }
        return false;
    }

}
