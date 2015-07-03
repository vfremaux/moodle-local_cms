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
 * Index page of all ùmenus
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');

$id = optional_param('id', 0, PARAM_INT);
$courseid = optional_param('course', SITEID, PARAM_INT);

require_login();

$USER->editing = false;

confirm_sesskey();

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

// Define context.

if ($courseid == SITEID ) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

require_capability('local/cms:manageview', $context);

$stradministration = get_string('administration');
$strcms = get_string('cms', 'local_cms');
$strmenus = get_string('menus', 'local_cms');

$url = new moodle_url('/local/cms/menus.php', array('course' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
if ($course->id > SITEID) {
    $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array('id' => $course->id)));
}
$PAGE->navbar->add($strcms.' '.$stradministration, new moodle_url('/local/cms/index.php', array('course' => $course->id, 'sesskey' => sesskey())));
$PAGE->navbar->add($strmenus);
$PAGE->set_context($context);
$PAGE->set_title($strmenus);
$PAGE->set_heading($strmenus);

$renderer = $PAGE->get_renderer('local_cms');

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading($stradministration);

// Print list of menus.
echo $renderer->menus($course->id);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
