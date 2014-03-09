<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class Delete_Form extends moodleform{

	function definition(){
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