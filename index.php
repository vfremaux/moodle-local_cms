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
 * CMS administration index page for site/course level.
 *
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$courseid = optional_param('course', SITEID, PARAM_INT);

// Security.
require_login($courseid);

if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('coursemisconf');
}

$url = new moodle_url('/local/cms/index.php', array('course' => $courseid));

// Get proper context for operations.

$contextinstance = null;
if ($courseid == SITEID) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

$stradministration = get_string('administration');
$strcms = get_string('cms', 'local_cms');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->navbar->add($strcms.' '.$stradministration);
$PAGE->set_title($strcms);
$PAGE->set_heading($strcms);

$renderer = $PAGE->get_renderer('local_cms');

echo $OUTPUT->header();

echo $OUTPUT->box_start('', 'cmsmanagementpanel');

echo $renderer->render_index($context);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
