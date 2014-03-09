<?php // $Id: view.php,v 1.8 2008/03/23 09:11:38 julmis Exp $

//  Display a content page.
//  This is a slightly modified version of course/view.php
//  Whenever course/view.php is updated this page should probably be updated as well

    if (!defined('MOODLE_INTERNAL')){
        require_once('../../config.php');
        $pagename = optional_param('page', '', PARAM_FILE);
        $courseid = optional_param('id', SITEID, PARAM_INT);
    } else {
        // $courseid = SITEID;
        // if (!isset($pagename)) $pagename = '';
        global $OUTPUT;
    }
    
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->libdir.'/blocklib.php');
    require_once($CFG->libdir.'/ajax/ajaxlib.php');
    require_once($CFG->dirroot.'/mod/forum/lib.php');
    require_once($CFG->dirroot.'/local/cms/lib.php');

    $pageid = optional_param('pid', 0, PARAM_INT);

    // The following parameters are the same as on course/view.php minus $id, $name, $idnumber
    $edit        = optional_param('edit', -1, PARAM_BOOL);
    $hide        = optional_param('hide', 0, PARAM_INT);
    $show        = optional_param('show', 0, PARAM_INT);
    $section     = optional_param('section', 0, PARAM_INT);
    $move        = optional_param('move', 0, PARAM_INT);
    $marker      = optional_param('marker',-1 , PARAM_INT);
    $switchrole  = optional_param('switchrole',-1, PARAM_INT);

    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('coursemisconf');
    }

    if ( defined('SITEID') && SITEID == $course->id && $CFG->slasharguments ) {
        // Support sitelevel slasharguments
        // in form /index.php/<$CFG->block_cmsnavigation_cmsvirtual>/<pagename>
        $relativepath = get_file_argument(basename($_SERVER['SCRIPT_FILENAME']));
        if ( preg_match("#^{$CFG->local_cms_virtual_path}(/[a-z0-9\_\-]+)#i", $relativepath) ) {
            $args = explode("/", $relativepath);
            $pagename = clean_param($args[2], PARAM_FILE);
        }
        unset($args, $relativepath);
    }

    if (empty($pagename) && !empty($pageid)) {
        $pid = explode(',',$_GET['pid']);
        $pageid = array_pop($pid);
        if (!$pagedata = cms_get_page_data_by_id( $courseid, $pageid )) {
            print_error('errorbadpage', 'local_cms');
        }
    } elseif ( !$pagedata = cms_get_page_data($course->id, $pagename) ) {
        print_error('errorbadpage', 'local_cms');
    }

/// get a valid context

    if ( empty($courseid) ) {
        $courseid = SITEID;
        $context = context_system::instance();
    } else {
        $context = context_course::instance($courseid);
    }

    // Remove any switched roles before checking login
    if ($switchrole == 0 && confirm_sesskey()) {
        role_switch($switchrole, $context);
    }

    //require_login($course); //CMS not used?

    // Switchrole - sanity check in cost-order...
    $reset_user_allowed_editing = false;
    if ($switchrole > 0 && confirm_sesskey() &&
        has_capability('moodle/role:switchroles', $context)) {
        // is this role assignable in this context?
        // inquiring minds want to know...
        $aroles = get_assignable_roles($context);
        if (is_array($aroles) && isset($aroles[$switchrole])) {
            role_switch($switchrole, $context);
            // Double check that this role is allowed here
            if (@!empty($pagedata->requirelogin) && !has_capability('moodle/site:doanything', $context)) { //CMS check if need to requirelogin.
                require_login($course->id);
            }
        }
    }

    add_to_log($course->id, 'course', 'view', "/local/cms/view.php?id=$course->id", "$course->id");
    
    $cmsstr = get_string('cms', 'local_cms');
    
    $url = $CFG->wwwroot.'/local/cms/view.php?courseid='.$course->id.'&amp;pid='.$pageid;
    
    $PAGE->set_url($url);
    $PAGE->set_context($context);
    $PAGE->set_title($pagedata->title);
    $PAGE->set_heading($cmsstr);

    if (!isset($USER->editing)) {
        $USER->editing = 0;
    }
    if ($PAGE->user_allowed_editing()) {
        if (($edit == 1) and confirm_sesskey()) {
            $USER->editing = 1;
        } else if (($edit == 0) and confirm_sesskey()) {
            $USER->editing = 0;
        }
    } else {
        $USER->editing = 0;
    }

	echo $OUTPUT->header();
	
    echo '<div class="course-content">';  // course wrapper start

    // Bounds for block widths

    $editing = $PAGE->user_is_editing();

    echo $OUTPUT->box_start('left', '580', '', 5, 'sitetopic');

    if (! empty($pagedata->requirelogin) &&
       (isguestuser() && !$pagedata->allowguest) ) {
            echo get_string('pageviewdenied', 'local_cms');
    } else {

        if ($editing){
        	echo cms_actions($pagedata, $course,$context);
        }
        echo format_string(cms_render($pagedata, $course));

        if ( !empty($pagedata->printdate) ) {
            print '<p style="font-size: x-small;">'. get_string('lastmodified', 'local_cms', userdate($pagedata->modified)) .'</p>';
        }
        if ($editing) {
            $stradmin = get_string('admin');
            print "<p style=\"font-size: x-small;\"><a href=\"$CFG->wwwroot/cms";
            print "/index.php?course=$courseid&amp;sesskey=$USER->sesskey\">$stradmin</a></p>\n";

        }
    }

    echo $OUTPUT->box_end();


    if ( defined('SITEID') && SITEID == $course->id ) {
        // Close the page when we're on site level.
        echo '</div>';  // content wrapper end
        echo $OUTPUT->footer();
    }
