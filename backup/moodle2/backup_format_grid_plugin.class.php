<?php

/**
 * Grid Information
 *
 * @package    course/format
 * @subpackage Grid
 * @version    See the value of '$plugin->version' in below.
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
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

/**
 * Provides the information to backup grid course format
 */
class backup_format_grid_plugin extends backup_format_plugin {
 /**
     * Returns the format information to attach to course element
     */
    protected function define_course_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill
        $plugin = $this->get_plugin_element(null, '/course/format', 'grid');

        // Create one standard named plugin element (the visible container)
        $pluginwrapper = new backup_nested_element($this->get_recommended_name(), null, array('showsummary'));  // courseid not required as populated on restore.

        // connect the visible container ASAP
        $plugin->add_child($pluginwrapper);

        // set source to populate the data
        $pluginwrapper->set_source_table('format_grid_summary', array(
            'courseid' => backup::VAR_PARENTID));

        // don't need to annotate ids nor files

        return $plugin;
    }

    /**
     * Returns the format information to attach to section element
     */
    protected function define_section_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill
        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'grid');

        // Create one standard named plugin element (the visible container)
        $pluginwrapper = new backup_nested_element($this->get_recommended_name(), null, array('imagepath'));   // sectionid and courseid not required as populated on restore.

        // connect the visible container ASAP
        $plugin->add_child($pluginwrapper);

        // set source to populate the data
        $pluginwrapper->set_source_table('format_grid_icon', array(
            'sectionid' => backup::VAR_SECTIONID));

        // don't need to annotate ids nor files

        return $plugin;
    }
}
