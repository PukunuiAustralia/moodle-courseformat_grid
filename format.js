// Javascript functions for course format

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get section list for this format
 *
 * @param {YUI} Y YUI3 instance
 * @return {string} section list selector
 */
M.course.format.get_section_selector = function(Y) {
    return 'li.section';
}
