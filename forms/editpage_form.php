<?php

require_once $CFG->dirroot.'/lib/formslib.php';

class Edit_Page_Form extends moodleform {

    var $editoroptions;

    public function definition() {
        global $COURSE, $USER;

        if ($this->_customdata['pagecourse'] == SITEID || $this->_customdata['pagecourse'] == 0) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($this->_customdata['pagecourse']);
        }

        $this->editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' =>  $context, 'maxbytes' => 0);

        $mform = $this->_form;

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_TEXT);

        // Current course.
        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_TEXT);

        // Pageid in navi_data.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Page record id.
        $mform->addElement('hidden', 'pid');
        $mform->setType('pid', PARAM_INT);

        // Menu id.
        $mform->addElement('hidden', 'nid');
        $mform->setType('nid', PARAM_INT);

        $mform->addElement('hidden', 'what');
        $mform->setType('what', PARAM_TEXT);

        // menu to wich the page belongs to
        $options = array();
        foreach ($this->_customdata['menus'] as $menu) {
            $options[$menu->id] = strip_tags($menu->name);
        }
        $mform->addElement('select', 'naviid', get_string('choosemenu', 'local_cms'), $options);

        $mform->addElement('advcheckbox', 'showinmenu', get_string('showinmenu', 'local_cms'), '');
        $mform->setDefault('showinmenu', 1);

        $mform->addElement('text', 'title', get_string('linkname', 'local_cms'), array('size' => 60, 'maxlength' => 255));
        $mform->setType('title', PARAM_CLEANHTML);

        $mform->addElement('text', 'pagename', get_string('pagename', 'local_cms'), array('size' => 60, 'maxlength' => 100));
        $mform->setType('pagename', PARAM_CLEANHTML);

        $mform->addElement('editor', 'body', get_string('pagecontent', 'local_cms'), null, $this->editoroptions);

        $mform->addElement('text', 'url', get_string('pageurl', 'local_cms'), array('size' => 60, 'maxlength' => 255));
        $mform->setType('url', PARAM_URL);

        $openingoptions[0] = get_string('pagewindow', 'local_cms');
        $openingoptions[1] = get_string('newwindow', 'local_cms');
        $mform->addElement('select', 'target', get_string('pagetarget', 'local_cms'), $openingoptions);

        $mform->addElement('advcheckbox', 'showblocks', get_string('showblocks', 'local_cms'), '');

        if ( has_capability('local/cms:publishpage', $context, $USER->id) ) {
            $mform->addElement('advcheckbox', 'publish', get_string('publish', 'local_cms'), '');
        } else {
            $mform->addElement('hidden', 'publish');
        }

        // parent page :
        $parentoptions[0] = get_string('noparent', 'local_cms');
        if (!empty($this->_customdata['parents'])) {
            foreach ($this->_customdata['parents'] as $parent) {
                $parentoptions[$parent->id] = $parent->pagename;
            }
        }
        $mform->addElement('select', 'parentid', get_string('parentpage', 'local_cms'), $parentoptions);
        $mform->setType('parentid', PARAM_INT);

        //

        $mform->addElement('submit', 'preview', get_string('preview', 'local_cms'));

        $this->add_action_buttons(true);
    }

    public function validation($data, $files = array()) {
        global $COURSE;

        $errors = array();

        if ($data['what'] == 'add') {
            if (cms_pagename_exists($data['pagename'], $COURSE->id)) {
                $errors['pagename'] = get_string('nameinuse', 'local_cms', $data['pagename']);
            }
        }

        return $errors;
    }

    public function set_data($defaults) {

        if ($defaults->course == SITEID || $defaults->course == 0) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($defaults->course);
        }

        $defaults->bodyformat = FORMAT_HTML;

        $body_draftid_editor = file_get_submitted_draft_itemid('body');
        $currenttext = file_prepare_draft_area($body_draftid_editor, $context->id, 'local_cms', 'body', @$defaults->id, array('subdirs' => true), @$defaults->body);
        $defaults = file_prepare_standard_editor($defaults, 'body', $this->editoroptions, $context, 'local_cms', 'body', @$defaults->id);
        $defaults->body = array('text' => $currenttext, 'format' => FORMAT_HTML, 'itemid' => $body_draftid_editor);

        parent::set_data($defaults);
    }
}