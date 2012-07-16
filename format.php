<?php // $Id: format.php

defined('MOODLE_INTERNAL') || die();

require_once('./lib.php');

$context            = get_context_instance(CONTEXT_COURSE, $course->id);
$editing            = $PAGE->user_is_editing();
$has_cap_update     = has_capability('moodle/course:update', $context);
$has_cap_vishidsect = has_capability('moodle/course:viewhiddensections', $context);

if ($editing) {     
    $str_edit_summary   = get_string('editsummary');
    $url_pic_edit   = $OUTPUT->pix_url('t/edit');
}

$summary_status = _get_summary_visibility($course->id);

echo html_writer::script('',
    $CFG->wwwroot.'/course/format/grid/lib.js');

/// Layout the whole page as three big columns (was, id="layout-table")
?>
<div class="topicscss-format">
    <div id="middle-column" class=""><?php
            echo $OUTPUT->skip_link_target();

            //start at 1 to skip the summary block
            //or include the summary block if it's in the grid display
            $topic0_at_top = $summary_status->showsummary == 1;
            if($topic0_at_top) {
                $topic0_at_top = _make_block_topic0(0, true);
            } ?>
        <div id="iconContainer">
            <ul class="icons"><?php
                /// Print all of the icons. 
                _make_block_icon_topics($topic0_at_top); ?>
            </ul>
        </div>
        <div id="shadebox">
            <div id="shadebox_overlay" style="display:none;" onclick="toggle_shadebox();"></div>
            <div id="shadebox_content">
                <img id="shadebox_close" style="display: none;" src="<?php echo $OUTPUT->pix_url('close', 'format_grid'); ?>" onclick="toggle_shadebox();" />
                <ul class='topics'><?php
                    /// If currently moving a file then show the current clipboard
                    _make_block_show_clipboard_if_file_moving();

                    /// Print Section 0 with general activities
                    if (!$topic0_at_top) {
                        _make_block_topic0(0, false);
                    }

                    /// Now all the normal modules by topic
                    /// Everything below uses "section" terminology - each "section" is a topic/module.
                    _make_block_topics(); ?>
                </ul>
            </div>
        </div>
        <div class="clearer">&nbsp;</div>
    </div>
    <?php
        if (!$editing || !$has_cap_update) {
            echo html_writer::script('hide_sections();');
        }
    ?>
</div>
<?php
// Include course format js module
$PAGE->requires->js('/course/format/grid/format.js');
?>
