<?php

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

        $mform->addElement('htmleditor', 'intro', get_string('intro', 'local_cms'), array('cols' => 60, 'rows' => 10));
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