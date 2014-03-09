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
 * menu editing table
 *
 * @package    local_cms
 * @author Moodle 1.9 Janne Mikkonen
 * @reauthor Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
 */

    defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

    global $OUTPUT;
    global $CFG;

    $tbl = new html_table();

    $strname     = get_string('name');
    $stractions  = get_string('actions','local_cms');
    $strintro    = get_string('intro','local_cms');
    $strcreated  = get_string('created','local_cms');
    $strmodified = get_string('modified');
    $strrequirelogin = get_string('requirelogin','local_cms');
    $strallowguest   = get_string('allowguest','local_cms');
    $keypix = $OUTPUT->pix_url('key', 'local_cms');
    $imgrlogin = '<img src="'.$keypix.' width="16" height="16" alt="'. $strrequirelogin .'"'.' title="'. $strrequirelogin .'" />';
	$guestpix = $OUTPUT->pix_url('guest', 'local_cms');
    $imgallowguest = '<img src="'.$guestpix.' width="16" height="16" alt="'. $strallowguest .'"' .' title="'. $strallowguest .'" />';

    $tbl->head = array($strname, $stractions, $strintro,$strcreated, $strmodified, $imgrlogin, $imgallowguest);

    $tbl->width = '100%';
    $tbl->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center');
    $tbl->wrap  = array('nowrap', 'nowrap', '', '', '', '', '');
    $tbl->data  = array();
    
    $editpagestr = get_string('editmenu', 'local_cms');
    $deletepagestr = get_string('deletemenu', 'local_cms');
    $viewpagestr = get_string('viewpages', 'local_cms');

    foreach ($menus as $menu) {

        $editlink  = '<a href="'.$CFG->wwwroot.'/local/cms/menuedit.php?id='.$menu->id.'&amp;sesskey='.sesskey().'&amp;course='.$courseid.'">';
        $editlink .= '<img src="'.$OUTPUT->pix_url('t/edit').'" alt="'.$editpagestr.'" title="'.$editpagestr.'" border="0" /></a>';

        $dellink  = '<a href="'.$CFG->wwwroot.'/local/cms/menudelete.php?id='.$menu->id.'&amp;sesskey='.sesskey().'&amp;course='.$courseid.'">';
        $dellink .= '<img src="'.$OUTPUT->pix_url('t/delete').'" alt="'.$deletepagestr.'" title="'.$deletepagestr.'" border="0" /></a>';

        $created  = userdate($menu->created, "%x %X");
        $modified = userdate($menu->modified, "%x %X");

        $menu->name  = format_string($menu->name);
        $menuname    = '<a title="'.$viewpagestr.'" href="'.$CFG->wwwroot.'/local/cms/pages.php?sesskey='.sesskey().'&amp;course='. $courseid .
                       '&amp;menuid='. $menu->id .'">'. $menu->name .'</a>';

        $rlogin      = ($menu->requirelogin) ? get_string('yes') : get_string('no');
        $allowguest  = ($menu->allowguest or !$menu->requirelogin) ? get_string('yes') : get_string('no');

        $newrow = array($menuname, "$editlink $dellink", format_string($menu->intro), $created, $modified, $rlogin, $allowguest);

        array_push($tbl->data, $newrow);

    }

    echo html_writer::table($tbl);
