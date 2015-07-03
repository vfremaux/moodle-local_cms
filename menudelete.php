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
 * for deleting a menu
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');

$id = required_param('id', PARAM_INT);       // menu id
$courseid = optional_param('course', SITEID, PARAM_INT);

require_login();

confirm_sesskey();

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

// Define context.

if ($courseid == SITEID) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

require_capability('local/cms:deletemenu', $context);

$stradministration = get_string('administration');
$strdeletemenu = get_string('deletemenu', 'local_cms');
$strcms = get_string('cms', 'local_cms');
$strmenus = get_string('menus', 'local_cms');

$url = new moodle_url('/local/cms/menudelete.php', array('course' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($strcms.' '.$stradministration, $CFG->wwwroot.'/local/cms/index.php?course='.$course->id.'&amp;sesskey='.sesskey());
$PAGE->navbar->add($strdeletemenu);
$PAGE->set_context($context);
$PAGE->set_title($strdeletemenu);
$PAGE->set_heading($strdeletemenu);
    
if ($menu = data_submitted()) {

    // User pushed cancel button.
    if ( !empty($menu->cancel) ) {
        redirect(new moodle_url('/local/cms/menus.php', array('course' => $courseid, 'sesskey' => sesskey())));
    }

    // Just to be sure!
    if (empty($menu->id)) {
        print_error("Required variable missing!");
    }

    $menu->id = clean_param($menu->id, PARAM_INT);

    $pagerecords = $DB->get_records('local_cms_navi_data', array('naviid' => $menu->id));

    // Remove related pages.

    if (! empty($pagerecords)) {
        foreach ($pagerecords as $pr) {

            if (!$DB->delete_records('local_cms_pages', array('id' => $pr->pageid))) {
                print_error("Couldn't delete related page records!");
            }

            if (!$DB->delete_records('local_cms_navi_data', array('id' => $pr->id))) {
                print_error("Couldn't delete related navigation data!");
            }
        }
    }

    if (!$DB->delete_records('local_cms_navi', array('id' => $menu->id))) {
        print_error("Couldn't delete requested menu!");
    }

    $message = get_string('menudeleted', 'local_cms');
    redirect(new moodle_url('/local/cms/menus.php', array('course' => $courseid, 'sesskey' => sesskey())), $message);

}

// Print confirmation page.
// Just to be sure!!

if (empty($id)) {
    print_error("Required variable missing!");
}

echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading($strdeletemenu);

$form = $DB->get_record('local_cms_navi', array('id' => $id));

$deletemessage = get_string('menudeletesure', 'local_cms', $form->name);
$form->id = $id;

$url->params(array('id' => $id));
echo $deletemessage;
echo $OUTPUT->single_button($url, get_string('confirm'));

$cancelurl = new moodle_url('/local/cms/menudelete.php', array('course' => $courseid, 'cancel' => 1));
echo $OUTPUT->single_button($cancelurl, get_string('cancel'));

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
