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

function xmldb_local_cms_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $result = true;
    //removed old upgrade stuff, as it now uses install.xml by default to install.

    $dbman = $DB->get_manager();

    if ($oldversion < 2015110100) {

        $table = new xmldb_table('local_cms_pages');

        // Add field lastuserid to track the last writer in a page
        $field = new xmldb_field('lastuserid');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, 0, 'modified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015110100, 'local', 'cms');
    }

    if ($oldversion < 2018102401) {

        $table = new xmldb_table('local_cms_navi_data');

        // Add field embedded to allow embed in another moodle page.
        $field = new xmldb_field('embedded');
        $field->set_attributes(XMLDB_TYPE_INTEGER, 1, null, XMLDB_NOTNULL, null, 0, 'target');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2018102401, 'local', 'cms');
    }

    return $result;
}
