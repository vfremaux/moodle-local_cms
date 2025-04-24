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
 * for updating a menu
 *
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');
require_once($CFG->dirroot.'/local/cms/forms/editmenu_form.php');

$id = required_param('id', PARAM_INT);       // menu id
$courseid = optional_param('course', SITEID, PARAM_INT);

if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('coursemisconf');
}

// Security.

confirm_sesskey();
require_login();

if ($courseid == SITEID ) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

require_capability('local/cms:editmenu', $context);

$stradministration = get_string('administration');
$streditmenu = get_string('editmenu', 'local_cms');
$strcms = get_string('cms', 'local_cms');
$strmenus = get_string('menus', 'local_cms');

$url = new moodle_url('/local/cms/menuedit.php', array('course' => $courseid));
$PAGE->set_pagelayout('admin');
$PAGE->set_url($url);
$PAGE->navbar->add($strcms.' '.$stradministration, $CFG->wwwroot.'/local/cms/index.php?course='.$course->id.'&amp;sesskey='.sesskey());
$PAGE->navbar->add($streditmenu);
$PAGE->set_context($context);
$PAGE->set_title($streditmenu);
$PAGE->set_heading($streditmenu);

$menuform = new Edit_Menu_form();

if ( $menu = $menuform->get_data() ) {

    $menu->name  = trim($menu->name);

    if (empty($menu->requirelogin)) {
        $menu->requirelogin = 0;
    }

    $description = $menu->intro;
    $menu->intro = $description['text'];
    // $menu->introformat = $description['format']; // Not implemented

    $menu->modified = time();

    try {
        $DB->update_record('local_cms_navi', $menu);
    } catch (Exception $ex) {
        print_error("CMS menu : Couldn't update menu record !");
    }

    $message = get_string('updatedmenu', 'local_cms');
    redirect(new moodle_url('/local/cms/menus.php', array('course' => $course->id, 'sesskey' => sesskey())), $message);

}

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading($streditmenu);

// Print form to add new menu.

if (empty($form)) {
    $formdata = $DB->get_record('local_cms_navi', array('id' => $id));
    $menuform->set_data($formdata);
}

if (!empty($error)) {
    echo $OUTPUT->notification($error);
}

$menuform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
