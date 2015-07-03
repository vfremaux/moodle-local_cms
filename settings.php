<?php

if (!defined('MOODLE_INTERNAL')) die ("You cannot use this script this way");

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings = new admin_settingpage('local_cms', get_string('pluginname', 'local_cms'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_cms/virtual_path', get_string('virtualpath', 'local_cms'), get_string('virtualpath_desc', 'local_cms'), '/documentation'));
}