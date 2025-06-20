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
 * page editing table
 *
 * @package    local_cms
 * @category
 * @author Moodle 1.9 Janne Mikkonen
 * @author Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * TODO : Should be rewritten
 */

global $USER;

$strpagetitle = get_string('page', 'local_cms');
$stractions = get_string('actions', 'local_cms');
$strpublish = get_string('publish', 'local_cms');
$strembedded = get_string('embed', 'local_cms');
$strmenu = get_string('showinmenu', 'local_cms');
//$strcreated   = get_string('created', 'local_cms');
$strversion   = get_string('version');
$strmodified  = get_string('modified');

$themenu = new cms_pages_menu($menu->id, $courseid);
$haspages = count($themenu->pages);
$tbl = new html_table;

$tbl->head = array('', '', $strpagetitle, '', $strembedded, $strpublish, $strmenu, $strversion, $strmodified);
$tbl->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
$tbl->size = array('5%', '10%', '30%', '20%', '5%', '5%', '5%', '10%', '10%');
$tbl->width = '100%';
$tbl->cellpadding = 3;
$tbl->cellspacing = 1;
$tbl->nowrap = array('', 'nowrap', 'nowrap', '', '', '', '', '', '');
$tbl->data  = array();

//$tbl->data = cms_print_pages_menu(0, $menu->id, $courseid);
$tbl->data = $themenu->get_page_tree_rows(0);

$pagesurl = new moodle_url('/local/cms/pages.php');
echo '<form id="cmsPages" name="cmsPages" method="get" action="'.$pagesurl.'">';
echo '<input type="hidden" name="sesskey" value="'.$USER->sesskey.'" />';
echo '<input type="hidden" name="menuid" value="'.$menu->id.'" />';
echo '<input type="hidden" name="course" value="'.$courseid.'" />';

echo html_writer::table($tbl);

/*
$options = empty($tbl->data) ? array('add' => get_string('add')) :
                               array('add' => get_string('add'),
                                     'edit' => get_string('edit'),
                                     'purge' => get_string('delete'));

print $stractions .': ';
choose_from_menu($options, "action", "", "choose", "javascript:document.cmsPages.submit();");
print '<noscript>';
print '<input type="submit" value="'. get_string('commitselectedaction','cms', '', $cmslangpath) .'" />';
print '</noscript>';*/

if ( has_capability('local/cms:createpage', $context, $USER->id) ) {
    echo '<input type="submit" name="add" value="'. get_string('add') .'" />'."\n";
}
if ($haspages) {
    if ( has_capability('local/cms:editpage', $context, $USER->id) ) {
        echo '<input type="submit" name="edit" value="'. get_string('edit') .'" />'."\n";
    }
    if ( has_capability('local/cms:deletepage', $context, $USER->id) ) {
        echo '<input type="submit" name="purge" value="'. get_string('delete') .'"/>'."\n";
    }
}

echo '</form>' . "\n";

