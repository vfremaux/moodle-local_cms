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
 */

/*
 * Edit menu form
 */

global $USER;
?>
<div align="center">
<form method="get" action="<?php echo basename($_SERVER['SCRIPT_NAME']); ?>">
<input type="hidden" name="sesskey" value="<?php p($USER->sesskey) ?>" />
<input type="hidden" name="course" value="<?php p($courseid) ?>" />
<?php echo get_string('choose') ?>: <select name="menuid" onchange="this.form.submit();">
<?php
if (is_array($menus)) {
    foreach ($menus as $m) {
        echo "<option value=\"$m->id\"";
        echo (!empty($menu) && $menu->id == $m->id) ? " selected=\"true\"" : "";
        $menu->n = format_string($m->name);
        echo ">$m->name</option>\n";
    }
}
?>
</select>
</form>
</div>
<br />