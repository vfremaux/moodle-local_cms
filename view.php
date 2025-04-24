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
 * displays a page
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

if (!defined('MOODLE_INTERNAL')) {
    // We are entering directly in this page.
    require('../../config.php');
    require_once($CFG->dirroot.'/local/cms/locallib.php');

    $config = get_config('local_cms');

    $pagename = optional_param('page', '', PARAM_TEXT);

    // If a pagename is given, give course scope
    if ($CFG->slasharguments && !$pagename) {
        $relativepath = str_replace($config->virtual_path, '', cms_get_file_argument());
        $pagename = urldecode(preg_replace('#^/#', '', $relativepath));
    } else {
        $courseid = optional_param('id', SITEID, PARAM_INT);
    }

    // A single pid is enough to rebuild scope.
    $pageid = optional_param('pid', 0, PARAM_INT);
    $internal = false;
} else {
    // this case when this script is included
    // TODO: check this is still useful....
    // $courseid = SITEID;
    // if (!isset($pagename)) $pagename = '';
    global $OUTPUT;
    $pageid = optional_param('pid', 0, PARAM_INT);
    $internal = true;
}

require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->libdir.'/ajax/ajaxlib.php');
require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->dirroot.'/local/cms/lib.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');

$config = get_config('local_cms');

// The following parameters are the same as on course/view.php minus $id, $name, $idnumber
$edit = optional_param('edit', -1, PARAM_BOOL);
$hide = optional_param('hide', 0, PARAM_INT);
$show = optional_param('show', 0, PARAM_INT);
$section = optional_param('section', 0, PARAM_INT);
$move = optional_param('move', 0, PARAM_INT);
$marker = optional_param('marker',-1 , PARAM_INT);
$switchrole = optional_param('switchrole',-1, PARAM_INT);

// Resolve page from input params.
if (!empty($pageid)) {
    // Explicit page id given.
    if ($pageid == 0) {
        throw new moodle_exception(get_string('errorbadpage', 'local_cms'));
    }
    if (!$pagedata = cms_get_page_data_from_id($pageid)) {
        throw new moodle_exception(get_string('errorbadpage', 'local_cms'));
    }

    if (!$course = $DB->get_record('course', array('id' => $pagedata->course))) {
        throw new moodle_exception(get_string('coursemisconf'));
    }
} else {
    if (empty($courseid)) {
        $courseid = SITEID;
    }
    if ($courseid > SITEID || !empty($pagename)) {
        $course = $DB->get_record('course', array('id' => $courseid));
        if (!$pagedata = cms_get_page_data($course->id, 0, $pagename)) {
            $e = new StdClass;
            $e->courseid = $course->id;
            $e->pagename = $pagename;
            throw new moodle_exception(get_string('errorbadpage', 'local_cms', $e));
        }
    } else {
        if (!$pagedata = cms_get_page_data(0, 0, $pagename)) {
            throw new moodle_exception(get_string('errorbadpage', 'local_cms'));
        }
    }
    $pageid = $pagedata->id;
}

if (empty($pagedata)) {
    // Last chance if we are in site context : use slasharguments
    if (SITEID == $courseid && $CFG->slasharguments ) {
        /*
         * Support sitelevel slasharguments.
         * in form /index.php/<$CFG->block_cmsnavigation_cmsvirtual>/<pagename>
         */
        $relativepath = get_file_argument(basename($_SERVER['SCRIPT_FILENAME']));
        if ( preg_match("#^{$config->virtual_path}(/[a-z0-9\_\-]+)#i", $relativepath) ) {
            $args = explode("/", $relativepath);
            $pagename = clean_param($args[2], PARAM_FILE);
        } else {
            throw new moodle_exception(get_string('errorbadpage', 'local_cms'));
        }
        unset($args, $relativepath);
        $pagedata = cms_get_page_data(SITEID, 0, $pagename);
        $course = $DB->get_record('course', array('id' => $pagedata->course));
    }
}

// Get a valid context.

if ($course->id == SITEID) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

// Check accessibility by checking page menu state.
if ($pagedata->requirelogin) {

    if ($course->id == SITEID) {
        require_login();
    } else {
        require_course_login($course);
    }

    if (!$pagedata->allowguest) {
        if ($course->id != SITEID) {
            if (is_guest($context)) {
                redirect(new moodle_url('/course/view.php', array('id' => $courseid)));
            }
        }
    }
} else {
    // We can see this page, but it is held on a course context, so we need fake the global $COURSE;
    $COURSE = $course;
}

// Remove any switched roles before checking login.
if ($switchrole == 0 && confirm_sesskey()) {
    role_switch($switchrole, $context);
}

//require_login($course); //CMS not used?

// Switchrole - sanity check in cost-order...
$reset_user_allowed_editing = false;
if ($switchrole > 0 && confirm_sesskey() &&
    has_capability('moodle/role:switchroles', $context)) {
    /*
     * is this role assignable in this context?
     * inquiring minds want to know...
     */
    $aroles = get_assignable_roles($context);
    if (is_array($aroles) && isset($aroles[$switchrole])) {
        role_switch($switchrole, $context);
        // Double check that this role is allowed here.
        if (@!empty($pagedata->requirelogin) && !has_capability('moodle/site:config', $context)) { //CMS check if need to requirelogin.
            require_login($course->id);
        }
    }
}

$params = array(
    'context' => $context,
    'objectid' => $pagedata->id
);
$event = \local_cms\event\local_cms_page_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$cmsstr = get_string('cms', 'local_cms');

$url = new moodle_url('/local/cms/view.php', array('pid' => $pageid));

$PAGE->set_url($url);
$PAGE->set_context($context);
local_cms_add_nav($pagedata);
$PAGE->set_title($pagedata->title);
$PAGE->set_course($course);
if ($pagedata->embedded) {
    $PAGE->set_pagelayout('embedded');
}

$renderer = $PAGE->get_renderer('local_cms');

if (!isset($USER->editing)) {
    $USER->editing = 0;
}

if (!$editing = $PAGE->user_is_editing()) {
    $button = $renderer->update_page_button($pageid, $context);
    $PAGE->set_button($button);
}

if ($PAGE->user_allowed_editing()) {
    if (($edit == 1) and confirm_sesskey()) {
        $USER->editing = 1;
    } elseif (($edit == 0) and confirm_sesskey()) {
        $USER->editing = 0;
    }
} else {
    $USER->editing = 0;
}

if (!$internal) {
    echo $OUTPUT->header();
}

echo '<div class="course-content">';  // Course wrapper start.

// Bounds for block widths.

echo $OUTPUT->box_start('sitetopic');

if (! empty($pagedata->requirelogin) &&
   (isguestuser() && !$pagedata->allowguest)) {
        echo get_string('pageviewdenied', 'local_cms');
} else {

    if ($editing) {
        echo $renderer->actions($pagedata, $course, $context);
    }
    $pagecontent = $renderer->render_page($pagedata, $course);

    echo $pagecontent;

    if (!empty($pagedata->printdate)) {
        if ($pagedata->lastuserid) {
            $lastuser = $DB->get_record('user', array('id' => $pagedata->lastuserid));
            $e = new StdClass();
            $e->by = fullname($lastuser);
            $e->modified = userdate($pagedata->modified);
            echo '<p style="font-size: x-small;">'. get_string('lastmodifiedby', 'local_cms', $e) .'</p>';
        } else {
            // This is just for handling old unassigned records.
            $modified = userdate($pagedata->modified);
            echo '<p style="font-size: x-small;">'. get_string('lastmodified', 'local_cms', $modified) .'</p>';
        }
    }
    if ($editing) {
        $stradmin = get_string('admin');
        $adminurl = new moodle_url('/local/cms/index.php', array('course' => $course->id, 'sesskey' => $USER->sesskey));
        echo '<p style="font-size: x-small;">';
        echo '<a href="'.$adminurl.'">'.$stradmin.'</a></p>';
    }
}

echo $OUTPUT->box_end();

echo '</div>';

if (!$internal) {
    echo $OUTPUT->footer();
}