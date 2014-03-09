This local module provides a smart and simple CMS module that allows distributing
web pages built by authorised users.

Pages can be proposed with or without login, and are versionned. 

Pages are organized into page volumes that are published using a page menu
block to access them. Pages can be published or unpublished.

the local component provides the page management engine, and will work together with the
CMS Navigation Block. 

Volumes can be site level or course level. when placing a CMS Navigation block in a course
page, the volumes will be by default course level. However, the block setup allows publishing site level
documentation volumes into a course context.

## Release information

this Moodle 2.0 version has been refactored by Valery Fremaux (valery.fremaux@gmail.com)

## Installation

Deploy the local CMS plugin into the "local" folder. 
Install the CMS Navigation Block into the "blocks" folder of Moodle
run the Administration => Notifications to finish install

Plug the local/cms/indexcmshook.php at the highest position possible in the main index.php file of Moodle

require_once $CFG->dirroot.'/local/cms/indexcmshook.php';

this should occur BEFORE call to $OUTPUT->header()

## Quick start

Add a CMS Navigation block to the Home page
Configure the block usig a two step procedure :

* First create a volume and some pages in the volume
* Secondly setup again the block giving a front block title and attaching the block to a volume.

## What is NOT handled in this version

Some moodle way to backup/restore/export/import pages or volumes