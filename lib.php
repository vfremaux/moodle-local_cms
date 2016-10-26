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
 * This file contains necessary functions to output
 * cms content on site or course level.
 *
 * @package    local_cms
 * @category   local
 * @author     Moodle 1.9 Janne Mikkonen
 * @author Gustav Delius
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    global $CFG, $DB, $COURSE;

    if (!in_array($filearea, array('body'))) {
        return false;
    }

    $itemid = (int)array_shift($args);

    $navidata = $DB->get_record('local_cms_navi_data', array('pageid' => $itemid));
    $navi = $DB->get_record('local_cms_navi', array('id' => $navidata->naviid));

    if ($navi->requirelogin) {
        if ($course->id == SITEID) {
            require_login();
        } else {
            require_course_login($course);
        }

        $readcontext = context_course::instance($COURSE->id);
        if (!$navi->allowguest && is_guest($readcontext)) {
            return false;
        }
    }

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_cms/$filearea/$itemid/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload);
}

