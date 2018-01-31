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
 * page administration in a menu
 *
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once $CFG->dirroot.'/local/cms/locallib.php';

$id = optional_param('id', 0, PARAM_INT);
$menuid = optional_param('menuid', 1, PARAM_INT);    //
$courseid = optional_param('course', SITEID, PARAM_INT); // only for return and access check
$setfrontpage = optional_param('setfp', 0, PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('coursemisconf');
}

// Security.
confirm_sesskey();
require_login($courseid);

if (!$menu = $DB->get_record('local_cms_navi', array('id' => $menuid))) {
    redirect(new moodle_url('/local/cms/menus.php', array('course' => $courseid, 'sesskey' => sesskey())));
}

$contextinstance = null;
if ($menu->course == SITEID) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($COURSE->id);
}

require_capability('local/cms:manageview', $context);

$stradministration = get_string('administration');
$strcms = get_string('cms', 'local_cms');
$strpages = get_string('pages', 'local_cms');

$url = new moodle_url('/local/cms/pages.php', array('course' => $courseid, 'menuid' => $menuid));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->navbar->add($strcms.' '.$stradministration, new moodle_url('/local/cms/index.php', array('course' => $course->id, 'sesskey' => sesskey())));
$PAGE->navbar->add($strpages);

if ( !empty($setfrontpage) && has_capability('local/cms:editpage', $context) ) {

    $sql = "
        SELECT
            nd.id
        FROM
            {local_cms_navi_data} AS nd,
            {local_cms_navi} AS n
        WHERE
            nd.naviid = n.id AND
            n.id = ? AND
            nd.isfp = 1
    ";

    if (! $olddefault = $DB->get_field_sql($sql, array($menu->id))) {
        $DB->set_field('local_cms_navi_data', 'isfp', 1, array('id' => $setfrontpage));
    } else {
        $DB->set_field('local_cms_navi_data', 'isfp', 1, array('id' => $setfrontpage));
        $DB->set_field('local_cms_navi_data', 'isfp', 0, array('id' => $olddefault));
    }

    $strsuccess = get_string('defaultpagechanged', 'local_cms');
    redirect(new moodle_url('/local/cms/pages.php', array('course' => $course->id, 'sesskey' => $USER->sesskey)), $strsuccess, 2);

} else if ( !empty($_GET['add']) && has_capability('local/cms:createpage', $context) ) {

    $parentid = !empty($id) ? $id : 0;
    redirect(new moodle_url('/local/cms/pageadd.php', array('id' => $COURSE->id, 'nid' => $menuid, 'sesskey' => sesskey(), 'parentid' => $parentid, 'course' => $course->id)));

} else if (!empty($_GET['edit']) && has_capability('local/cms:editpage', $context)) {

    $id = required_param('id', PARAM_INT);
    redirect(new moodle_url('/local/cms/pageupdate.php', array('id' => $id, 'sesskey' => sesskey(), 'course' => $course->id)));

} else if (!empty($_GET['purge']) && has_capability('local/cms:deletepage', $context)) {

    $id = required_param('id', PARAM_INT);
    redirect(new moodle_url('/local/cms/pagedelete.php', array('id' => $id, 'sesskey' => sesskey(), 'course' => $course->id)));

}

// Sort.

$sort = optional_param('sort', '', PARAM_ALPHA);
$publish = optional_param('publish', '', PARAM_ALPHA);

if ( $sort && ($sort == 'up' or $sort == 'down') && has_capability('local/cms:movepage', $context) ) {

    $pageid    = required_param('pid', PARAM_INT);
    $parentid  = required_param('mid', PARAM_INT);
    $direction = required_param('sort', PARAM_ALPHA);

    if (! cms_reorder($pageid, $parentid, $menuid, $direction) ) {
        $strerr = "Couldn't reorder pages!";
    }
}

if ($publish && ($publish == 'yes' or $publish == 'no') && has_capability('local/cms:publishpage', $context) ) {
    $pageid = required_param('pid', PARAM_INT);
    $publish = ($publish != 'no') ? '1' : '0';

    $DB->set_field('local_cms_pages', 'publish', $publish, array('id' => $pageid));
}

if ( isset($_GET['move']) &&
     has_capability('local/cms:movepage', $context) ) {

    $pageid = required_param('pid', PARAM_INT);
    $move   = optional_param('move', '0', PARAM_INT);
    $DB->set_field('local_cms_navi_data', 'parentid', $move, array('pageid' => $pageid));

}

// Check if there is any menus builded.

if (!$DB->get_record('local_cms_navi', array('id' => $menuid))) {

    $strnomenusyet = get_string('nomenus', 'local_cms');
    redirect(new moodle_url('/local/cms/menus.php', array('course' => $courseid, 'sesskey' => sesskey())), $strnomenusyet, 2);
}

$renderer = $PAGE->get_renderer('local_cms');

// Start outputting page.

echo $OUTPUT->header();
echo $OUTPUT->box_start();
if (! empty($strerr) ) {
    echo $OUTPUT->notification($strerr);
} else {
    echo $OUTPUT->heading($stradministration);
}

echo $renderer->pages($menu, $course->id, $context);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
