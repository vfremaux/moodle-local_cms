<?php

/**
 * This file contains necessary functions to output
 * cms content on site or course level.
 *
 * @author Janne Mikkonen
 * @author Gustav Delius
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 * @package local_cms
 */

/**
* DEPRECATED (valery). Shouldn't be called from anywhere.
* This function is only called from course/format/cms/format.php
* Print the selected page content.
*
* @param int $pageid
* @param object $course
* @param bool $editing
* @return void
*/
function cms_print_page ($pageid, $course, $editing=false) {
    global $CFG, $USER, $DB;

    if ( !is_object($course) ) {
        $courseid = intval($course);
        $course = $DB->get_record('course', array('id' => $courseid));
        echo $OUTPUT->notificaiton("Second parameter of cms_print_page has changed from integer to object!<br />".
               "To get rid of this message make appropriate changes to your index.php file!");
    }

    global $sections, $modnames, $mods, $modnamesused;

    $pageid   = clean_param($pageid, PARAM_INT);
    $courseid = clean_param($course->id, PARAM_INT);

    if ($pageid == 0) {
        $whereclause = "nd.isfp = 1 AND n.course = ". $courseid;
    } else {
        $whereclause = "p.id = ". $pageid;
    }

    $sql = "
        SELECT 
            p.id, 
            p.body, 
            p.modified, 
            nd.isfp, 
            nd.parentid,
            n.requirelogin, 
            n.allowguest, 
            n.printdate 
        FROM
            {local_cms_pages} AS p
        INNER JOIN 
            {local_cms_navi_data} AS nd 
        ON 
            p.id = nd.pageid
        LEFT JOIN 
            {local_cms_navi} AS n 
        ON 
            nd.naviid = n.id
        WHERE
            $whereclause
    ";
    $pagedata = get_record_sql($sql);

    include('html/frontpage.php');
}

/**
 * Get page content.
 *
 * @param int $courseid Site or course id.
 * @param int $pageid   Page id.
 * @return string
 */
function cms_get_page_data_by_id($courseid, $pageid) {
    global $CFG, $DB;

    // Fetch pagedata from the database

    if (intval($pageid) != $pageid || intval($courseid) != $courseid) {
        return false;
    }

    $sql = "
            SELECT
                p.id,
                p.body,
                p.modified,
                nd.isfp,
                nd.parentid,
                nd.naviiid,
                nd.title,
                nd.pagename,
                nd.showblocks,
                n.requirelogin,
                n.allowguest,
                n.printdate,
                n.course
            FROM
                {cmspages} p
			INNER JOIN {local_cms_navi_data} nd ON p.id = nd.pageid
            LEFT JOIN {local_cms_navi} n ON nd.naviid = n.id
            WHERE
                p.id = ?
            AND
                n.course =  ?
    ";

    $pagedata = $DB->get_record_sql($sql, array($pageid, $courseid));
    if (empty($pagedata)) {
        return '<p>'. get_string('nocontent', 'local_cms') .'</p>';
    }
    return $pagedata;
}

/**
 * Get page content. This function should decapricate
 * all previous funcitons including course format.
 *
 * @param int $courseid Site or course id.
 * @param mixed $pagename Page name or page id.
 * @return string
 */
function cms_get_page_data($courseid, $pagename) {
    global $CFG, $DB;
    
    // Fetch pagedata from the database

    if ($pagename) {
        $whereclause = "nd.pagename = '{$pagename}' AND";
    } else {
        $whereclause = "nd.isfp = '1' AND";
    }

    $sql = "
        SELECT 
			p.id, 
			p.body, 
			p.modified, 
			nd.isfp, 
			nd.parentid,
			nd.naviid,
            nd.title, 
            nd.pagename, 
            nd.showblocks,
            n.requirelogin, 
            n.allowguest, 
            n.printdate, 
            n.course        
         FROM
            {local_cms_pages} p 
        INNER JOIN 
            {local_cms_navi_data} nd 
        ON 
            p.id = nd.pageid
        LEFT JOIN 
            {local_cms_navi} n 
        ON 
            nd.naviid = n.id
        WHERE 
            $whereclause
            n.course = ?
    ";

    if (!$pagedata = $DB->get_record_sql($sql, array($courseid))) {
        //return dummy object.
        $fields = preg_replace("/ ?[a-z]{1,2}\./", "", $fields);
        $dummy = explode(",", $fields);
        $pagedata = array_flip($dummy);
        foreach ( $pagedata as $key => $value ) {
            $pagedata[$key] = null;
            if ( $key == 'body' ) {
                $pagedata[$key] = sprintf("<p>%s</p>\n", get_string('nocontent','local_cms'));
            }
        }
        $pagedata = (object)$pagedata;
    }
    return $pagedata;
}

/**
* prints an automatically generated TOC
*
*/
function cms_print_toc($pid) {
    global $CFG, $DB;

    $return = '';
    if ($navidatas = $DB->get_records('local_cms_navi_data', array('parentid' => $pid), 'sortorder ASC')) {
        $return .= '<ul>';
        foreach ($navidatas as $navidata) {
            if ($navidata->showinmenu) {
                $return .= '<li><a href="'.$CFG->wwwroot.'/cms/view.php?page='.$navidata->pagename.'">'.$navidata->title.'</a></li>';
            }
        }
        $return .= '</ul>';
    }
    return $return;
}

/**
* this function outputs to the output buffer the contents of the supplied http url
*/
function cms_safe_include($url, $setbase=false) {
    global $CFG;
    $url = trim($url);
    if (substr(trim(strtoupper($url)), 0, 7) !== "HTTP://") {
        $url = "http://".$url;
    }

    if (strpos($url,"?") === false) {
        $url = $url."?".$_SERVER["QUERY_STRING"];
    } else {
        $url = $url."&".$_SERVER["QUERY_STRING"];
    }

    if ($outstr = file_get_contents($url)) {
        if ($setbase) {
            $outstr = '<base href="'.$url.'" />'.$outstr.'<base href="'.$CFG->wwwroot.'/cms/" />';
        }
        return $outstr;
    } else {
        return '';
    }
}

function cms_include_page($pagename, $course) {
	global $DB;
	
    $pagedata->id = $DB->get_field('local_cms_navi_data', 'pageid', array('pagename' => $pagename));
    $pagedata->body = $DB->get_field('local_cms_pages', 'body', array('id' => $pagedata->id));

    return cms_render($pagedata, $course);
}

function cms_breadcrumbs(&$path, $navidata) {
    global $CFG, $DB;

    $path[format_string($navidata->title)] = $CFG->wwwroot.'/local/cms/view.php?page='.$navidata->pagename;

    if ($navidata->parentid) {
        if (!$parent = $DB->get_record('local_cms_navi_data', array('pageid' => $navidata->parentid), 'title, pagename, parentid')) {
            print_error('Could not find data for page '.$navidata->parentid);
        }
        cms_breadcrumbs($path, $parent);
    }
}

/**
 * Create navigation string from breadcrumbs array
 *
 * @param array $breadcrumbs
 * @return mixed Returns string or false
 */
function cms_navigation_string ($breadcrumbs) {
    if ( !is_array($breadcrumbs) ) {
        return false;
    }
    $breadcrumbs = array_reverse($breadcrumbs);
    $navigation = '';
    $current = 1;
    $total = count($breadcrumbs);
    foreach ( $breadcrumbs as $key => $value ) {
        if ( $current++ == $total ) {
            $navigation .= ' '. $key;
        } else {
            $navigation .= '<a href="'. $value .'">'. s(format_string($key)) .'</a> -> ';
        }
    }
    return $navigation;
}

function cms_render_link($link, $title) {
    return '<a href="'.$link.'">'.$title.'</a>';
}

function cms_print_preview($pagedata, $course) {
    global $CFG, $DB, $OUTPUT;

    echo $OUTPUT->notification(get_string('onlypreview','local_cms'));
    echo '<table id="layout-table" cellspacing="0">';
    echo '<tr><td style="width: 210px;" id="left-column">&nbsp;</td><td id="middle-column">';
    echo $OUTPUT->box_start('center', '100%', '', 5, 'sitetopic');
    echo cms_render($pagedata, $course);
    echo $OUTPUT->box_end();
    echo '</td>';

    if ($pagedata->showblocks) {
        echo '<td style="width: 210px;" id="right-column">&nbsp;</td>';
    }

    echo '</tr></table>';
}

function cms_render_news($course) {
    global $CFG;

    if ($course->newsitems) { // Print forums only when needed
        require_once($CFG->dirroot .'/mod/forum/lib.php');

        if (! $newsforum = forum_get_course_forum($course->id, 'news')) {
            print_error('Could not find or create a main news forum for the course');
        }

        if (isset($USER->id)) {
            $SESSION->fromdiscussion = $CFG->wwwroot;
            if (forum_is_subscribed($USER->id, $newsforum->id)) {
                $subtext = get_string('unsubscribe', 'forum');
            } else {
                $subtext = get_string('subscribe', 'forum');
            }
            $headertext = '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>'.
                '<td><div class="title">'.$newsforum->name.'</div></td>'.
                '<td><div class="link"><a href="mod/forum/subscribe.php?id='.$newsforum->id.'">'.$subtext.'</a></div></td>'.
                '</tr></table>';
        } else {
            $headertext = $newsforum->name;
        }

        print_heading_block($headertext);
        forum_print_latest_discussions($course, $newsforum, $course->newsitems, 'plain', 'p.modified DESC');
        $return = ob_get_contents();
        return $return;
    }
    return '';
}

/**
* inserts dynamic content
* @param object $pagedata 
* @param object $course the course in context
*/
function cms_render($pagedata, $course) {
    global $sections, $USER, $DB;

    // content marked as private should be shown with a special style to people with editing rights
    // and should not be shown to others
    
    if ($course->id == SITEID){
	    $context = context_system::instance();
	} else {
	    $context = context_course::instance($course->id);
	}
    
    $canedit = has_capability('local/cms:editpage', $context, $USER->id);

    $private = $canedit ? '<div class="private">$1</div>' : '';

    $search = array(
        "#\[\[INCLUDE (.+?)\]\]#ie",
        "#\[\[SCRIPT (.+?)\]\]#ie",
        "#\[\[PAGE (.+?)\]\]#ie", // [[PAGE subPage]] for including another page
        "#\[\[NEWS\]\]#ie",
        "#\[\[PRIVATE (.+?)\]\]#is" , // [[PRIVATE content]] for content only to be shown for users with writing privileges
        "#\[\[TOC\]\]#ie" , // [[TOC]] produces a table of contents listing all child pages
        "#\[\[([^\[\]]+?)\s*\|\s*(.+?)\]\]#es", // [[free link | title]]
        //"#\[\[(.+?)\]\]#e", // [[free link]]
        "#\._\.#ie" // escape string, to prevent recognition of special senquences
    );

    $replace = array(
        'cms_safe_include("$1", true)',
        'cms_safe_include("$1", false)',
        'cms_include_page("$1", $course)',
        'cms_render_news($course)',
        $private,
        'cms_print_toc($pagedata->id)',
        'cms_render_link("$1" ,"$2")',
        //'cms_render_link("$1")',
        ''
    );

    $body = preg_replace($search, $replace, $pagedata->body);
    $body = file_rewrite_pluginfile_urls($body, 'pluginfile.php', $context->id, 'local_cms', 'body', $pagedata->id);

    // Search sections.
    preg_match_all("/{#section([0-9]+)}/i", $body, $match);
    $cmssections = $match[1];
    // At this point allow only course level not site level.
    if ( !empty($cmssections) ) {
        foreach ( $cmssections as $cmssection ) {
            if ( !empty($sections[$cmssection]) ) {
                $thissection = $sections[$cmssection];
            } else {
                unset($thissection);
                // make sure that the section doesn't exist.
                if ( !$DB->record_exists('course_sections', array('section' => $cmssection, 'course' => $course->id)) ) {
                    $thissection->course = $course->id;   // Create a new section structure
                    $thissection->section = $cmssection;
                    $thissection->summary = '';
                    $thissection->visible = 1;
                    if (!$thissection->id = $DB->insert_record('course_sections', $thissection)) {
                        echo $OUTPUT->notification('Error inserting new topic!');
                    }
                } else {
                    $thissection = $DB->get_record('course_sections', array('course' => $course->id, 'section' => $cmssection));
                }
            }

            if ( !empty($thissection) ) {
                if (empty($mods)) {
                    get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
                }
                $showsection = ( $canedit or $thissection->visible or !$course->hiddensections);
                if ( $showsection ) {
                    $content  = '<div id="cms-section-'. $cmssection .'">';
                    $content .= cms_get_section($course, $thissection, $mods, $modnamesused);

                    if (isediting($course->id)) {
                        $content .= cms_section_add_menus($course, $cmssection, $modnames, false);
                    }
                    $content .= '</div>';
                    $body = preg_replace("/{#section$cmssection}/", $content, $body);
                }
            } else {
                $body = preg_replace("/{#section$cmssection}/", '', $body);
            }
        }
    }

    $options = new stdClass;
    $options->noclean = true;
    return format_text($body, FORMAT_HTML, $options);
}

/**
* Implements controller for CMS page actions
* @uses $CFG
* @uses $USER
*
*/
function cms_actions($pagedata, $course, $context) {
    global $CFG, $USER, $OUTPUT, $DB;
    
    if ( has_capability('local/cms:manageview', $context) ) {
        $stredit   = get_string('edit');
        $stradd    = get_string('addchild', 'local_cms');
        $strhistory    = get_string('pagehistory', 'local_cms');
        $strdelete = get_string('delete');
        $streditmenus = get_string('editmenu', 'local_cms');

        $toolbar = '';

        if ( has_capability('local/cms:editpage', $context) ) {
            $editlink = $CFG->wwwroot .'/local/cms/pageupdate.php?id='. $pagedata->id .'&amp;sesskey='.sesskey().'&amp;course='. $course->id;
            $editicon = $OUTPUT->pix_url('i/edit');
            $toolbar = sprintf('<a href="%s"><img src="%s" width="16" height="16" ' .'alt="%s" title="%s" border="0" /></a>%s',$editlink, $editicon, $stredit, $stredit, "\n");
        }

        if ( has_capability('local/cms:createpage', $context, $USER->id) && !empty($pagedata->id) ) {
            $menuid = $DB->get_field('local_cms_navi_data', 'naviid', array('pageid' => $pagedata->id));
        	$addlink = $CFG->wwwroot .'/local/cms/pageadd.php?id='. $menuid .'&amp;'.'sesskey='.sesskey().'&amp;parentid='.$pagedata->id.'&amp;course=' . $course->id .'';
        	$addicon = $OUTPUT->pix_url('add','local_cms');
            $toolbar .= sprintf('<a href="%s"><img src="%s" width="16" '.'height="16" alt="%s" title="%s" border="0" /></a>%s',$addlink, $addicon, $stradd, $stradd, "\n");
        }

        if ( has_capability('local/cms:editpage', $context, $USER->id) &&!empty($pagedata->id) ) {
        	$historylink = $CFG->wwwroot .'/local/cms/pagehistory.php?pageid='.$pagedata->id.'&amp;'.'sesskey='.sesskey() ;
        	$historyicon = $OUTPUT->pix_url('history', 'local_cms');
            $toolbar .= sprintf('<a href="%s"><img src="%s" width="16" '.'height="16" alt="%s" title="%s" border="0" /></a>%s',$historylink, $historyicon, $strhistory, $strhistory, "\n");
        }

        if ( has_capability('local/cms:deletepage', $context, $USER->id) && ( !empty($pagedata->id) && intval($pagedata->isfp) !== 1) ) {
	        $deletelink = $CFG->wwwroot .'/local/cms/pagedelete.php?id='. $pagedata->id .'&amp;'.'sesskey='.sesskey().'&amp;course=' . $course->id .'';
	        $deleteicon = $OUTPUT->pix_url('t/delete');
            $toolbar .= sprintf('<a href="%s"><img src="%s" width="11" '.'height="11" alt="%s" title="%s" border="0" /></a>%s',$deletelink, $deleteicon, $strdelete, $strdelete, "\n");
        }

        if ( has_capability('local/cms:editmenu', $context, $USER->id) && !empty($pagedata->naviid)) {
	        $editmenulink = $CFG->wwwroot .'/local/cms/menus.php?id='. $pagedata->naviid .'&amp;'.'sesskey='.sesskey().'&amp;course=' . $course->id .'';
	        $editmenuicon = $OUTPUT->pix_url('f/folder');
            $toolbar .= sprintf('<a href="%s"><img src="%s" width="11" '.'height="11" alt="%s" title="%s" border="0" /></a>%s',$editmenulink, $editmenuicon, $streditmenus, $streditmenus, "\n");
        }

        if ( !empty($toolbar) ) {
            $toolbar = '<div class="cms-frontpage-toolbar">'. $toolbar .'</div>'."\n";
        }
        return $toolbar;
    }
}

/**
 * Serves the files included in cms pages. Implements needed access control ;-)
 *
 * There are several situations in general where the files will be sent.
 * 1) filearea = 'body'
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function local_cms_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB;

    if (!in_array($filearea, array('body'))) {
        return false;
    }

	$itemid = (int)array_shift($args);

	$navidata = $DB->get_record('local_cms_navi_data', array('pageid' => $itemid));
	$navi = $DB->get_record('local_cms_navi', array('id' => $navidata->naviid));
	
	if ($navi->requirelogin){
		if ($course->id == SITEID){
			require_login();
		} else {
			require_login($course);
		}
	
		if (!$navi->allowguest && is_guest_user()){
			return false;
		}
	}

    $fs = get_file_storage();
    
    if ($files = $fs->get_area_files($context->id, 'local_cms', 'body', $itemid, "sortorder, itemid, filepath, filename", false)){
    	$file = array_pop($files);

	    // finally send the file
	    send_stored_file($file, 0, 0, $forcedownload);
    }


    return false;
}

