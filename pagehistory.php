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
 * Tracks all versions of a page
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

    require('../../config.php');
    require($CFG->dirroot.'/local/cms/locallib.php');

    $pageid   = required_param('pageid',  PARAM_INT);
    $courseid = required_param('course',  PARAM_INT);

    if (!$pageinfo = $DB->get_record('local_cms_navi_data', array('pageid' => $pageid))) {
        print_error('Invalid page id');
    }

    if (!$navi = $DB->get_record('local_cms_navi', array('id' => $pageinfo->naviid))) {
        print_error('Invalid menu');
    }

    $menuid = $navi->id;

    if ( !$course = $DB->get_record('course', array('id' => $navi->course)) ) {
        error('coursemisconf');
    }

    require_login($course->id);

    
    confirm_sesskey();
    
    /// Define context
    if ($courseid == SITEID ) {
	    $context = context_system::instance();
    } else {
	    $context = context_course::instance($course->id);
    }

    require_capability('local/cms:editpage', $context);

    $stradministration = get_string('administration');
    $strcms            = get_string('cms', 'local_cms');
    $strpages          = get_string('pages', 'local_cms');
    $strhistory        = get_string('pagehistory', 'local_cms');
    $strunknown        = get_string('unknownauthor', 'local_cms');
    $strview           = get_string('view');
    $strfetchback      = get_string('fetchback', 'local_cms');
    $strdiff           = get_string('diff', 'local_cms');

	$url = $CFG->wwwroot.'/local/cms/pagehistory.php?course='.$courseid;
    $PAGE->set_url($url);
    $PAGE->set_pagelayout('admin');
    $PAGE->navbar->add($strcms.' '.$stradministration, $CFG->wwwroot.'/local/cms/index.php?course='.$course->id.'&amp;sesskey='.sesskey());
    $PAGE->navbar->add($strhistory);
    $PAGE->set_context($context);
    $PAGE->set_title($strhistory);
    $PAGE->set_heading($strhistory);
    
    echo $OUTPUT->header();
    
    echo $OUTPUT->box_start('center');
    echo $OUTPUT->heading(format_string($pageinfo->title));

    if ( $pagehistory = cms_get_page_history($pageid) ) {
        $tbl = new html_table();
        $tbl->head  = array(get_string('author', 'local_cms'), get_string('version'), get_string('modified'), get_string('action'));
        $tbl->align = array('left', 'center', 'left', 'left');
        $tbl->width = '100%';
        $tbl->wrap  = array('nowrap', '', '', 'nowrap');
        $tbl->data  = array();

        foreach ( $pagehistory as $page ) {
            $row = array();
            $row[] = !empty($page->firstname) ? fullname($page) : $strunknown;
            $row[] = $page->version;
            $row[] = userdate($page->modified, "%x %X");

            $viewurl  = '/local/cms/historyview.php?pageid='. $page->id .'&amp;course='. $course->id;

            $params = array(
            'height' =>  600,
            'width' => 800,
            'top' => 0,
            'left' => 0,
            'menubar' => false,
            'location' => false,
            'scrollbars' => true,
            'resizable' => true,
            'toolbar' => true,
            'status' => true,
            'directories' => false,
            'fullscreen' => false,
            'dependent' => true            
            );

            $action = new popup_action('onclick', $viewurl, 'openpopup', $params);
            $viewlink = $OUTPUT->action_link($viewurl, $strview, $action, array('title' => $strview));

            $fetchback = '<a href="pageupdate.php?id='. $pageinfo->pageid .'&amp;sesskey='. sesskey().'&amp;course='. $course->id .'&amp;version='. $page->id .'">'. $strfetchback .'</a>';

            if ( floatval($page->version) >= 1.1 ) {
                $diffurl  = '/local/cms/pagediff.php?id='.$page->id.'&amp;course='.$course->id.'&amp;sesskey='.sesskey();
            	$action = new popup_action('onclick', $viewurl, 'openpopup', $params);
            	$difflink = $OUTPUT->action_link($diffurl, $strdiff, $action, array('title' => $strdiff));
            } else {
                $difflink = '';
            }

            $row[] = " $viewlink | $fetchback | $difflink ";
            array_push($tbl->data, $row);
        }

        echo html_writer::table($tbl);
    }

    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();


//////////////////////////////// Supporting functions ////////////////////////////////

function cms_get_page_history($pageid) {
    global $CFG, $DB;

    $sql = "
        SELECT 
            h.id, 
            h.version, 
            h.modified,
            u.firstname, 
            u.lastname
        FROM
            {local_cms_pages_history} h
        LEFT JOIN
            {user} u ON h.author = u.id
        WHERE 
            h.pageid = ?
        ORDER BY 
            h.modified DESC
    ";
    return $DB->get_records_sql($sql, array($pageid));

}
