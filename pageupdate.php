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
 * for updating a page
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

$id = required_param('id', PARAM_INT); // Page id
$courseid = optional_param('course', SITEID, PARAM_INT); // This is the menu block implantation course.
$version = optional_param('version', 0, PARAM_INT);

$originalpage = cms_get_page_data_from_id($id);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

// Security.

confirm_sesskey();
require_login($course->id);

if ($originalpage->course == SITEID || $originalpage->course == 0) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($originalpage->course);
}

require_capability('local/cms:editpage', $context);

$stradministration = get_string('administration');
$strcms = get_string('cms', 'local_cms');
$strpages = get_string('pages', 'local_cms');
$strupdatepage = get_string('updatepage', 'local_cms');
$strformtitle = &$strupdatepage;

$url = new moodle_url('/local/cms/pageupdate.php', array('course' => $courseid, 'pageid' => $id));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add($strcms.' '.$stradministration, new moodle_url('/local/cms/index.php', array('course' => $course->id, 'sesskey' => sesskey())));
$PAGE->navbar->add($strupdatepage);
$PAGE->set_context($context);
$PAGE->set_title($strupdatepage);
$PAGE->set_heading($strupdatepage);

$menus = $DB->get_records('local_cms_navi', array('course' => $course->id));
$parents = cms_get_possible_parents($originalpage->nid, $originalpage->navidataid); // this is a new page

$pageform = new Edit_Page_form($url->out_omit_querystring(), array('menus' => $menus, 'parents' => $parents, 'pagecourse' => $originalpage->course));

if ($pageform->is_cancelled()) { // Cancel button has been pressed
    redirect(new moodle_url('/local/cms/view.php', array('page' => $originalpage->pagename, 'id' => $courseid)));
}

if ($fromform = $pageform->get_data()) { // Save button has been pressed

    if (empty($fromform->pagename) or is_numeric($fromform->pagename)) { // if no pagename is supplied, use page id
        $fromform->pagename = $fromform->id;
    }

    $oldname = $DB->get_field('local_cms_navi_data', 'pagename', array('pageid' => $fromform->id));

    if (($oldname != $fromform->pagename) && cms_pagename_exists($fromform->pagename, $course->id)) {
        // the name has changed but new name is already taken
        $error = get_string('nameinuse', 'local_cms', $fromform->pagename);
        $pagenameerror = true;
    } else {

        // Update title to cmsnavi_data

        // If menu has been changed set parentid to zero.
        // And get all pages underneath this page and move them too
        // into that new menu.
        $oldnaviid = $DB->get_field('local_cms_navi_data', 'naviid', array('id' => $fromform->id));

        if (intval($oldnaviid) !== intval($fromform->nid)) {
            $children = cms_get_children_ids($fromform->id);

            foreach ( $children as $childid ) {
                if ( !$DB->set_field('local_cms_navi_data', 'naviid', $fromform->naviid, array('pageid' => $childid)) ) {
                    $errorbackurl = new moodle_url('/cms/pages.php', array('course' => $course->id, 'menuid' => $oldnaviid));
                    print_error("Cannot modify menu information for child pages!", '', $errorbackurl);
                }
            }

            $fromform->sortorder = 2000;
        }

        if (!empty($fromform->url)) {
            $fromform->url = clean_param($fromform->url, PARAM_URL);
            $fromform->target = ($fromform->target != '_blank') ? '_top' : '_blank';
        } else {
            $fromform->url = '';
            $fromform->target = '';
        }

        if (empty($fromform->showinmenu)) {
            $fromform->showinmenu = 0;
        }

        if (empty($fromform->showblocks)) {
            $fromform->showblocks = 0;
        }

        if (empty($fromform->embedded)) {
            $fromform->embedded = 0;
        }

        $updatedpage = new StdClass();
        $updatedpage->id = $fromform->pid;

        if (!$DB->update_record('local_cms_navi_data', $fromform)) {
            print_error ("Error while linking page to menu! Page has been removed.");
        }

        // Update page first.
        $updatedpage->modified = time();
        $updatedpage->lastuserid = $USER->id;

        // Pre process files.
        $body_draftid_editor = file_get_submitted_draft_itemid('body');
        $fromform->body = file_save_draft_area_files($body_draftid_editor, $context->id, 'local_cms', 'body', $updatedpage->id, $pageform->editoroptions, $fromform->body['text']);

        // This post update is needed to rencode embedded files links.

        $updatedpage->body = $fromform->body;

        if (empty($fromform->publish)) {
            $updatedpage->publish = 0;
        } else {
            $updatedpage->publish = 1;
        }

        $oldbody = $DB->get_field('local_cms_pages', 'body', array('id' => $updatedpage->id));

        if (!$DB->update_record('local_cms_pages', $updatedpage)) {
            print_error("Couldn't update page: $fromform->title!");
        }

        if ($oldbody != $updatedpage->body) {
            // Get old version info and add new entry to history table.
            if ($version = cms_get_page_version($updatedpage->id, true)) {
                $history = new StdClass();
                $history->version = (string) (floatval($version) + 0.1);
                if (strpos($history->version, ".") === FALSE) {
                    $history->version .= '.0';
                }
                $history->modified = time();
                $history->content = !empty($fromform->url) ? $fromform->url : $updatedpage->body;
                $history->author = $USER->id;
                $DB->insert_record('local_cms_pages_history', $history);
            }
        }

        if (defined('SITEID')) {
            if ($course->id != SITEID) {
                // We're in course level.
                redirect(new moodle_url('/local/cms/view.php', array('id' => $course->id, 'page' => $fromform->pagename)));
            }
            // We're in site level.
            redirect(new moodle_url('/local/cms/view.php', array('page' => $fromform->pagename)));
        }
        redirect(new moodle_url('/local/cms/view.php', array('page' => $fromform->pagename)));
    }
}

$renderer = $PAGE->get_renderer('local_cms');

echo $OUTPUT->header();

if (!empty($version)) {
    if ( $versiondata = $DB->get_record('local_cms_pages_history', array('id' => $version)) ) {
        $formdata->body = $versiondata->content;
    }
}

if (isset($page->preview)) {
    // Preview button has been pressed.
    echo $renderer->preview($formdata, $course);
}

echo $OUTPUT->box_start();
$formdata = $originalpage;
$formdata->id = $originalpage->id;
$formdata->pid = $originalpage->id;
$formdata->nid = $originalpage->nid;
$formdata->course = $originalpage->course;
$formdata->what = 'update';
$pageform->set_data($formdata);
$pageform->display();

echo $OUTPUT->box_end();

echo $OUTPUT->footer();
