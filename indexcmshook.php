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
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This is a special script for hooking the Moodle index page. 
 * It adds a sideway to standard index when subnavigating to 
 * a virtual cms subdir.
 */

// Pre defines if not initialized.

if (!defined('FRONTPAGECMS')) define ('FRONTPAGECMS', 52);

$cmsconfig = get_config('local_cms');
if (empty($cmsconfig->virtual_path)) {
    set_config('virtual_path', '/documentation', 'local_cms');
}

// capture pid param and redirect.
// this cause proper URL to be used to access the documentation page.

if ($pid = optional_param('pid', '', PARAM_INT)) {
    $page = $DB->get_record('local_cms_navi_data', array('pageid' => $pid));
    if ($CFG->slasharguments) {
        redirect($CFG->wwwroot.'/local/cms/view.php'.$cmsconfig->virtual_path.'/'.urlencode($page->pagename));
    } else {
        redirect(new moodle_url('/local/cms/view.php', array('page' => urlencode($page->pagename))));
    }
}

// get pagename.

if (!$pagename = optional_param('page', '', PARAM_FILE)) {
    if ($CFG->slasharguments) {
        /*
         * Support sitelevel slasharguments
         */
        $relativepath = get_file_argument(basename($_SERVER['SCRIPT_FILENAME']));
        if (preg_match('#^'.$cmsconfig->virtual_path.'/(.*)#i', $relativepath, $matches)) {
            redirect(new moodle_url('/local/cms/view.php', array('page' => $matches[1])));
        }
        unset($args, $relativepath);
    }
}

// last case is if frontpage format is set to CMS format.
 
if (isloggedin() and !isguestuser() and isset($CFG->frontpageloggedin)) {
    $frontpagelayout = $CFG->frontpageloggedin;
} else {
    $frontpagelayout = $CFG->frontpage;
}

if ( $frontpagelayout == FRONTPAGECMS or !empty($pagename) ) {
    $courseid = optional_param('id', SITEID, PARAM_INT);
    require_once($CFG->dirroot .'/local/cms/view.php');
    die;
}

// let continue normally if no sidehook has been catched.
