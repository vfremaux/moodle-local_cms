<?php // $Id: cmslocallib.php,v 1.13 2008/03/23 09:11:37 julmis Exp $
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
 * local librairies
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/local/cms/lib.php');

/**
* Print menu selection list.
*
* @param int $courseid
* @return void
*/
function cms_print_menus ($courseid = 1) {

    cms_print_newmenulink($courseid);
    cms_print_allmenus($courseid);

}

/**
* Print all menus.
*
* @uses $CFG
* @uses $USER
* @param int $courseid
* @return void
*/
function cms_print_allmenus($courseid = 1) {
    global $CFG, $USER, $DB;
    
    $menus = $DB->get_records('local_cms_navi', array('course' => $courseid));

    if (is_array($menus)) {
        include_once($CFG->dirroot.'/local/cms/html/printmenus.php');
    } else {
        echo '<div align="center">';
        echo '<p>';
        echo get_string('nomenus', 'local_cms');
        echo '</p></div>';
    }
}

/**
* Print Add new link into menu administration page.
*
* @uses $USER
* @param int $courseid
* @return void
*/
function cms_print_newmenulink($courseid = 1) {
    global $CFG;
    
    $straddnew = get_string('addnewmenu','local_cms');

    echo '<div align="center"><p><a href="'.$CFG->wwwroot.'/local/cms/menuadd.php?course='.$courseid.'&amp;';
    echo 'sesskey='.sesskey().'">'.$straddnew.'</a></p></div>';

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
function cms_print_pages($menu, $courseid, $context) {
    global $CFG, $DB;

    // prepare navigation to all menus in same context
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
function cms_print_addnewpage ($menuid, $courseid=1) {
    global $USER, $CFG;

    $menuid = clean_param($menuid, PARAM_INT);

    echo '<p><a href="'.$CFG->wwwroot.'/local/cms/pageadd.php?id='.$menuid.'&amp;sesskey='.$sesskey().'&amp;course='.$courseid.'">';
    echo get_string('addnewpage', 'local_cms');
    echo '</a></p>';

}

/**
* Get navigation data for page index.
*
* @uses $CFG
* @uses $USER
* @param int $parentid
* @param int $menuid
* @return array
*/
function cms_get_navi($parentid, $menuid=1) {
    global $CFG, $DB;

    $menuid   = intval($menuid);
    $parentid = intval($parentid);

    $sql  = "
        SELECT 
            n.*, 
            p.publish,
            cn.requirelogin 
        FROM 
            {local_cms_navi_data} n,
            {local_cms_pages} p,
            {local_cms_navi} cn
        WHERE 
            n.pageid = p.id AND 
            p.publish = 1 AND 
            n.naviid = ? AND 
            cn.id = ? AND 
            n.parentid = ?
    ";

    return $DB->get_records_sql($sql, array($menuid, $menuid, $parentid));
}

/**
* Get all possible parents in the same menu.
* Eliminating own childs
*
* @uses $CFG
* @uses $USER
* @param int $parentid
* @param int $menuid
* @return array
*/
function cms_get_possible_parents($menuid, $navidataid) {
    global $CFG, $DB;
    
    if($navidataid){
    
	    $list = '';
	    if ($children = cms_get_children_ids($navidataid)){
	    	$list = implode("','", array_values($children));
	    }
	    
	    return $DB->get_records_select('local_cms_navi_data', " naviid = ? AND id NOT IN ('{$list}') AND id != $navidataid ", array($menuid));

	} else {

	    return $DB->get_records('local_cms_navi_data', array('naviid' => $menuid));

	}
}

/**
* Get all page data.
*
* @uses $CFG
* @param int $pageid
* @return object
*/
function cms_get_pagedata($pageid) {
    global $CFG, $DB;

    $pageid = clean_param($pageid, PARAM_INT);

    $sql  = "
        SELECT 
            p.*, 
            n.title, 
            n.showinmenu, 
            n.id AS nid, 
            n.naviid, 
            n.parentid, 
            n.url, 
            n.target, 
            n.pagename, 
            n.showblocks 
        FROM 
            {local_cms_pages} p, 
            {local_cms_navi_data} n 
        WHERE 
            n.pageid = p.id AND 
            n.pageid = ?
    ";

    if ($data = $DB->get_record_sql($sql, array($pageid))) {
        $data->parentname = $DB->get_field('local_cms_navi_data', 'pagename', array('pageid' => $data->parentid));
    }

    return $data;
}

/**
* Resets menu order of selected menu.
*
* @global object $CFG
* @staticvar int $count
* @param int $parentid
* @param int $menuid
* @return bool
*/
function cms_reset_menu_order ($parentid, $menuid) {

    global $CFG;
    static $count;

    $parentid = intval($parentid);
    $menuid   = intval($menuid);

    if ( empty($count) ) {
        $count = 0;
    }

    $sql = "
        SELECT 
            id, 
            pageid, 
            parentid
        FROM 
            {local_cms_navi_data}
        WHERE 
            parentid = ? AND 
            naviid = ?
    ";

    $pages = $DB->get_records_sql($sql, array($parentid, $menuid));

    if (! empty($pages) ) {
        $count++;
        $pagecount = 1;
        foreach ($pages as $page) {
            $sortorder = (1000 * $count) + $pagecount;
            $DB->set_field('local_cms_navi_data', 'sortorder', $sortorder, array('pageid' => intval($page->pageid)));
            cms_reset_menu_order($page->pageid, $menuid);
            $pagecount++;
        }
        $count--;
    }

    return true;
}

/**
* This class takes care of page index output almost completely.
*
* @package CMS_plugin
*/
class cms_pages_menu {
    /**
    * Array container for pages
    * @var array $pages
    */
    var $pages = NULL;
    /**
    * Menu id
    * @var int $menuid
    */
    var $menuid = NULL;
    /**
    * Course id
    * @var int $courseid
    */
    var $courseid = NULL;
    /**
    * String holder for image up on pages index.
    * @var string $imgup
    */
    var $imgup = NULL;
    /**
    * String holder for image down on pages index.
    * @var string $imgdown
    */
    var $imgdown;
    /**
    * String holder for image right on pages index.
    * @var string $imgright
    */
    var $imgright;
    /**
    * String holder for image left on pages index.
    * @var string $imgleft
    */
    var $imgleft;
    /**
    * String holder for publish image on pages index.
    * @var string $imgpub
    */
    var $imgpub;
    /**
    * String holder for unpublish image on pages index.
    * @var string $imgunpub
    */
    var $imgunpub;
    /**
    * String holder for blank image on pages index.
    * @var string $imgblank
    */
    var $imgblank;
    /**
    * Language string for default page.
    * @var string $strisdefault
    */
    var $strisdefault;
    /**
    * Language string for set as default page.
    * @var string $strsetasdefault
    */
    var $strsetasdefault;
    /**
    * Language string for published.
    * @var string $strpublished
    */
    var $strpublished;
    /**
    * Language string for unpublished.
    * @var string $strunpublished
    */
    var $strunpublished;
    /**
    * Site container.
    * @var object $site
    */
    var $siteid;
    /**
    * wwwroot for internal use.
    * @var string $wwwroot
    */
    var $wwwroot;

    /**
    * Constructor sets up needed variables and
    * fetch pages information from database.
    *
    * @uses $CFG
    * @uses $USER
    * @param int $menuid
    * @param int $courseid
    */
    function __construct($menuid, $courseid = 1) {
        global $CFG, $USER, $DB, $OUTPUT;
        
        $this->menuid = clean_param($menuid, PARAM_INT);
        $this->courseid = clean_param($courseid, PARAM_INT);

        // Get strings
        $this->strisdefault    = get_string('isdefaultpage', 'local_cms');
        $this->strsetasdefault = get_string('setdefault'   , 'local_cms');
        $this->strpublished    = get_string('published'    , 'local_cms');
        $this->strunpublished  = get_string('unpublished'  , 'local_cms');

        // Cache images. Pointless to initialize them in
        // methods every time.
        $this->imgup   = '<img src="'. $OUTPUT->pix_url('t/up').'" width="11" height="11" alt="" border="0" />';
        $this->imgdown = '<img src="'. $OUTPUT->pix_url('t/down').'" width="11" height="11" alt="" border="0" />';
        $this->imgright = '<img src="'. $OUTPUT->pix_url('t/right').'" width="11" height="11" alt="" border="0" />';
        $this->imgleft  = '<img src="'. $OUTPUT->pix_url('t/left').'" width="11" height="11" alt="" border="0" />';
        $this->imgpub  = '<img src="'. $OUTPUT->pix_url('yespublish', 'local_cms').'" alt="' .
                         stripslashes($this->strpublished) .'" title="' .
                         stripslashes($this->strpublished) .'" />';
        $this->imgunpub = '<img src="'. $OUTPUT->pix_url('nopublish', 'local_cms').'" alt="' .
                          stripslashes($this->strunpublished) .'" title="' .
                          stripslashes($this->strunpublished) .'" />';
        $this->imgblank = '<img src="'. $OUTPUT->pix_url('blank', 'local_cms').'" width="11" height="11" alt="" />';

        $sql  = "
            SELECT 
                n.pageid AS id, 
                n.naviid, 
                n.pagename, 
                n.title, 
                n.isfp,
                n.parentid,
                n.url, 
                n.target,
                p.publish, 
                p.created, 
                p.modified
            FROM 
                {local_cms_navi_data} n,
                {local_cms_pages} p 
            WHERE 
                n.pageid = p.id AND
                n.naviid = ? 
            ORDER BY 
                n.sortorder
        ";

        $this->pages = $DB->get_records_sql($sql, array($this->menuid));
        $this->siteid = SITEID;
        $this->wwwroot = $CFG->wwwroot;
        $this->path = array();
        $this->tmparray = array();

        if ($this->pages) {
            foreach ( $this->pages as $page ) {
                $this->tmparray[$page->parentid][] = $page->id;
            }
        }
    }

    /**
    * Check if current page has parent page. For internal use only.
    * @param int $pageid
    * @param bool $returnid
    * @return mixed Returns parent page id if enable or true/false
    */
    function __hasParent($pageid, $returnid = FALSE) {

        $pageid = intval($pageid);

        if ( !empty($this->pages[$pageid]) ) {
            $page = $this->pages[$pageid];
                if ( $page->parentid != 0 ) {
                if ( !$returnid ) {
                    return true;
                } else {
                    // return first item.
                    return (int) $page->parentid;
                }
            }
        }
        return false;
    }

    /**
    * Check if current page has child page. For internal use only.
    * @param int $pageid
    * @param bool $returnid
    * @return mixed Returns child page id if enable or true/false
    */
    function __hasChildren($pageid, $returnid = FALSE) {

        $pageid = intval($pageid);

        if ( !empty($this->tmparray[$pageid]) ) {
            if ( !$returnid ) {
                return true;
            } else {
                // return first item in array.
                return (int) $this->tmparray[$pageid][0];
            }
        }

        return false;
    }

    /**
    * Check if current page has sibling page. For internal use only.
    * @param int $parentid
    * @return bool
    */
    function __hasSibling($parentid) {

        $parentid = intval($parentid);
        if ( !empty($this->tmparray[$parentid]) ) {
                    return true;
                }

        return false;
    }

    /**
    * Check if current page is the first page in current level.
    * @param int $parentid
    * @param int $pageid
    * @return bool
    */
    function __firstAtLevel ( $parentid, $pageid ) {

        $pageid = intval($pageid);
        $parentid = intval($parentid);

        if ( !empty($this->tmparray[$parentid]) ) {
            $first = array_shift($this->tmparray[$parentid]);
            array_unshift($this->tmparray[$parentid], $first);
            if ( $first == $pageid ) {
                        return true;
                    }
                }

        return false;

    }

    /**
    * Check if current page is the last page in current level.
    * @param int $parentid
    * @param int $pageid
    * @return bool
    */
    function __lastAtLevel($parentid, $pageid) {

        $pageid = intval($pageid);
        $parentid = intval($parentid);

        if ( !empty($this->tmparray[$parentid]) ) {
            if ( end($this->tmparray[$parentid]) == $pageid ) {
                return true;
            }
        }
        return false;
    }

    /**
    * Construct data for table class used in pagesindex page.
    *
    * @uses $USER
    * @staticvar array $output
    * @staticvar int $count
    * @staticvar object $prevpage
    * @param int $parentid
    * @return array
    */
    function get_page_tree_rows($parentid){

        static $output, $count, $prevpage;

        if ( empty($output) ) {
            $output = array();
        }
        if ( empty($count) ) {
            $count = 0;
        }

        if ( !empty($this->pages) ) {
            $count++;
            foreach ( $this->pages as $p ) {
                if ( $p->parentid == $parentid ) {
                    $row = array();

                    $row[] = '<input type="checkbox" name="id" value="'.$p->id .'" />';

                    $hrefup = '<a href="pages.php?sesskey='.sesskey().
                          '&amp;sort=up&amp;menuid='. $p->naviid . '&amp;pid='. $p->id .
                          '&amp;mid='. $p->parentid .'&amp;course='. $this->courseid .'">'.
                          $this->imgup .'</a>';

                    $hrefdown = '<a href="pages.php?sesskey='.sesskey().
                            '&amp;sort=down&amp;menuid='. $p->naviid .'&amp;pid='. $p->id .
                            '&amp;mid='. $p->parentid .'&amp;course='. $this->courseid .'">'.
                            $this->imgdown .'</a>';

                    $hrefleft = '';
                    if ( !empty($prevpage->id) or $this->__hasParent($p->id) ) {
                        $moveto = $this->__hasParent($p->parentid, true);
                        if ( empty($moveto) ) {
                            $moveto = '0';
                        }
                        $hrefleft = '<a href="pages.php?sesskey='.sesskey().
                                    '&amp;move='. $moveto .'&amp;pid='. $p->id .
                                    '&amp;menuid='. $p->naviid .'&amp;course='.
                                    $this->courseid .'" alt="">'. $this->imgleft .'</a>';
                    }

                    $hrefright = '';
                    if ( !empty($prevpage->id) ) {
                        $hrefright  = '<a href="pages.php?sesskey='.sesskey().
                                      '&amp;move='. $prevpage->id .'&amp;pid='. $p->id .
                                      '&amp;menuid='. $p->naviid .'&amp;course='.
                                      $this->courseid .'" alt="">'. $this->imgright .'</a>';
                    }

                    $moverow = '<table border="0" cellpadding="2"><tr>';

                    if ( $this->__firstAtLevel($p->parentid, $p->id) &&
                         $this->__hasSibling($p->parentid) ) {
                        $moverow .= '<td>'. $hrefdown .'</td><td>'. $this->imgblank .'</td>';
                    } else if ( $this->__lastAtLevel($p->parentid, $p->id) &&
                                $this->__hasSibling($p->parentid) ) {
                        $moverow .= '<td>'. $this->imgblank .'</td><td>'. $hrefup .'</td>';
                    } else if ( $this->__hasSibling($p->parentid) ) {
                        $moverow .= '<td>'.$hrefdown .'</td><td>'. $hrefup .'</td>';
                    } else {
                        $moverow .= '<td>'.$this->imgblank .'</td><td>'. $this->imgblank .'</td>';
                    }

                    // Add level changers.
                    if ( $this->__hasParent($p->id) ) {
                        $moverow .= '<td>'. $hrefleft .'</td>';
                    } else {
                        $moverow .= '<td>'. $this->imgblank .'</td>';
                    }
                    if ( $this->__hasSibling($p->parentid) && !$this->__firstAtLevel($p->parentid, $p->id) ) {
                        $moverow .= '<td>'. $hrefright .'</td>';
                    }

                    $row[] = $moverow .'</tr></table>';

                    $pageurl = '';
                    if ( !empty($this->siteid) ) {
/*                        $pageurl = ($this->courseid > $this->siteid) ?
                                   $this->wwwroot .'/course/view.php?id='. $this->courseid .
                                   '&amp;pid='. $this->__get_path($p->id) :
                                   $this->wwwroot .'/index.php?pid='. $this->__get_path($p->id);
*/
                        $pageurl = $this->wwwroot .'/index.php?pid='. $p->id;
                    }

                    // If link is a direct url to resource or webpage
                    if ( !empty($p->url) ) {
                        $pageurl = $p->url;
                    }

                    $p->title  = '<a href="'. $pageurl .'" target="_blank">'.format_string($p->title).'</a>';
                    $pagetitle  = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $count - 1);
                    $pagetitle .= !empty($p->isfp) ? '<strong>'. $p->title .'</strong>' : $p->title;
                    $row[] = $pagetitle;

                    $default = !empty($p->isfp) ? $this->strisdefault :
                               ((!empty($p->publish) && empty($p->parentid)) ?
                               '<a href="pages.php?course='. $this->courseid .'&amp;sesskey='.sesskey().
                               '&amp;setfp='. $p->id .'">'. $this->strsetasdefault.'</a>' : '');
                    $row[] = $default;

                    $publishurl = '<a href="pages.php?sesskey='.sesskey().
                                  '&amp;pid='. $p->id .'&amp;menuid='. $p->naviid .
                                  '&amp;course='. $this->courseid;
                    $publish = !empty($p->publish) ? $publishurl .'&amp;publish=no">'. $this->imgpub .'</a>'
                                                   : $publishurl .'&amp;publish=yes">'. $this->imgunpub .'</a>';
                    $row[] = $publish;

                    // Get version information.
                    $version = cms_get_page_version($p->id);
                    $historylink = '<a href="pagehistory.php?sesskey='.sesskey().'&amp;course='.
                                   $this->courseid .'&amp;menuid='. $p->naviid .'&amp;pageid='. $p->id .
                                   '">' . s($version) .'</a>';
                    $row[] = $historylink; //s($version);
                    $row[] = userdate($p->modified, "%x %X");

                    array_push($output, $row);
                    $this->get_page_tree_rows ($p->id);
                    $prevpage = $p;
                }
            }
            $count--;
        }

        return $output;
    }

    /**
    * Create path string from page ids like 2,3,4
    * @param int $pageid
    * @return string
    */
    function __get_path($pageid) {

        $pagearray = array();
        array_push($pagearray, $pageid);
        while ( $pageid = $this->__hasParent($pageid, true) ) {
            array_push($pagearray,$pageid);
        }
        return implode(",", array_reverse($pagearray));
    }
}

/**
* Get child page ids of selected page.
*
* @param int $parentid
* @return array An array of ids.
*/
function cms_get_children_ids($parentid) {
	global $DB;
    static $childrenids;

    $parentid = intval($parentid);

    if ( empty($childrenids) ) {
        $childrenids = array();
    }

    if ( $children = $DB->get_records('local_cms_navi_data', array('parentid' =>  $parentid)) ) {
        foreach ( $children as $child ) {
            array_push($childrenids, intval($child->pageid));
            cms_get_children_ids($child->pageid);
        }
    }
    return $childrenids;
}

/**
* Include necessary javascript scripts into editpage form
* @uses $CFG
* @return void
*/
function include_webfx_scripts () {
    global $CFG;

    echo "\n";
    ?>
    <script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cms/js/tabpane/local/webfxlayout.js"></script>
    <link type="text/css" rel="stylesheet" href="<?php echo $CFG->wwwroot ?>/local/cms/js/tabpane/css/luna/tab.css" />
    <script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/local/cms/js/tabpane/js/tabpane.js"></script>
    <?php
    echo "\n";
}

/**
* Get version information for selected page. Information
* can be a single string or an object.
*
* @uses $CFG, $DB
* @param int $pageid
* @param bool $object Return data as a string or object.
* @return mixed Returns true/false or an object
*/
function cms_get_page_version ($pageid) {
    global $CFG, $DB;

    $sql = "
        SELECT 
            MAX(version)
        FROM 
            {local_cms_pages_history}
        WHERE 
            pageid = ?
        ORDER BY 
            id DESC
    ";

    $version = $DB->get_field_sql($sql, array($pageid));

    if (empty($version)) {
        return '1.0';
    }

    return $version;
}

/**
* Reorder pages
* @uses $CFG, $DB
* @param int $id Page id.
* @param int $parent Parent id.
* @param int $menuid Menu id.
* @param string $direction.
* @return bool
*/
function cms_reorder($id, $parent, $menuid, $direction) {
    global $CFG, $DB;

    $sql  = "
        SELECT 
            id, 
            pageid, 
            parentid 
        FROM 
            {local_cms_navi_data}
        WHERE 
            parentid = ? AND 
            naviid = ?
        ORDER BY 
            sortorder
    ";

    if (! ($results = $DB->get_records_sql($sql, array($parent, $menuid))) ) {
        return false;
    }

    $records = array();
    $tmp     = array();

    $i = 0;
    foreach ($results as $row) {
        $records[$i]['id'] = intval($row->pageid);
        $records[$i]['sortorder'] = intval($i + 1);
        array_push($tmp, $records[$i]);
        $i++;
    }
    unset($results, $i, $row);

    $rows = intval(count($records));

    for ($i = 0; $i < $rows; $i++) {

        if ( $tmp[$i]['id'] == $id ) {
            // Check direction and can we move up?
            switch (strtolower($direction)) {
                case 'up':
                if ($i != 0) {
                    $tmp[$i]['sortorder'] -= 1;
                    $tmp[$i - 1]['sortorder'] += 1;
                }
                break;
                case 'down':
                if ($i < ($rows - 1)) {
                    $tmp[$i]['sortorder'] += 1;
                    $tmp[$i + 1]['sortorder'] -= 1;
                }
                break;
            }

        }
    }

    // Update menu table

    foreach ($tmp as $record) {
        if (! $DB->set_field('local_cms_navi_data', 'sortorder', $record['sortorder'], array('pageid' => $record['id']))) {
            return false;
        }
    }
    return true;
}

/**
 * Check if given name already exists at same course level.
 *
 * @uses $CFG, $DB
 * @param string $pagename Page name.
 * @param int $naviid Menu id.
 * @param int $courseid Course or site id.
 * @return bool
 */
function cms_pagename_exists ($pagename, $courseid) {
    global $CFG, $DB;

    $sql = "
        SELECT 
            nd.id, 
            nd.pagename
        FROM
            {local_cms_navi_data} nd
        LEFT JOIN
            {local_cms_navi} c
        ON 
            nd.naviid = c.id
        WHERE 
            nd.pagename = ? AND 
            c.course = ?
    ";
    return $DB->record_exists_sql($sql, array($pagename, $courseid));
}

