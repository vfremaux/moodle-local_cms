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
 * This class takes care of page index output almost completely.
 *
 * @package CMS_plugin
 */
class cms_pages_menu {
    /**
    * Array container for pages
    * @var array $pages
    */
    public $pages = null;
    /**
    * Menu id
    * @var int $menuid
    */
    public $menuid = null;
    /**
    * Course id
    * @var int $courseid
    */
    public $courseid = null;
    /**
    * String holder for image up on pages index.
    * @var string $imgup
    */
    public $imgup = null;
    /**
    * String holder for image down on pages index.
    * @var string $imgdown
    */
    public $imgdown;
    /**
    * String holder for image right on pages index.
    * @var string $imgright
    */
    public $imgright;
    /**
    * String holder for image left on pages index.
    * @var string $imgleft
    */
    public $imgleft;
    /**
    * String holder for publish image on pages index.
    * @var string $imgpub
    */
    public $imgpub;
    /**
    * String holder for unpublish image on pages index.
    * @var string $imgunpub
    */
    public $imgunpub;
    /**
    * String holder for blank image on pages index.
    * @var string $imgblank
    */
    public $imgblank;
    /**
    * Language string for default page.
    * @var string $strisdefault
    */
    public $strisdefault;
    /**
    * Language string for set as default page.
    * @var string $strsetasdefault
    */
    public $strsetasdefault;
    /**
    * Language string for published.
    * @var string $strpublished
    */
    public $strpublished;
    /**
    * Language string for unpublished.
    * @var string $strunpublished
    */
    public $strunpublished;
    /**
    * Site container.
    * @var object $site
    */
    public $siteid;
    /**
    * wwwroot for internal use.
    * @var string $wwwroot
    */
    public $wwwroot;

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
        $this->strsetasdefault = get_string('setdefault', 'local_cms');
        $this->strpublished    = get_string('published', 'local_cms');
        $this->strunpublished  = get_string('unpublished', 'local_cms');
        $this->strembed    = get_string('isembed', 'local_cms');
        $this->strnoembed  = get_string('isnoembed', 'local_cms');
        $this->strmenu  = get_string('showinmenu', 'local_cms');
        $this->strinmenu  = get_string('inmenu', 'local_cms');
        $this->strnotinmenu  = get_string('notinmenu', 'local_cms');

        // Cache images. Pointless to initialize them in
        // methods every time.
        $this->imgup   = $OUTPUT->pix_icon('t/up', '');
        $this->imgdown = $OUTPUT->pix_icon('t/down', '');
        $this->imgright = $OUTPUT->pix_icon('t/right', '');
        $this->imgleft  = $OUTPUT->pix_icon('t/left', '');
        $this->imgpub  = $OUTPUT->pix_icon('yespublish', $this->strpublished, 'local_cms');
        $this->imgunpub = $OUTPUT->pix_icon('nopublish', $this->strunpublished, 'local_cms');
        $this->imgembed  = $OUTPUT->pix_icon('yespublish', $this->strembed, 'local_cms');
        $this->imgnoembed = $OUTPUT->pix_icon('nopublish', $this->strnoembed, 'local_cms');
        $this->imginmenu  = $OUTPUT->pix_icon('inmenu', $this->strinmenu, 'local_cms');
        $this->imgnotinmenu = $OUTPUT->pix_icon('notinmenu', $this->strnotinmenu, 'local_cms');
        $this->imgblank = $OUTPUT->pix_icon('blank', '', 'local_cms');

        $sql  = "
            SELECT
                n.pageid AS id,
                n.id as navidataid,
                n.naviid,
                n.pagename,
                n.title,
                n.isfp,
                n.parentid,
                n.url,
                n.target,
                n.showinmenu,
                n.embedded,
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
    function get_page_tree_rows($parentid) {
        static $output, $count, $prevpage;

        if ( empty($output) ) {
            $output = array();
        }
        if ( empty($count) ) {
            $count = 0;
        }

        if (!empty($this->pages)) {
            $count++;
            foreach ($this->pages as $p) {
                if ($p->parentid == $parentid) {
                    $row = array();

                    $row[] = '<input type="checkbox" name="id" value="'.$p->id .'" />';

                    $params = array('sesskey' => sesskey(),
                                    'sort' => 'up',
                                    'menuid' => $p->naviid,
                                    'pid' => $p->id,
                                    'mid' => $p->parentid,
                                    'course' => $this->courseid);
                    $url = new moodle_url('/local/cms/pages.php', $params);
                    $hrefup = '<a href="'.$url.'">'.$this->imgup .'</a>';

                    $params = array('sesskey' => sesskey(),
                                    'sort' => 'down',
                                    'menuid' => $p->naviid,
                                    'pid' => $p->id,
                                    'mid' => $p->parentid,
                                    'course' => $this->courseid);
                    $url = new moodle_url('/local/cms/pages.php', $params);
                    $hrefdown = '<a href="'.$url.'">'.$this->imgdown .'</a>';

                    $hrefleft = '';
                    if ( !empty($prevpage->id) or $this->__hasParent($p->id) ) {
                        $moveto = $this->__hasParent($p->parentid, true);
                        if ( empty($moveto) ) {
                            $moveto = '0';
                        }
                        $params = array('sesskey' => sesskey(),
                                        'move' => $moveto,
                                        'pid' => $p->id,
                                        'menuid' => $p->naviid,
                                        'course' => $this->courseid);
                        $url = new moodle_url('/local/cms/pages.php', $params);
                        $hrefleft = '<a href="'.$url.'" alt="">'. $this->imgleft .'</a>';
                    }

                    $hrefright = '';
                    if ( !empty($prevpage->id) ) {
                        $params = array('sesskey' => sesskey(),
                                        'move' => $prevpage->id,
                                        'pid' => $p->id,
                                        'menuid' => $p->naviid,
                                        'course' => $this->courseid);
                        $url = new moodle_url('/local/cms/pages.php', $params);
                        $hrefright  = '<a href="'.$url.'" alt="">'. $this->imgright .'</a>';
                    }

                    $moverow = '<table border="0" cellpadding="2"><tr>';

                    if ($this->__firstAtLevel($p->parentid, $p->id) &&
                         $this->__hasSibling($p->parentid)) {
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
                    if ($this->__hasParent($p->id)) {
                        $moverow .= '<td>'. $hrefleft .'</td>';
                    } else {
                        $moverow .= '<td>'. $this->imgblank .'</td>';
                    }
                    if ($this->__hasSibling($p->parentid) && !$this->__firstAtLevel($p->parentid, $p->id)) {
                        $moverow .= '<td>'. $hrefright .'</td>';
                    }

                    $row[] = $moverow .'</tr></table>';

                    $pageurl = '';
                    if (!empty($this->siteid)) {
                        $pageurl = new moodle_url('/local/cms/view.php', array('pid' => $p->id));
                    }

                    // If link is a direct url to resource or webpage
                    if (!empty($p->url)) {
                        $pageurl = $p->url;
                    }

                    $p->title  = '<a href="'. $pageurl .'" target="_blank">'.format_string($p->title).'</a>';
                    $pagetitle  = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $count - 1);
                    $pagetitle .= !empty($p->isfp) ? '<strong>'. $p->title .'</strong>' : $p->title;
                    $row[] = $pagetitle;

                    $url = new moodle_url('/local/cms/pages.php', array('course' => $this->courseid, 'sesskey' => sesskey(), 'setfp' => $p->id));
                    $default = !empty($p->isfp) ? $this->strisdefault :
                               ((!empty($p->publish) && empty($p->parentid)) ?
                               '<a href="'.$url.'">'. $this->strsetasdefault.'</a>' : '');
                    $row[] = $default;

                    $params = array('course' => $this->courseid, 'sesskey' => sesskey(), 'pid' => $p->id, 'menuid' => $p->naviid);
                    $url = new moodle_url('/local/cms/pages.php', $params);

                    $embed = $p->embedded;
                    if (!$embed) {
                        /*
                        $url->param('publish', 'yes');
                        $link = '<a href="'.$url.'">'. $this->imgunpub .'</a>';
                        */
                        $link = $this->imgnoembed;
                    } else {
                        /*
                        $url->param('publish', 'no');
                        $link = '<a href="'.$url.'">'. $this->imgpub .'</a>';
                        */
                        $link = $this->imgembed;
                    }
                    $row[] = $link;

                    if (empty($p->publish)) {
                        $url->param('publish', 'yes');
                        $publishlink = '<a href="'.$url.'">'. $this->imgunpub .'</a>';
                    } else {
                        $url->param('publish', 'no');
                        $publishlink = '<a href="'.$url.'">'. $this->imgpub .'</a>';
                    }
                    $row[] = $publishlink;

                    $url = new moodle_url('/local/cms/pages.php', $params);
                    if (empty($p->showinmenu)) {
                        $url->param('showinmenu', 'yes');
                        $menulink = '<a href="'.$url.'">'. $this->imgnotinmenu .'</a>';
                    } else {
                        $url->param('showinmenu', 'no');
                        $menulink = '<a href="'.$url.'">'. $this->imginmenu .'</a>';
                    }
                    $row[] = $menulink;

                    // Get version information.
                    $version = cms_get_page_version($p->id);
                    $params = array('sesskey' => sesskey(), 'course' => $this->courseid, 'menuid' => $p->naviid, 'pageid' => $p->id);
                    $url = new moodle_url('/local/cms/pagehistory.php', $params);
                    $historylink = '<a href="'.$url.'">' . s($version) .'</a>';
                    $row[] = $historylink; //s($version);
                    $row[] = userdate($p->modified, "%x %X");

                    array_push($output, $row);
                    $this->get_page_tree_rows($p->navidataid);
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
