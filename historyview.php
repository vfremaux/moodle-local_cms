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
 * View page histories
 *
 * @package    local_cms
 * @category   local
 * @author Moodle 1.9 Janne Mikkonen
 * @author Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require($CFG->dirroot.'/local/cms/locallib.php');

$pageid   = required_param('pageid', PARAM_INT);
$courseid = required_param('course', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('coursemisconf');
}

// Security.
// You need being enrolled in that course (or being superuser).
require_login($course->id);

$context = context_course::instance($courseid);
require_capability('local/cms:editpage', $context);

// setup page.

$historystr = get_string('history', 'local_cms');

$url = new moodle_url('/local/cms/historyview.php', array('pageid' => $pageid, 'course' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_heading($historystr);
$PAGE->set_title($historystr);

// Print page.

echo $OUTPUT->header();

if ( $pagedata = $DB->get_record('local_cms_pages_history', array('id' => $pageid)) ) {
    $options = new stdClass;
    $options->noclean = true;
    echo format_text($pagedata->content, FORMAT_HTML, $options);
}

echo $OUTPUT->footer();
