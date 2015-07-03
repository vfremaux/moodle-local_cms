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
 * for deleting a page
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');
require_once($CFG->dirroot.'/local/cms/forms/deletepage_form.php');

$id = required_param('id', PARAM_INT); // page id
$courseid = optional_param('course', SITEID, PARAM_INT);

require_login();

confirm_sesskey();

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

$formdata = cms_get_page_data_from_id($id);

// Define context.

if ($courseid == SITEID ) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

require_capability('local/cms:deletepage', $context);

$stradministration = get_string('administration');
$strcms = get_string('cms', 'local_cms');
$strpages = get_string('pages', 'local_cms');
$strdelete = get_string('deletepage', 'local_cms');

$url = new moodle_url('/local/cms/pagedelete.php', array('course' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($strcms.' '.$stradministration, new moodle_url('/local/cms/index.php', array('course' => $course->id, 'sesskey' => sesskey())));
$PAGE->navbar->add($strdelete);
$PAGE->set_context($context);
$PAGE->set_title($strdelete);
$PAGE->set_heading($strdelete);

$deleteform = new Delete_form($url, array('pagetitle' => $formdata->title));

if ($deleteform->is_cancelled()) {
    redirect(new moodle_url('/local/cms/pages.php', array('course'=> $course->id, 'menuid' => $formdata->naviid, 'sesskey' => sesskey())));
}

if ($data = $deleteform->get_data()) {

    // Get page data to see if this user can delete this page.
    if (!$navidata = $DB->get_record('local_cms_navi_data', array('pageid' => $data->id))) {
        redirect(new moodle_url('/local/cms/pages.php', array('course' => $course->id, 'menuid' => $data->naviid, 'sesskey' => sesskey())),
                 "Could not get navidata! You cant delete this page!", 2);
    }

    if (!$navi = $DB->get_record('local_cms_navi', array('id' => $navidata->naviid))) {
        redirect(new moodle_url('/local/cms/pages.php', array('course' => $course->id, 'menuid' => $data->naviid, 'sesskey' => sesskey())),
                 "Could not get navi and course id's! You cant delete this page!", 2);
    }

    if (intval($navi->course) !== intval($course->id)) {
        print_error("You have no rights to delete page $navidata->title ", $CFG->wwwroot);
    }

    // Delete child pages first if any.
    $childpages = cms_get_children_ids($data->id);
    if (!empty($childpages)) {
        foreach ($childpages as $childpage) {
            $DB->delete_records('local_cms_pages', array('id' => $childpage));
            $DB->delete_records('local_cms_navi_data', array('pageid' => $childpage));
        }
    }

    // Delete page first
    if (!$DB->delete_records('local_cms_pages', array('id' => $data->id))) {
        print_error("Could not delete page!");
    }

    // Delete navidata
    if (!$DB->delete_records('local_cms_navi_data', array('id' => $navidata->id))) {
        print_error("Could not delete navigation data!");
    }

    // Delete page history.
    if (!$DB->delete_records('local_cms_pages_history', array('pageid' => $data->id)) ) {
        print_error("Could not delete page history!");
    }

    $message = get_string('pagedeleted', 'local_cms');
    redirect(new moodle_url('/local/cms/pages.php', array('course' => $course->id, 'menuid' => $data->naviid, 'sesskey' => sesskey())), $message);
}

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading($stradministration);

$deletemessage = get_string('pagedeletesure', 'local_cms', $formdata->title);

// include_once('html/delete.php');
$deleteform->set_data($formdata);
$deleteform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
