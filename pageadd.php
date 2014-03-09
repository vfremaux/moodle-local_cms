<?php // $Id: pageadd.php,v 1.8 2008/03/23 09:11:38 julmis Exp $

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

    require_once('../../config.php');
    include_once($CFG->dirroot.'/local/cms/locallib.php');
    include_once($CFG->dirroot.'/local/cms/forms/editpage_form.php');

    // Required params.
    $id = required_param('id', PARAM_INT); // Menu id
    $courseid = optional_param('course', SITEID, PARAM_INT);

    confirm_sesskey();
    
    if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
        print_error('coursemisconf');
    }

    require_login($courseid);
        
    /// Define context
    if ($courseid == SITEID ) {
	    $context = context_system::instance();
    } else {
	    $context = context_course::instance($course->id);
    }

    require_capability('local/cms:createpage', $context);
    
    $menus = $DB->get_records('local_cms_navi', array('course' => $course->id));
    $parents = cms_get_possible_parents($id, 0); // this is a new page

	$url = $CFG->wwwroot.'/local/cms/pageadd.php?course='.$courseid.'&id='.$id;
    $PAGE->set_url($url);

    $pageform = new Edit_Page_form($url, array('menus' => $menus, 'parents' => $parents));

    if ($pageform->is_cancelled()) { 
        // Cancel button has been pressed
        if (!empty($pageparentid)) {
        	$parentpagename = $DB->get_field('local_cms_navi_data', 'pagename', array('id' => $pageparentid));
            redirect($CFG->wwwroot.'/local/cms/index.php?id='.$courseid.'&sesskey='.sesskey().'&page='.$parentpagename);
        }
        redirect($CFG->wwwroot.'/local/cms/pages.php?sesskey='.sesskey().'&course='.$course->id.'&menuid='.$id);
    }

    if ($fromform = $pageform->get_data()) { 

        if ( preg_match("/^\S{1,}/", $fromform->title) ) {

			// pre process files
			$body_draftid_editor = file_get_submitted_draft_itemid('body_editor');
			$fromform->body = file_save_draft_area_files($body_draftid_editor, $context->id, 'local_cms', 'body', $data->id, $pageform->editoroptions, $fromform->body);
        	
            // insert page first
            $newpage = new StdClass();
            $newpage->created  = time();
            $newpage->modified = time();
            $newpage->body     = $fromform->body;
            $newpage->format = FORMAT_HTML; // fakes as not really recorded

            if (!empty($fromform->publish) or !empty($fromform->pageisfp)) {
                $newpage->publish = 1;
            } else {
                $newpage->publish = 0;
            }

            if (!$newpage->id = $DB->insert_record('local_cms_pages', $newpage)) {
                print_error("Couldn't create new page!");
            }
            
            $pageid = $newpage->id;

			// this post update is needed to rencode embedded files links
		    $newpage = file_postupdate_standard_editor($newpage, 'body', $pageform->editoroptions, $context, 'local_cms', 'body', $newpage->id);	
	        $DB->update_record('local_cms_pages', $newpage);
            
            // cleanup used fields
            
            // Insert title to cmsnavi_data

            if (empty($fromform->pagename) or is_numeric($fromform->pagename)) { // if no pagename is supplied, use page id
                $fromform->pagename = $fromform->pageid;
            }

            if (empty($fromform->showinmenu) ) {
                $fromform->showinmenu = 0;
            }

            if (empty($fromform->showblocks) ) {
                $fromform->showblocks = 0;
            }

            $fromform->isfp        = 0;
            $fromform->sortorder   = 2000;
            $fromform->pageid   = $pageid;

            if (!empty($fromform->pageurl) ) {                
                $fromform->target = ($pagetarget != '_blank') ? '_top' : '_blank';
            } else {
                $fromform->target = '';
            }

            if (!$newid = $DB->insert_record('local_cms_navi_data', $fromform)) {
                $DB->delete_records('local_cms_pages', array('id' => $pageid));
                print_error ("Error while linking page to menu! Page has been removed.");
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
                redirect($CFG->wwwroot . '/index.php?id='.$course->id.'&page='.$fromform->pagename);
            }
            // We're in site level.
            redirect($CFG->wwwroot.'/index.php?page='.$fromform->pagename);
        }
    } else {
        $error = get_string('missingtitle', 'local_cms');
    }

    $stradministration = get_string('administration');
    $strcms            = get_string('cms', 'local_cms');
    $strpages          = get_string('pages', 'local_cms');
    $straddnew         = get_string('addnewpage', 'local_cms');
    $strformtitle      = &$straddnew;

    $PAGE->set_pagelayout('admin');
    $PAGE->navbar->add($strcms.' '.$stradministration, $CFG->wwwroot.'/local/cms/index.php?course='.$course->id.'&amp;sesskey='.sesskey());
    $PAGE->navbar->add($straddnew);
    $PAGE->set_context($context);
    $PAGE->set_title($straddnew);
    $PAGE->set_heading($straddnew);
    
    echo $OUTPUT->header();

    if (!empty($pagepreview)) { // Preview button has been pressed
        cms_print_preview(data_submitted(), $course);
    }

    echo $OUTPUT->heading(get_string('newpage', 'local_cms'));
    echo $OUTPUT->box_start();

    if ( !empty($error) ) {
        echo $OUTPUT->notification($error);
    }

	$formdata = new StdClass();
	$formdata->what = 'add';
	$formdata->parentid = optional_param('parentid', 0, PARAM_INT);
	$formdata->title = get_string('newpage', 'local_cms');

	$pageform->set_data($formdata);

	$pageform->display();
    // include_once($CFG->dirroot.'/cms/html/editpage.php');

    echo $OUTPUT->box_end();

    echo $OUTPUT->footer();
