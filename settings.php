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

$hasconfig = false;
$hassiteconfig = false;
if (is_dir($CFG->dirroot.'/local/adminsettings')) {
    // Integration driven code 
    if (has_capability('local/adminsettings:nobody', context_system::instance())) {
        $hasconfig = true;
        $hassiteconfig = true;
    } else if (has_capability('moodle/site:config', context_system::instance())) {
        $hasconfig = true;
        $hassiteconfig = false;
    }
    $capability = 'local/adminsettings:nobody';
} else {
    // Standard Moodle code
    $hassiteconfig = true;
    $hasconfig = true;
    $capability = 'moodle/site:config';
}

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_cms', get_string('pluginname', 'local_cms'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_cms/virtual_path', get_string('virtualpath', 'local_cms'), get_string('virtualpath_desc', 'local_cms'), '/documentation'));
}