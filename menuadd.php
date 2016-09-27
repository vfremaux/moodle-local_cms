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
 * for adding a new menu
 *
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @reauthor   Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/cms/forms/editmenu_form.php');
require_once($CFG->dirroot.'/local/cms/lib.php');

$courseid = optional_param('course', SITEID, PARAM_INT);

// Security.

confirm_sesskey();

if (!$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('coursemisconf');
}

// Define context.

if ($courseid == SITEID) {
    $context = context_system::instance();
    require_login();
} else {
    $context = context_course::instance($course->id);
    require_course_login($course->id, true);
}

require_capability('local/cms:createmenu', $context);

$stradministration = get_string('administration');
$straddnew = get_string('addnewmenu', 'local_cms');
$strcms = get_string('cms', 'local_cms');
$strmenus = get_string('menus', 'local_cms');

$url = new moodle_url('/local/cms/menuadd.php', array('course' => $courseid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($strcms.' '.$stradministration, new moodle_url('/local/cms/index.php', array('course' => $course->id, 'sesskey' => sesskey())));
$PAGE->navbar->add($straddnew);
$PAGE->set_context($context);
$PAGE->set_title($straddnew);
$PAGE->set_heading($straddnew);

$menuform = new Edit_Menu_form();

if ($menu = $menuform->get_data() ) {

    $menu->id   = NULL;
    $menu->name = trim($menu->name);
    $menu->course = $courseid;
    $menu->created = time();
    $menu->modified = time();
    $menu->intro = $menu->intro;

    if (!$rs = $DB->insert_record('local_cms_navi', $menu)) {
        print_error("Couldn't create new menu!");
    }

    $message = get_string('menuadded', 'local_cms');
    redirect(new moodle_url('/local/cms/menus.php', array('course' => $courseid, 'sesskey' => sesskey())), $message);
}

// Start printing page.

echo $OUTPUT->header();

echo $OUTPUT->box_start();
echo $OUTPUT->heading($straddnew);

// Print form to add new menu.

if (empty($form)) {
    $form = new StdClass;
    $form->name  = '';
    $form->intro = '';
    $form->course = $courseid;
    $form->allowguest   = 0;
    $form->requirelogin = 0;
    $form->printdate = 1;
}

$menuform->set_data($form);
$menuform->display();

// include_once('html/editmenu.php');

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
