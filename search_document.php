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
 * Global Search Engine for Moodle
 *
 * @package local_cms
 * @category search
 * @subpackage document_wrappers
 * @author Valery Fremaux [valery.fremaux@gmail.com] > 1.8
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * document handling for the cms module pages module
 * A CMS page can be indexed if it has been published into a block somewhere
 */

require_once($CFG->dirroot.'/local/search/documents/document.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');

define('X_SEARCH_TYPE_CMS', 'cms');

/**
 * a class for representing searchable information
 *
 */
class CMSPageSearchDocument extends SearchDocument {

    /**
    * constructor
    */
    public function __construct(&$page, $course_id, $context_id) {

        // generic information; required
        $doc = new StdClass;
        $doc->docid         = $page['id'];
        $doc->documenttype  = SEARCH_TYPE_CMS;
        $doc->itemtype      = 'page';
        $doc->contextid     = $context_id;

        // We cannot call userdate with relevant locale at indexing time.
        $doc->title         = $page['title'];
        $doc->date          = $page['created'];

        // Remove '(ip.ip.ip.ip)' from chat author list.
        $doc->author        = $page['lastuserid'];
        $doc->contents      = strip_tags($page['body']);
        $doc->url           = local_cms_make_link($page_id);

        // Module specific information; optional.
        $data = new StdClass;
        $data->cmspage      = $page_id;

        // Construct the parent class.
        parent::__construct($doc, $data, $course_id, 0, 0, 'local/'.X_SEARCH_TYPE_CMS);
    } 
}

/**
 * constructs a valid link to a page content
 * @param cm_id the chat course module
 * @param start the start time of the session
 * @param end th end time of the session
 * @uses CFG
 * @return a well formed link to session display
 */
function local_cms_make_link($page_id) {
    return new moodle_url('/local/cms/view.php', array('id' => $page_id));
}

/**
 * part of search engine API
 *
 */
function local_cms_iterator() {
    global $DB;

    $sql = "
        SELECT
            nd.*,
            p.body,
            p.created,
            p.modified,
            p.lastuserid,
            n.course
        FROM
            {local_cms_navi} n,
            {local_cms_navi_data} nd,
            {local_cms_page} p
        WHERE
            nd.pageid = p.id AND
            n.naviid = n.id;
    ";

    $pages = $DB->get_records_sql($sql);
    return $pages;
}

/**
 * part of search engine API
 *
 */
function local_cms_get_content_for_index(&$page) {
    global $DB;

    $documents = array();
    $course = $DB->get_record('course', array('id' => $page->course));

    if ($page->course) {
        $context = context_module::instance($page->course);
    } else {
        $context = context_system::instance();
    }

    $user = $DB->get_record('user', array('id' => $page->lastuserid));
    $page->authors = ($user) ? fullname($user) : '';
    $pagearr = get_object_vars($page);
    $documents[] = new CMSPageSearchDocument($pagearr, $page->id, $page->course, $context->id);
    return $documents;
}

/**
 * returns a single data search document based on a cms page id
 * @param itemtype the type of information (page is the only type)
 */
function local_cms_single_document($id, $itemtype) {
    global $DB;

    $course = $DB->get_record('course', array('id' => $page->course));

    if ($page->course) {
        $context = context_module::instance($page->course);
    } else {
        $context = context_system::instance();
    }

    $user = $DB->get_record('user', array('id' => $page->lastuserid));
    $page->authors = ($user) ? fullname($user) : '';
    $pagearr = get_object_vars($page);
    $document = new CMSPageSearchDocument($pagearr, $page->id, $page->course, $context->id);
    return $document;
}

/**
 * dummy delete function that packs id with itemtype.
 * this was here for a reason, but I can't remember it at the moment.
 */
function local_cms_delete($info, $itemtype) {
    $object->id = $info;
    $object->itemtype = $itemtype;
    return $object;
}

/**
 * returns the var names needed to build a sql query for addition/deletions
 * // TODO cms indexable records are virtual. Should proceed in a special way 
 */
function local_cms_db_names() {
    //[primary id], [table name], [time created field name], [time modified field name]
    return null;
}

/**
 * this function handles the access policy to contents indexed as searchable documents. If this 
 * function does not exist, the search engine assumes access is allowed.
 * When this point is reached, we already know that : 
 * - user is legitimate in the surrounding context
 * - user may be guest and guest access is allowed to the module
 * - the function may perform local checks within the module information logic
 * @param path the access path to the module script code
 * @param itemtype the information subclassing (usefull for complex modules, defaults to 'standard')
 * @param this_id the item id within the information class denoted by entry_type. In cms pages, this navi_data id 
 * @param user the user record denoting the user who searches
 * @param group_id the current group used by the user when searching
 * @uses CFG
 * @return true if access is allowed, false elsewhere
 */
function local_cms_check_text_access($path, $itemtype, $this_id, $user, $group_id_unused, $context_id_unused) {
    global $CFG, $DB;

    include_once("{$CFG->dirroot}/{$path}/lib.php");

    // get the chat session and all related stuff
    $page = cms_get_page_data_by_id($courseidfoo, $this_id);

    $indexcontext = $DB->get_record('context', array('id' => $context_id));

    if ($page->course) {
        $context = context_course::instance($page->course);
        if ($page->requirelogin) {
            // check for enrollment in course
            if (!is_enrolled($context, $user)) {
                return false;
            }
        }
    } else {
        $context = context_system::instance();
        if ($page->requirelogin && !isloggedin()) {
            return false;
        }

        if (!$page->allowguests && isguestuser()) {
            return false;
        }
    }

    if ($page->publish || has_any_capability(array('local/cms:editpage', 'local/cms:publishpage', 'local/cms:deletepage'), $context)) {
        return false;
    }

    return true;
}

/**
 * this call back is called when displaying the link for some last post processing
 *
 */
function local_cms_post_processing($title) {
    global $CFG;

    return $title;
}
