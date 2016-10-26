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
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
include_once($CFG->dirroot.'/local/cms/locallib.php');

$courseid = required_param('course', PARAM_INT);

// Security.
require_login();

confirm_sesskey();

if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error("coursemisconf");
}

// Get a proper context.

if ( empty($courseid) ) {
    $courseid = SITEID;
    $context = context_system::instance();
} else {
    $context = context_course::instance($courseid);
}
require_capability('local/cms:editpage', $context);

// Print page header.

$strcms = get_string('cms', 'local_cms');
$stractres = get_string('activities') .'/'. get_string('resources');
$stradministration = get_string('administration');

$url = new moodle_url('/local/cms/activities.php', array('course' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($straddnew);
$PAGE->set_heading($straddnew);
$PAGE->navbar->add($strcms.' '.$stradministration, new moodle_url('/index.php', array('course' => $course->id, 'sesskey' => $USER->sesskey)));
$PAGE->navbar->add($stractres);
$PAGE->requires->js('/local/cms/js/cms.js');

echo $OUTPUT->header();

// Print page.

$straction = get_string('action');
$strchoose = get_string('choose');

$table = new html_table();

$table->head = array($stractres, $straction);
$table->align = array('left', 'left');
$table->cellpadding = 2;
$table->data = array();

$modinfo = unserialize($course->modinfo);
if ( !empty($modinfo) ) {
    foreach ( $modinfo as $mod ) {
        $row = array();
        if ( empty($mod->visible) ) {
            continue;
        }
        if ( !empty($mod->icon) ) {
            $icon = "$CFG->pixpath/$mod->icon";
        } else {
            $icon = $OUTPUT->pix_url('icon', $mod->mod);
        }
        $icon = '<img src="'. $icon .'" alt="" />';
        $instancename = urldecode($mod->name);
        $instancename = format_string($instancename, true,  $course->id);

        $javascript  = "<a href=\"javascript: void(set_value('/mod/{$mod->mod}/view.php?id={$mod->cm}', '{$CFG->wwwroot}'));\">";
        $javascript .= $strchoose .'</a>';
        //echo $icon . ' ';
        //echo $instancename . "<br />\n";
        $row[] = $icon . ' '. $instancename;
        $row[] = $javascript;
        array_push($table->data, $row);
    }
}

echo html_writer::table($table);

echo $OUTPUT->footer();
