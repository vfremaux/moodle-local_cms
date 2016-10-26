<?php // $Id: cms.php,v 1.2 2010/12/25 13:41:32 vf Exp $

$string['cms:manageblocks'] = 'Can manage block of CMS page';
$string['cms:manageview'] = 'Can manage view';
$string['cms:createmenu'] = 'Can create menu';
$string['cms:createmenu'] = 'Can create menu';
$string['cms:createpage'] = 'Can create page';
$string['cms:deletemenu'] = 'Can delete menus';
$string['cms:deletepage'] = 'can delete pages';
$string['cms:editmenu'] = 'Can edit menu';
$string['cms:editpage'] = 'Can edit pages';
$string['cms:movepage'] = 'Can edit pages';
$string['cms:publishpage'] = 'Publish pages';

$string['eventcmspageviewed'] = 'CMS Page viewed';

$string['actions'] = 'Actions';
$string['addchild'] = 'Add child page';
$string['addnewmenu'] = 'Add new menu';
$string['addnewpage'] = 'Add new page';
$string['addtop'] = 'Add top-level page';
$string['allowguest'] = 'Allow guest';
$string['author'] = 'Author';
$string['choosemenu'] = 'Choose menu';
$string['cms'] = 'CMS';
$string['cmsnavigation'] = 'CMS navigation';
$string['created'] = 'Created';
$string['defaultpage'] = 'Default page';
$string['defaultpagechanged'] = 'Default page changed successfully!';
$string['deletemenu'] = 'Delete menu';
$string['deletepage'] = 'Delete page';
$string['diff'] = 'Diff';
$string['editmenu'] = 'Edit menu';
$string['editpage'] = 'Edit page';
$string['editortricks'] = 'Editing special tricks';
$string['errorcreatepage'] = 'Couldn\'t create new page!';
$string['errorpagemenulink'] = 'Error while linking page to menu! Page has been removed.';
$string['errorbadpage'] = 'Error retrieving CMS page!';
$string['fetchback'] = 'Fetch-back';
$string['frontpagecms'] = 'CMS content';
$string['intro'] = 'Description';
$string['isdefaultpage'] = 'Default';
$string['lastmodified'] = 'Last modified: {$a}';
$string['lastmodifiedby'] = 'Last modified: {$a->modified} by {$a->by}';
$string['linkname'] = 'Link name';
$string['linkto'] = 'Link to ...';
$string['managemenus'] = 'Manage menus';
$string['managepages'] = 'Manage pages';
$string['menu'] = 'Menu';
$string['inmenu'] = 'Visible in navigation block';
$string['notinmenu'] = 'Not visible in navigation block';
$string['menuadded'] = 'New menu added';
$string['menudeleted'] = 'Menu deleted!';
$string['menudeletesure'] = 'You\'re about to <strong>delete</strong> menu <strong>{$a}</strong>!<br />Do you wish to continue?';
$string['menus'] = 'Menus';
$string['missingtitle'] = 'The \'link name\' field is mandatory';
$string['nameinuse'] = 'The page name {$a} is already in use. Please choose another name';
$string['nametooshort'] = 'This name is too short';
$string['newpage'] = 'New CMS page';
$string['newpage'] = 'New page';
$string['newpageadded'] = 'New page added!';
$string['newwindow'] = 'New window';
$string['nocontent'] = 'No content yet!!!';
$string['nomenus'] = 'No menus created yet!';
$string['nonvisibleblocks'] = '<strong>WARNING!</strong><br />Users cannot see these blocks!';
$string['noparent'] = 'No parent';
$string['nopermission'] = 'You do not have the required permissions to view this page';
$string['norecursion'] = 'The page cannot be its own parent.';
$string['nosuchparent'] = 'The parent page you selected ({$a}) does not exist.';
$string['onlypreview'] = 'This is a preview only. The page has not yet been saved';
$string['page'] = 'Page';
$string['pagecontent'] = 'Content';
$string['pagedeleted'] = 'Page deleted!';
$string['pagehistory'] = 'Page history';
$string['pagename'] = 'Page name';
$string['pages'] = 'Pages';
$string['pagetarget'] = 'Target window';
$string['pagetitle'] = 'Page title';
$string['pageupdated'] = 'Page <strong>{$a}</strong> updated!';
$string['pageurl'] = 'Page URL';
$string['pageviewdenied'] = 'You are not allowed to view the content of this page!';
$string['pagewindow'] = 'This window';
$string['parentname'] = 'Parent page';
$string['parentpage'] = 'Parent page';
$string['pluginname'] = 'CMS';
$string['preview'] = 'Preview';
$string['printdateonpage'] = 'Print date';
$string['publish'] = 'Publish';
$string['published'] = 'Published';
$string['requirelogin'] = 'Require login';
$string['setdefault'] = 'Set as default';
$string['settodefaultpage'] = 'Set default';
$string['shorttitle'] = 'The \'link name\' field is too short. Please enter a value of three or more characters';
$string['showblocks'] = 'Show all blocks';
$string['showinmenu'] = 'Show in menu';
$string['title'] = 'Title';
$string['unpublished'] = 'Unpublished';
$string['updatedmenu'] = 'Menu updated!';
$string['updatepage'] = 'Update page';
$string['virtualpath'] = 'Virtual CMS path';
$string['virtualpath_desc'] = 'A virtual sub path from where all pages are known as CMS pages';
$string['viewpage'] = 'View page content';
$string['viewpages'] = 'View pages';
$string['unknownauthor'] = 'Unkown author';
$string['history'] = 'Page History';
$string['pagediff'] = 'Version diff';

$string['cms_help'] = 'CMS builds menus in which you build a set of pages. Blocks can be added in course content that attaches one of the available menus.';

$string['pagedeletesure'] = 'You\'re about to <strong>delete</strong> page <strong>{$a}</strong>!<br />
NOTE! All child pages of <em>{$a}</em><br /> (if any exists) will also be deleted!<br /><br />
Do you wish to continue?';

$string['editortricks_help'] = '
    The moodle cms syntax let you use some special placeholders to help building powerful documentations:
    
    [[pagename]] : Will let the cms engine behave as a Wiki and let you create the subpage.

    [[pagename|alternatelabel]] : Same as above but he page name and visible link label are different.

    [[TOC]] : Provides a full toc on child pages

    [[PARENT]] : Provides alink to parent page using parent page title.

    [[PARENT|label]] : Same as above but uses label string as visible link label.

    [[PAGE pagename]] : Including another page\'s content (content reuse).

    [[PRIVATE string]] : A string only visible for write only users

    [[NEWS]] : Inserts the course news forum content.

    [[SCRIPT string]] : Experimental.
';
