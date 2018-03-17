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
 * popup window for reordering pages
 *
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/cms/locallib.php');

$sourceid = required_param('source', PARAM_FILE); // the page whoose siblings we want to show and which would be affected by any actions
$direction = optional_param('direction', '', PARAM_ALPHA); // up or down if page is to be moved up or down

if (!$source = $DB->get_record('local_cms_navi_data', array('pageid' => $sourceid))) {
    print_error('Page with id '.$sourceid.' does not exist');
}

if (!$navi = $DB->get_record('local_cms_navi', array('id' => $source->naviid))) {
    print_error('Source has invalid menu');
}

if (!$course = $DB->get_record('course', array('id' => $navi->course))) {
    print_error('coursemisconf');
}

// Security.

require_login($course->id);

/// Define context
if ($courseid == SITEID ) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

require_capability('local/cms:movepage', $context);

if ($direction) {
    // We want to reorder.
    cms_reorder($source->id, $source->parentid, $source->naviid, $direction);
}

$siblings = $DB->get_records_select('local_cms_navi_data', " parentid = ? AND naviid = ? ", array($source->parentid, $source->naviid), 'sortorder ASC');

$strmovepage = get_string('movepage', 'local_cms');

$url = new moodle_url('/local/cms/reorder.php');
$PAGE->set_url($url);
$PAGE->navbar->add($strcms.' '.$stradministration, $CFG->wwwroot.'/local/cms/index.php?course='.$course->id.'&amp;sesskey='.sesskey());
$PAGE->navbar->add($strmovepage);
$PAGE->set_context($context);
$PAGE->set_title($strmovepage);
$PAGE->set_heading($strmovepage);

echo $OUTPUT->header();

$first = true;
echo '<ol>';

foreach ($siblings as $sibling) {
    if ($first) {
        $uplink = '&nbsp;';
        $first = false;
    } else {
        $reorderurl = new moodle_url('/local/cms/reorder.php', array('source' => $sibling->pageid, 'direction' => 'up'));
        $uplink = '<a href="'.$reorderurl.'">'
                .$OUTPUT->pix_icon('t/up', get_string('up')).'</a> ';
    }
    echo '<li>'.$uplink.$sibling->title.'</li>';
}
echo '</ol>';

echo $OUTPUT->footer();
