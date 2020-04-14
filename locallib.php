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

require_once($CFG->dirroot.'/local/cms/classes/cms_pages_menu.class.php');

/**
 * local librairies
 *
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/local/cms/lib.php');

function cms_get_page_data_by_id($courseidfoo, $pageid) {
    global $CFG, $DB;

    // Fetch pagedata from the database

    if (intval($pageid) != $pageid) {
        return false;
    }

    $sql = "
            SELECT
                p.id,
                p.body,
                p.modified,
                p.publish,
                nd.isfp,
                nd.parentid,
                nd.naviid,
                nd.title,
                nd.pagename,
                nd.showblocks,
                nd.embedded,
                n.requirelogin,
                n.allowguest,
                n.printdate,
                n.course
            FROM
                {local_cms_pages} p
            INNER JOIN {local_cms_navi_data} nd ON p.id = nd.pageid
            LEFT JOIN {local_cms_navi} n ON nd.naviid = n.id
            WHERE
                p.id = ?
    ";

    $pagedata = $DB->get_record_sql($sql, array($pageid));
    if (empty($pagedata)) {
        return '<p>'. get_string('nocontent', 'local_cms') .'</p>';
    }
    return $pagedata;
}

/**
 * Get complete page from some page name in a menu. This function should deprecate
 * all previous functions including course format. the name must be unique in 
 * manu scope and also in a course scope.
 *
 * @param int $courseid A course scope.
 * @param int $naviid A menu id.
 * @param mixed $pagename Page name or page id.
 * @return string
 */
function cms_get_page_data($courseid = 0, $naviid = 0, $pagename = '') {
    global $CFG, $DB;

    $pagename = urldecode($pagename);

    $params = array();
    if (!empty($pagename)) {
        $whereclause = "nd.pagename = ?";
        $params[] = $pagename;
    } else {
        $whereclause = "nd.isfp = '1'";
    }

    $scopeclause = '';
    if ($naviid) {
        // Menu scope will superseede course scope.
        $scopeclause = ' AND nd.naviid = ?';
        $params[] = $naviid;
    } elseif ($courseid) {
        $scopeclause = 'AND n.course = ?';
        $params[] = $courseid;
    }

    $sql = "
        SELECT
            p.id,
            p.body,
            p.modified,
            p.lastuserid,
            nd.isfp,
            nd.parentid,
            nd.id as navidataid,
            nd.naviid as nid,
            nd.title,
            nd.pagename,
            nd.showblocks,
            nd.embedded,
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
            $scopeclause
    ";
    $pagedata = $DB->get_record_sql($sql, $params);
    return $pagedata;
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

/**
 * Get navigation data for page index.
 *
 * @uses $CFG
 * @uses $USER
 * @param int $parentid
 * @param int $menuid
 * @return array
 */
function cms_get_navi($parentid, $menuid = 1) {
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

    if ($navidataid) {

        $list = '';
        if ($children = cms_get_children_ids($navidataid)) {
            $list = implode("','", array_values($children));
        }

        return $DB->get_records_select('local_cms_navi_data', " naviid = ? AND id NOT IN ('{$list}') AND id != $navidataid ", array($menuid));

    } else {

        return $DB->get_records('local_cms_navi_data', array('naviid' => $menuid));

    }
}

/**
 * Get all page data. DEPRECATED, use cms_get_page_data()
 *
 * @uses $CFG
 * @param int $pageid
 * @return object
 */
function cms_get_page_data_from_id($pageid) {
    global $CFG, $DB;

    $pageid = clean_param($pageid, PARAM_INT);

    $sql  = "
        SELECT
            p.*,
            n.title,
            n.showinmenu,
            n.id AS navidataid,
            n.naviid as nid,
            n.parentid,
            n.url,
            n.target,
            n.isfp,
            n.pagename,
            m.requirelogin,
            m.allowguest,
            n.showblocks,
            n.embedded,
            m.course
        FROM
            {local_cms_pages} p,
            {local_cms_navi_data} n,
            {local_cms_navi} m
        WHERE
            m.id = n.naviid AND
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
 * Get child page ids of selected page.
 *
 * @param int $parentid
 * @return array An array of ids.
 */
function cms_get_children_ids($parentid) {
    global $DB;
    static $childrenids;

    $parentid = intval($parentid);

    if (empty($childrenids)) {
        $childrenids = array();
    }

    if ($children = $DB->get_records('local_cms_navi_data', array('parentid' =>  $parentid))) {
        foreach ($children as $child) {
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

    if (!($results = $DB->get_records_sql($sql, array($parent, $menuid)))) {
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

function local_cms_add_nav($pagedata) {
    global $PAGE, $DB;

    $thisid = $pagedata->id;

    $pagedatas[] = $pagedata;
    while ($pagedata->parentid) {
        $pagedata = $DB->get_record('local_cms_navi_data', array('id' => $pagedata->parentid));
        $pagedatas[] = $pagedata;
    }

    if (!empty($pagedatas)) {
        $pagedatasrev = array_reverse($pagedatas);

        foreach ($pagedatasrev as $pagedata) {
            if ($thisid == $pagedata->id) {
                $PAGE->navbar->add($pagedata->title);
            } else {
                $PAGE->navbar->add($pagedata->title, new moodle_url('/local/cms/view.php', array('pid' => $pagedata->id)));
            }
        }
    }
}

function cms_get_visible_pages($menuid) {
    global $DB;

    $sql = "
        SELECT
            nd.id,
            nd.parentid,
            nd.title,
            nd.isfp,
            nd.pagename,
            nd.url,
            nd.target,
            p.publish,
            n.requirelogin,
            n.course
        FROM
            {local_cms_navi_data} nd,
            {local_cms_pages} p,
            {local_cms_navi} n
        WHERE
            nd.pageid = p.id AND
            p.publish = 1 AND
            nd.naviid = n.id AND
            (n.id = ?) AND
            nd.showinmenu = '1'
        ORDER BY
            sortorder
    ";
    $pages = $DB->get_records_sql($sql, array($menuid));
    return $pages;
}

/**
 * Extracts file argument either from file parameter or PATH_INFO
 *
 * Note: $scriptname parameter is not needed anymore
 *
 * @return string file path (only safe characters)
 */
function cms_get_file_argument() {
    global $SCRIPT;

    $relativepath = false;
    $hasforcedslashargs = false;

    if (!$hasforcedslashargs) {
        $relativepath = optional_param('file', false, PARAM_TEXT);
    }

    if ($relativepath !== false and $relativepath !== '') {
        return $relativepath;
    }
    $relativepath = false;

    // Then try extract file from the slasharguments.
    if (stripos($_SERVER['SERVER_SOFTWARE'], 'iis') !== false) {
        // NOTE: IIS tends to convert all file paths to single byte DOS encoding,
        //       we can not use other methods because they break unicode chars,
        //       the only ways are to use URL rewriting
        //       OR
        //       to properly set the 'FastCGIUtf8ServerVariables' registry key.
        if (isset($_SERVER['PATH_INFO']) and $_SERVER['PATH_INFO'] !== '') {
            // Check that PATH_INFO works == must not contain the script name.
            if (strpos($_SERVER['PATH_INFO'], $SCRIPT) === false) {
                $relativepath = clean_param(urldecode($_SERVER['PATH_INFO']), PARAM_TEXT);
            }
        }
    } else {
        // All other apache-like servers depend on PATH_INFO.
        if (isset($_SERVER['PATH_INFO'])) {
            if (isset($_SERVER['SCRIPT_NAME']) and strpos($_SERVER['PATH_INFO'], $_SERVER['SCRIPT_NAME']) === 0) {
                $relativepath = substr($_SERVER['PATH_INFO'], strlen($_SERVER['SCRIPT_NAME']));
            } else {
                $relativepath = $_SERVER['PATH_INFO'];
            }
            $relativepath = clean_param($relativepath, PARAM_TEXT);
        }
    }

    return $relativepath;
}
