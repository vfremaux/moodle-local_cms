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

defined('MOODLE_INTERNAL') || die();

/**
 * CMS display library
 *
 * @package    local_cms
 * @category local
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class local_cms_renderer extends plugin_renderer_base {

    /**
    * inserts dynamic content
    * @param object $pagedata 
    * @param object $course the course in context
    */
    function render_page($pagedata, $course) {
        global $sections, $USER, $DB, $SESSION;

        // content marked as private should be shown with a special style to people with editing rights
        // and should not be shown to others

        if ($course->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($course->id);
        }

        $SESSION->currentcmsmenu = $pagedata->nid;
        $SESSION->currentcmspage = $pagedata->id;

        $canedit = has_capability('local/cms:editpage', $context, $USER->id);

        $private = $canedit ? '<div class="private">$1</div>' : '';

        $search = array(
            "#\[\[INCLUDE (.+?)\]\]#i",
            "#\[\[SCRIPT (.+?)\]\]#i",
            "#\[\[PAGE (.+?)\]\]#i", // [[PAGE subPage]] for including another page
            "#\[\[NEWS\]\]#i",
            "#\[\[PRIVATE (.+?)\]\]#is" , // [[PRIVATE content]] for content only to be shown for users with writing privileges
            "#\[\[TOC\]\]#i" , // [[TOC]] produces a table of contents listing all child pages
            "#\[\[([^\[\]]+?)\s*\|\s*(.+?)\]\]#s", // [[free link | title]]
            "#\[\[(.+?)\]\]#", // [[free new page]]
            "#\._\.#i" // escape string, to prevent recognition of special senquences
        );


        $body = preg_replace_callback($search, 'replace_handler', $pagedata->body);
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
     * Renders controller for CMS page actions
     * @uses $CFG
     * @uses $USER
     */
    function actions($pagedata, $course, $context) {
        global $USER, $OUTPUT, $DB;

        if ( has_capability('local/cms:manageview', $context) ) {
            $stredit = get_string('edit');
            $stradd = get_string('addchild', 'local_cms');
            $strhistory = get_string('pagehistory', 'local_cms');
            $strdelete = get_string('delete');
            $streditmenus = get_string('editmenu', 'local_cms');

            $toolbar = '';

            if ( has_capability('local/cms:editpage', $context) ) {
                $editurl = new moodle_url('/local/cms/pageupdate.php', array('id' => $pagedata->id, 'sesskey' => sesskey(), 'course' => $course->id));
                $editicon = $OUTPUT->pix_url('i/edit');
                $toolbar = '<a href="'.$editurl.'"><img src="'.$editicon.'" width="16" height="16" alt="'.$stredit.'" title="'.$stredit.'" border="0" /></a>';
            }

            if (has_capability('local/cms:createpage', $context, $USER->id) && !empty($pagedata->id)) {
                $menuid = $DB->get_field('local_cms_navi_data', 'naviid', array('pageid' => $pagedata->id));
                $addurl = new moodle_url('/local/cms/pageadd.php', array('nid' => $menuid, 'sesskey' => sesskey(), 'parentid' => $pagedata->id, 'course' => $course->id));
                $addicon = $OUTPUT->pix_url('add','local_cms');
                $toolbar .= ' <a href="'.$addurl.'"><img src="'.$addicon.'" width="16" height="16" alt="'.$stradd.'" title="'.$stradd.'" border="0" /></a>';
            }

            if (has_capability('local/cms:editpage', $context, $USER->id) &&!empty($pagedata->id)) {
                $historyurl = new moodle_url('/local/cms/pagehistory.php', array('pageid' => $pagedata->id, 'sesskey' => sesskey(), 'course' => $course->id));
                $historyicon = $OUTPUT->pix_url('history', 'local_cms');
                $toolbar .= ' <a href="'.$historyurl.'"><img src="'.$historyicon.'" width="16" '.'height="16" alt="'.$strhistory.'" title="'.$strhistory.'" border="0" /></a>';
            }

            if ( has_capability('local/cms:deletepage', $context, $USER->id) && ( !empty($pagedata->id) && intval($pagedata->isfp) !== 1) ) {
                $deleteurl = new moodle_url('/local/cms/pagedelete.php', array('id' => $pagedata->id, 'sesskey' => sesskey(), 'course' => $course->id));
                $deleteicon = $OUTPUT->pix_url('t/delete');
                $toolbar .= ' <a href="'.$deleteurl.'"><img src="'.$deleteicon.'" width="11" height="11" alt="'.$strdelete.'" title="'.$strdelete.'" border="0" /></a>';
            }

            if ( has_capability('local/cms:editmenu', $context, $USER->id) && !empty($pagedata->naviid)) {
                $editmenuurl = new moodle_url('/local/cms/menus.php', array('id' => $pagedata->naviid, 'sesskey' => sesskey(), 'course' => $course->id));
                $editmenuicon = $OUTPUT->pix_url('f/folder');
                $toolbar .= ' <a href="'.$editmenuurl.'"><img src="'.$editmenuicon.'" width="11" '.'height="11" alt="'.$streditmenus.'" title="'.$streditmenus.'" border="0" /></a>';
            }

            if (has_capability('local/cms:editpage', $context)) {
                $toolbar .= $OUTPUT->help_icon('editortricks', 'local_cms');
            }

            if ( !empty($toolbar) ) {
                $toolbar = '<div class="cms-frontpage-toolbar">'.$toolbar.'</div>'."\n";
            }
            return $toolbar;
        }
    }

    function preview($pagedata, $course) {
        global $CFG, $DB, $OUTPUT;

        $str = '';

        $str .= $OUTPUT->notification(get_string('onlypreview','local_cms'));
        $str .= '<table id="layout-table" cellspacing="0">';
        $str .= '<tr><td style="width: 210px;" id="left-column">&nbsp;</td><td id="middle-column">';
        $str .= $OUTPUT->box_start('center', '100%', '', 5, 'sitetopic');
        $str .= $this->render($pagedata, $course);
        $str .= $OUTPUT->box_end();
        $str .= '</td>';

        if ($pagedata->showblocks) {
            $str .= '<td style="width: 210px;" id="right-column">&nbsp;</td>';
        }

        $str .= '</tr></table>';

        return $str;
    }

    function news($course) {
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
                $forumurl = new moodle_url('/mod/forum/subscribe.php', array('id' => $newsforum->id));
                $headertext = '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>'.
                    '<td><div class="title">'.$newsforum->name.'</div></td>'.
                    '<td><div class="link"><a href="'.$forumurl.'">'.$subtext.'</a></div></td>'.
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
     * prints an automatically generated TOC
     */
    function toc($pid) {
        global $DB;

        $page = $DB->get_record('local_cms_navi_data', array('id' => $pid));

        $return = '';
        if ($navidatas = $DB->get_records('local_cms_navi_data', array('parentid' => $pid, 'naviid' => $page->naviid), 'sortorder ASC')) {
            $return .= '<ul>';
            foreach ($navidatas as $navidata) {
                if ($navidata->showinmenu) {
                    $pageurl = new moodle_url('/local/cms/view.php', array('pid' => $navidata->id));
                    $return .= '<li><a href="'.$pageurl.'">'.$navidata->title.'</a></li>';
                }
            }
            $return .= '</ul>';
        }
        return $return;
    }

    function include_page($pagename, $course) {
        global $DB;

        $pagedata->id = $DB->get_field('local_cms_navi_data', 'pageid', array('pagename' => $pagename));
        $pagedata->body = $DB->get_field('local_cms_pages', 'body', array('id' => $pagedata->id));

        return $this->render($pagedata, $course);
    }

    /**
     * This function outputs to the output buffer the contents of the supplied http url.
     */
    function safe_include($url, $setbase = false) {
        global $CFG;

        $str = '';
        $url = trim($url);
        if (substr(trim(strtoupper($url)), 0, 7) !== "HTTP://") {
            $url = "http://".$url;
        }
    
        if (strpos($url,"?") === false) {
            $url = $url."?".$_SERVER["QUERY_STRING"];
        } else {
            $url = $url."&".$_SERVER["QUERY_STRING"];
        }
    
        if ($str = file_get_contents($url)) {
            if ($setbase) {
                $str = '<base href="'.$url.'" />'.$str.'<base href="'.$CFG->wwwroot.'/local/cms/" />';
            }
        }

        return $str;
    }

    function breadcrumbs(&$path, $navidata) {
        global $CFG, $DB;

        $str = '';

        $path[format_string($navidata->title)] = new moodle_url('/local/cms/view.php', array('page' => $navidata->pagename));

        if ($navidata->parentid) {
            if (!$parent = $DB->get_record('local_cms_navi_data', array('pageid' => $navidata->parentid), 'title, pagename, parentid')) {
                print_error('Could not find data for page '.$navidata->parentid);
            }
            $str .= $this->breadcrumbs($path, $parent);
        }

        return $str;
    }

    /**
     * Prints a link over parent page if exists
     *
     */
    function parent_link($pid, $label = '') {
        global $DB;

        $page = cms_get_page_data_by_id(0, $pid);
        if ($page->parentid) {
            $parentpid = $DB->get_field('local_cms_navi_data', 'pageid', array('id' => $page->parentid));
            $parent = cms_get_page_data_by_id(0, $parentpid);
            $parenturl = new moodle_url('/local/cms/view.php', array('pid' => $parentpid));
            if (empty($label)) {
                return '<a href="'.$parenturl.'">'.$parent->title.'</a>';
            } else {
                return '<a href="'.$parenturl.'">'.$label.'</a>';
            }
        }
    }

    /**
     * Print menu selection list.
     *
     * @param int $courseid
     * @return void
     */
    function menus ($courseid = 1) {
        $str = $this->newmenulink($courseid);
        $str .= $this->allmenus($courseid);
        return $str;
    }

    /**
     * Print all menus.
     *
     * @uses $CFG
     * @uses $USER
     * @param int $courseid
     * @return void
     */
    function allmenus($courseid = 1) {
        global $DB, $OUTPUT;

        $menus = $DB->get_records('local_cms_navi', array('course' => $courseid));

        if (is_array($menus)) {
            $tbl = new html_table();

            $strname     = get_string('name');
            $stractions  = get_string('actions','local_cms');
            $strintro    = get_string('intro','local_cms');
            $strcreated  = get_string('created','local_cms');
            $strmodified = get_string('modified');
            $strrequirelogin = get_string('requirelogin','local_cms');
            $strallowguest   = get_string('allowguest','local_cms');
            $keypix = $OUTPUT->pix_url('key', 'local_cms');
            $imgrlogin = '<img src="'.$keypix.'" width="16" height="16" alt="'. $strrequirelogin .'"'.' title="'. $strrequirelogin .'" />';
            $guestpix = $OUTPUT->pix_url('guest', 'local_cms');
            $imgallowguest = '<img src="'.$guestpix.'" width="16" height="16" alt="'. $strallowguest .'"' .' title="'. $strallowguest .'" />';

            $tbl->head = array($strname, $stractions, $strintro,$strcreated, $strmodified, $imgrlogin, $imgallowguest);

            $tbl->width = '100%';
            $tbl->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center');
            $tbl->wrap  = array('nowrap', 'nowrap', '', '', '', '', '');
            $tbl->data  = array();

            $editpagestr = get_string('editmenu', 'local_cms');
            $deletepagestr = get_string('deletemenu', 'local_cms');
            $viewpagestr = get_string('viewpages', 'local_cms');

            foreach ($menus as $menu) {
                $editurl = new moodle_url('/local/cms/menuedit.php', array('id' => $menu->id, 'sesskey' => sesskey(), 'course' => $courseid));
                $editlink  = '<a href="'.$editurl.'">';
                $editlink .= '<img src="'.$OUTPUT->pix_url('t/edit').'" alt="'.$editpagestr.'" title="'.$editpagestr.'" border="0" /></a>';

                $deleteurl = new moodle_url('/local/cms/menudelete.php', array('id' => $menu->id, 'sesskey' => sesskey(), 'course' => $courseid));
                $dellink  = '<a href="'.$deleteurl.'">';
                $dellink .= '<img src="'.$OUTPUT->pix_url('t/delete').'" alt="'.$deletepagestr.'" title="'.$deletepagestr.'" border="0" /></a>';

                $created  = userdate($menu->created, "%x %X");
                $modified = userdate($menu->modified, "%x %X");

                $menu->name  = format_string($menu->name);
                $pagelinkurl = new moodle_url('/local/cms/pages.php', array('sesskey' => sesskey(), 'course' => $courseid, 'menuid' => $menu->id));
                $menuname    = '<a title="'.$viewpagestr.'" href="'.$pagelinkurl.'">'. $menu->name .'</a>';

                $rlogin      = ($menu->requirelogin) ? get_string('yes') : get_string('no');
                $allowguest  = ($menu->allowguest or !$menu->requirelogin) ? get_string('yes') : get_string('no');

                $newrow = array($menuname, "$editlink $dellink", format_string($menu->intro), $created, $modified, $rlogin, $allowguest);

                array_push($tbl->data, $newrow);
            }

            return html_writer::table($tbl);
        } else {
            $str = '<div align="center">';
            $str .= '<p>';
            $str .= get_string('nomenus', 'local_cms');
            $str .= '</p></div>';
        }

        return $str;
    }

    /**
     * Print Add new link into menu administration page.
     *
     * @uses $USER
     * @param int $courseid
     * @return void
     */
    function newmenulink($courseid = 1) {

        $straddnew = get_string('addnewmenu','local_cms');
        $url = new moodle_url('/local/cms/menuadd.php', array('course' => $courseid, 'sesskey' => sesskey()));
        return '<div align="center"><p><a href="'.$url.'">'.$straddnew.'</a></p></div>';
    
    }

    /**
     * Print pages index page.
     *
     * @uses $CFG
     * @uses $USER
     * @param int $menuid the currently edited menu
     * @param int $courseid the course the visit is originated from
     * @param int $courseid the current context for checking control permissions
     * @return void
     */
    function pages($menu, $courseid, $context) {
        global $CFG, $DB;

        // Prepare navigation to all menus in same context.
        $menus = $DB->get_records('local_cms_navi', array('course' => $menu->course), 'name');

        include_once($CFG->dirroot.'/local/cms/html/navimenu.php');
        include_once($CFG->dirroot.'/local/cms/html/pagesindex.php');
    }

    /**
     * Print add new page link
     *
     * @uses $USER
     * @param int $menuid
     * @param int $courseid
     * @return void
     */
    function addnewpage ($menuid, $courseid = 1) {

        $str = '';

        $menuid = clean_param($menuid, PARAM_INT);

        $url = new moodle_url('/local/cms/pageadd.php', array('id' => $menuid, 'sesskey' => sesskey(), 'course' => $courseid));
        $str .= '<p><a href="'.$url.'">';
        $str .= get_string('addnewpage', 'local_cms');
        $str .= '</a></p>';

        return $str;
    }

    function update_page_button($pageid, $context) {
        global $CFG, $USER;

        if (has_capability('local/cms:editpage', $context, $USER->id, true)) {
            $string = get_string('updatepage', 'local_cms');
            $url = new moodle_url('/local/cms/view.php', array('pid' => $pageid, 'edit' => 1, 'sesskey' => sesskey(), 'courseid' => $context->instanceid));
            return $this->single_button($url, $string);
        } else {
            return '';
        }
    }
}

/**
 * Makes all replacements in page body
 */
function replace_handler($matches) {
    global $PAGE, $COURSE, $SESSION;
    static $renderer = null;

    if ($COURSE->id > SITEID) {
        $context = context_course::instance($COURSE->id);
    } else {
        $context = context_system::instance();
    }

    if (is_null($renderer)) {
        // Optimization for numerous calls
        $renderer = $PAGE->get_renderer('local_cms');
    }

    $fullpattern = $matches[0];
    if (preg_match('#\[\[INCLUDE (.+?)\]\]#i', $fullpattern)) {
        return $renderer->safe_include("$matches[1]", true);
    } elseif (preg_match('#\[\[SCRIPT (.+?)\]\]#i', $fullpattern)) {
        return $renderer->include_page("$matches[1]", false);
    } elseif (preg_match('#\[\[PAGE (.+?)\]\]#i', $fullpattern)) {
        return $renderer->include_page("$matches[1]", $COURSE);
    } elseif (preg_match('#\[\[NEWS\]\]#i', $fullpattern)) {
        return $renderer->news($COURSE);
    } elseif (preg_match('#\[\[PRIVATE (.+?)\]\]#is', $fullpattern)) {
        return '';
    } elseif (preg_match('#\[\[TOC\]\]#i', $fullpattern)) {
        return $renderer->toc($SESSION->currentcmspage);
    } elseif (preg_match('#\[\[PARENT\]\]#i', $fullpattern)) {
        return $renderer->parent_link($SESSION->currentcmspage);
    } elseif (preg_match('#\[\[PARENT\|(.+?)\]\]#i', $fullpattern, $matches)) {
        return $renderer->parent_link($SESSION->currentcmspage, $matches[1]);
    } elseif (preg_match('#\[\[([^\[\]]+?)\s*\|\s*(.+?)\]\]#s', $fullpattern)) {
        return html_writer::link(@$matches[1] ,@$matches[2]);
    } elseif (preg_match('#\[\[(.+?)\]\]#s', $fullpattern, $matches)) {
        $pagename = @$matches[1];
        if ($page = cms_get_page_data($COURSE->id, $SESSION->currentcmsmenu, $pagename)) {
            $pageurl = new moodle_url('/local/cms/view.php', array('pid' => $page->id));
            return html_writer::link($pageurl, $pagename);
        } else {
            if (has_capability('local/cms:editpage', $context)) {
                $params = array('nid' => $SESSION->currentcmsmenu, 'course' => $COURSE->id, 'pagename' => $pagename, 'sesskey' => sesskey(), 'parentid' => $SESSION->currentcmspage);
                $addpageurl = new moodle_url('/local/cms/pageadd.php', $params);
                return '<span class="local-cms-not-exists">'.$pagename.'</span><a href="'.$addpageurl.'">?</a>';
            } else {
                return '<span class="local-cms-not-exists">'.$pagename.'</span>';
            }
        }
    } elseif (preg_match('#\._\.#i', $fullpattern)) {
        return '';
    }
    return '';
}
