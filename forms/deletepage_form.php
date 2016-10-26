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

require_once $CFG->dirroot.'/lib/formslib.php';

class Delete_Form extends moodleform{

    function definition() {
        global $COURSE;

        $mform = $this->_form;

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_TEXT);

        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'nid');
        $mform->setType('nid', PARAM_INT);

        $mform->addElement('hidden', 'naviid');
        $mform->setType('naviid', PARAM_INT);

        $deletemessage = get_string('pagedeletesure', 'local_cms', $this->_customdata['pagetitle']);
        $mform->addElement('html', '<p>'.$deletemessage.'</p>');

        $this->add_action_buttons(true, get_string('confirm'));
    }

}