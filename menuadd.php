<?php // $Id: menuadd.php,v 1.2 2008/03/23 09:11:37 julmis Exp $

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
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

    require_once("../../config.php");
    require_once($CFG->dirroot.'/local/cms/forms/editmenu_form.php');

    $courseid = optional_param('course', SITEID, PARAM_INT);

    require_login();

    confirm_sesskey();

    if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
        print_error('coursemisconf');
    }

    include_once($CFG->dirroot.'/local/cms/lib.php');

    /// Define context
    if ($courseid == SITEID) {        
    	$context = context_system::instance();
    } else {
    	$context = context_course::instance($course->id);
    }

    require_capability('local/cms:createmenu', $context);

    $stradministration = get_string('administration');
    $straddnew         = get_string('addnewmenu','local_cms');
    $strcms            = get_string('cms','local_cms');
    $strmenus          = get_string('menus','local_cms');

	$url = $CFG->wwwroot.'/local/cms/menuadd.php?course='.$courseid;
    $PAGE->set_url($url);
    $PAGE->set_pagelayout('admin');
    $PAGE->navbar->add($strcms.' '.$stradministration, $CFG->wwwroot.'/local/cms/index.php?course='.$course->id.'&amp;sesskey='.sesskey());
    $PAGE->navbar->add($straddnew);
    $PAGE->set_context($context);
    $PAGE->set_title($straddnew);
    $PAGE->set_heading($straddnew);
    
    $menuform = new Edit_Menu_form();
    
    if ($menu = $menuform->get_data() ) {

        $menu->id   = NULL;
        $menu->name = trim($menu->name);

        $menu->created = time();
        $menu->modified = time();
        
        $menu->intro = $menu->intro;

        if (!$rs = $DB->insert_record('local_cms_navi', $menu)) {
            print_error("Couldn't create new menu!");
        }

        $message = get_string('menuadded', 'local_cms');
        redirect($CFG->wwwroot.'/local/cms/menus.php?course='.$courseid.'&amp;sesskey='.sesskey(), $message);
    }

    echo $OUTPUT->header();


    echo $OUTPUT->box_start();
    echo $OUTPUT->heading($straddnew);
    
    // Print form to add new menu

    if ( empty($form) ) {
    	$form = new StdClass;
        $form->name  = '';
        $form->intro = '';
        $form->allowguest   = 0;
        $form->requirelogin = 0;
        $form->printdate = 1;
    }

    $menuform->set_data($form);
    $menuform->display();

    // include_once('html/editmenu.php');

    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
