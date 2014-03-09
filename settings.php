<?php

if (!defined('MOODLE_INTERNAL')) die ("You cannot use this script this way");

$settings->add(new admin_setting_configtext('local_cms_virtual_path', get_string('virtualpath', 'local_cms'), get_string('virtualpath_desc', 'local_cms'), '/documentation'));
