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

class Edit_Menu_Form extends moodleform{

    function definition() {
        global $COURSE;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_TEXT);

        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('text', 'name', get_string('title', 'local_cms'), array('size' => 45));
        $mform->setType('name', PARAM_CLEANHTML);

        $editoroptions = array('cols' => 60, 'rows' => 10);
        $mform->addElement('editor', 'intro', get_string('intro', 'local_cms'), null, $editoroptions);
        $mform->setType('intro', PARAM_CLEANHTML);

        $yesnooptions[0] = get_string('no');
        $yesnooptions[1] = get_string('yes');

        $mform->addElement('select', 'printdate', get_string('printdateonpage', 'local_cms'), $yesnooptions);

        $mform->addElement('select', 'requirelogin', get_string('requirelogin', 'local_cms'), $yesnooptions);

        $mform->addElement('select', 'allowguest', get_string('allowguest', 'local_cms'), $yesnooptions);
        $mform->disabledIf('allowguest', 'requirelogin', 'eq', 1);

        $this->add_action_buttons(false);
    }

    function validation($data, $files = array()) {

        if (! empty($menu->name) and preg_match("/^\S{2,}/", $menu->name)  ) {
            $errors['name'] = get_string('nametooshort', 'local_cms');
        }
    }
}