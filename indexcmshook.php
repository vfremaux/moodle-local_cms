<?php

    /**
    * @package local_cms
    * @category local
    * @date 27/09/2009
    * @version: reviewed by MyLearningFactory (valery.fremaux@gmail.com)
    *
    * This is a special script for hooking the Moodle index page. 
    * It adds a sideway to standard index when subnavigating to 
    * a virtual cms subdir.
    */
    
    // Pre defines if not initialized
    if (!defined('FRONTPAGECMS')) define ('FRONTPAGECMS', 52);
    
    if (empty($CFG->local_cms_virtual_path)) {
        set_config('local_cms_virtual_path', '/documentation');
    }

    // capture pid param and redirect.
    // this cause proper URL to be used to access the documentation page
    if ($pid = optional_param('pid', '', PARAM_INT)){
        $page = $DB->get_record('local_cms_navi_data', array('pageid' => $pid));
        if ($CFG->slasharguments){
            redirect($CFG->wwwroot.'/local/cms/view.php'.$CFG->local_cms_virtual_path.'/'.$page->pagename);
        } else {
            redirect($CFG->wwwroot.'/local/cms/view.php?page='.$page->pagename);
        }
    }
    
    // get pagename
    if (!$pagename = optional_param('page', '', PARAM_FILE)){
	    if ($CFG->slasharguments) {
	        // Support sitelevel slasharguments
	        // in form /index.php/<$CFG->block_cmsnavigation_cmsvirtual>/<pagename>
	        $relativepath = get_file_argument(basename($_SERVER['SCRIPT_FILENAME']));
	        if ( preg_match('#^'.$CFG->local_cms_virtual_path.'/([a-z0-9\_\- ]+)#i', $relativepath, $matches) ) {
	            redirect($CFG->wwwroot.'/local/cms/view.php?page='.$matches[1]);
	        }
	        unset($args, $relativepath);
	    }
	}

	// last case is if frontpage format is set to CMS format.    
    if (isloggedin() and !isguestuser() and isset($CFG->frontpageloggedin)) {
        $frontpagelayout = $CFG->frontpageloggedin;
    } else {
        $frontpagelayout = $CFG->frontpage;
    }
    
    if ( $frontpagelayout == FRONTPAGECMS or !empty($pagename) ) {
    	$courseid = optional_param('id', SITEID, PARAM_INT);
    	require_once($CFG->dirroot .'/local/cms/view.php');
    	die;
    }
    
    // let continue normally if no sidehook has been catched
