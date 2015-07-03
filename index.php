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
 * CMS administration index page for site/course level.
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$courseid = optional_param('course', SITEID, PARAM_INT);

// Check for valid admin user.

require_login($courseid);

if ( !$course = $DB->get_record('course', array('id' => $courseid)) ) {
    print_error('coursemisconf');
}

$url = new moodle_url('/local/cms/index.php', array('course' => $courseid));

// Get proper context for operations.

$contextinstance = null;
if ($courseid == SITEID) {
    $context = context_system::instance();
} else {
    $context = context_course::instance($course->id);
}

$stradministration = get_string('administration');
$strcms = get_string('cms', 'local_cms');
$strmanagepages = get_string('managepages', 'local_cms');
$strmanagemenus = get_string('managemenus', 'local_cms');

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->navbar->add($strcms.' '.$stradministration);
$PAGE->set_title($strcms);
$PAGE->set_heading($strcms);

echo $OUTPUT->header();
echo $OUTPUT->box_start("center", "100%", '', 20);

echo '<table class="generaltable" border="0" cellpadding="4" cellspacing="2" align="center">';
echo '<tr>';
echo '<td class="generaltablecell">';

echo $OUTPUT->heading_with_help($strcms . ' '. $stradministration, 'cms', 'local_cms', 'cms');

echo '<table border="0" cellpadding="4" cellspacing="2">';
echo '<tr>';
echo '<td align="center">';
if ( has_capability('local/cms:createmenu', $context, $USER->id) ) {
    $menusurl = new moodle_url('/local/cms/menus.php', array('course' => $courseid, 'sesskey' => sesskey()));
    echo '<a href="'.$menusurl.'">';
    echo '<img src="'.$OUTPUT->pix_url('menus', 'local_cms').'" width="50" height="50" alt="'.$strmanagemenus.'"
    title="'.$strmanagemenus.'" border="0" /></a><br />';
    echo '<a href="'.$menusurl.'">'.$strmanagemenus.'</a>';
} else {
    echo "&nbsp;";
}
        
echo '</td>';
echo '<td align="center">';
if ( has_capability('local/cms:publishpage', $context, $USER->id) or has_capability('local/cms:createpage', $context, $USER->id) ) {
    $pagesurl = new moodle_url('/local/cms/pages.php', array('course' => $courseid, 'sesskey' => sesskey()));
    echo '<a href="'.$pagesurl.'">';
    echo '<img src="'.$OUTPUT->pix_url('pages', 'local_cms').'" width="50" height="50" alt="'.$strmanagepages.'"
    title="'.$strmanagepages.'" /></a><br />';
    echo '<a href="'.$pagesurl.'">'.$strmanagepages.'</a>';
} else {
    echo "&nbsp;";
}

echo '</td>';
echo '</tr>';
echo '</table>';

echo '</td>';
echo '</tr>';
echo '</table>';

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
