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
 * Version details.
 *
 * @package     local_cms
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2012 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_cms';
$plugin->version  = 2015110100;   // The (date) version of this plugin.
$plugin->requires = 2018042700;   // Requires this Moodle version.
$plugin->maturity = MATURITY_RC;
$plugin->release = '3.5.0 (Build 2015110100)';

// Non moodle attributes.
$plugin->codeincrement = '3.5.0000';