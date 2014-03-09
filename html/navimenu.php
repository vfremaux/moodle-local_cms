<?php
//
// Edit menu form
//

    defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

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