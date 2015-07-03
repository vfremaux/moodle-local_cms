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
 * For adding a page
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');
require_once($CFG->dirroot.'/local/cms/forms/editpage_form.php');

// Required params.

$menuid = required_param('nid', PARAM_INT); // Menu id
$courseid = optional_param('course', SITEID, PARAM_INT);

confirm_sesskey();

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

require_login($courseid);

// Define context.

$menu = $DB->get_record('local_cms_navi', array('id' => $menuid));
if (($menu->course == SITEID) || ($menu->course == 0)) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

require_capability('local/cms:createpage', $context);

$menus = $DB->get_records('local_cms_navi', array('course' => $course->id));
$parents = cms_get_possible_parents($menuid, 0); // this is a new page

$url = new moodle_url('/local/cms/pageadd.php', array('course' => $courseid, 'id' => $menuid));
$PAGE->set_url($url);

$pageform = new Edit_Page_form($url->out_omit_querystring(), array('menus' => $menus, 'parents' => $parents, 'pagecourse' => $courseid));

if ($pageform->is_cancelled()) {
    // Cancel button has been pressed
    if (!empty($pageparentid)) {
        $parentpagename = $DB->get_field('local_cms_navi_data', 'pagename', array('id' => $pageparentid));
        redirect(new moodle_url('/local/cms/index.php', array('id' => $courseid, 'sesskey' => sesskey(), 'page' => $parentpagename)));
    }
    redirect(new moodle_url('/local/cms/pages.php', array('sesskey' => sesskey(), 'course' => $course->id, 'menuid' => $menuid)));
}

if ($fromform = $pageform->get_data()) {

    // Odd behaviour : Why do we loose that field ? 
    $fromform->parentid = $_REQUEST['parentid'];

    confirm_sesskey();

    if (preg_match("/^\S{1,}/", $fromform->title)) {

        // Insert page first.
        $newpage = new StdClass();
        $newpage->created  = time();
        $newpage->modified = time();
        $newpage->body = $fromform->body['text'];
        $newpage->bodyformat = $fromform->body['format']; // fakes as not really recorded

        if (!empty($fromform->publish) or !empty($fromform->pageisfp)) {
            $newpage->publish = 1;
        } else {
            $newpage->publish = 0;
        }

        try {
            $newpage->id = $DB->insert_record('local_cms_pages', $newpage);
        } catch(Exception $e) {
            print_error('errorcreatepage', 'local_cms');
        }

        // Pre process files.
        $body_draftid_editor = file_get_submitted_draft_itemid('body');
        $fromform->body = file_save_draft_area_files($body_draftid_editor, $context->id, 'local_cms', 'body', $fromform->id, $pageform->editoroptions, $fromform->body['text']);

        $newpage->body = $fromform->body;

        $pageid = $newpage->id;

        // Post update to store file insertions recoding
        $DB->update_record('local_cms_pages', $newpage);

        // Cleanup used fields.

        // Insert title to cmsnavi_data.

        if (empty($fromform->pagename) or is_numeric($fromform->pagename)) { // if no pagename is supplied, use page id
            $fromform->pagename = $fromform->pageid;
        }

        if (empty($fromform->showinmenu) ) {
            $fromform->showinmenu = 0;
        }

        if (empty($fromform->showblocks) ) {
            $fromform->showblocks = 0;
        }

        $fromform->isfp = 0;
        $fromform->sortorder = 2000;
        $fromform->pageid = $pageid;

        if (!empty($fromform->pageurl) ) {
            $fromform->target = ($pagetarget != '_blank') ? '_top' : '_blank';
        } else {
            $fromform->target = '';
        }

        try {
            $newid = $DB->insert_record('local_cms_navi_data', $fromform);
        } catch(Exception $e) {
            $DB->delete_records('local_cms_pages', array('id' => $pageid));
            print_error('errorpagemenulink', 'local_cms');
        }

        if ( $pageid && $newid ) {
            // Add entry to cmspage_history table.
            $history = new stdClass;
            $history->pageid = $fromform->pageid;
            $history->modified = time();
            $history->version = '1.0';
            $history->content = !empty($fromform->url) ? $fromform->url : $fromform->body ;
            $history->author = (int) $USER->id;
            $DB->insert_record('local_cms_pages_history', $history);
        }

        if ($course->id != SITEID) {
            // We're in course level.
            redirect(new moodle_url('/local/cms/view.php', array('id' => $course->id, 'page' => $fromform->pagename, 'edit' => 1, 'sesskey' => sesskey())));
        }
        // We're in site level.
        redirect(new moodle_url('/local/cms/view.php', array('page' => $fromform->pagename, 'edit' => 1, 'sesskey' => sesskey())));
    }
} else {
    $error = get_string('missingtitle', 'local_cms');
}

$stradministration = get_string('administration');
$strcms            = get_string('cms', 'local_cms');
$strpages          = get_string('pages', 'local_cms');
$straddnew         = get_string('addnewpage', 'local_cms');
$strformtitle      = &$straddnew;

$PAGE->set_title($straddnew);
$PAGE->set_context($context);
if ($courseid > SITEID) {
    $PAGE->set_pagelayout('standard');
} else {
    $PAGE->set_pagelayout('admin');
}
$PAGE->navbar->add($strcms.' '.$stradministration, new moodle_url('/local/cms/index.php', array('course' => $course->id, 'sesskey' => sesskey())));
$PAGE->navbar->add($straddnew);
$PAGE->set_heading($straddnew);

$renderer = $PAGE->get_renderer('local_cms');

echo $OUTPUT->header();

if (!empty($pagepreview)) {
    // Preview button has been pressed.
    echo $renderer->preview(data_submitted(), $course);
}

echo $OUTPUT->heading(get_string('newpage', 'local_cms'));
echo $OUTPUT->box_start();

if ( !empty($error) ) {
    echo $OUTPUT->notification($error);
}

$formdata = new StdClass();
$formdata->what = 'add';
$formdata->id = 0; // new page
$formdata->pid = 0; // new page
$formdata->nid = $menuid; // current menu
$formdata->course = $courseid; // current course context
$formdata->parentid = optional_param('parentid', 0, PARAM_INT);
$formdata->body = '';
$pagename = optional_param('pagename', '', PARAM_TEXT);
if (empty($pagename)) {
    $formdata->title = get_string('newpage', 'local_cms');
} else {
    $formdata->title = htmlentities($pagename, ENT_QUOTES, 'UTF-8');
    $formdata->pagename = htmlentities($pagename, ENT_QUOTES, 'UTF-8');
}

$pageform->set_data($formdata);

$pageform->display();
// include_once($CFG->dirroot.'/cms/html/editpage.php');

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
