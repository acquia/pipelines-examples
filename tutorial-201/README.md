# Building Drupal with Composer

* [Download files](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial201.zip)
* [Watch video](https://drive.google.com/a/acquia.com/file/d/0BwBnqz3kkaPuWU5GNlhOLTc1YUk/view?usp=sharing)

This tutorial demonstrates the ability to build a Drupal site with Composer, with only the essential
local files (e.g.: not the vendor directory) committed to the source branch.  It builds Acquia Lightning, based on the [Lightning Project](https://github.com/acquia/lightning-project). To use it clear out a branch in your Acquia Cloud repo so that only the tutorial files are included. Then commit all the files from this branch into your Acquia Cloud repository, push them to Cloud, then run ```pipelines start```.

 Then commit all the files from this branch into your Acquia Cloud repository, push them to Cloud, then run pipelines start.

To get the files, clone this repository and checkout the pipelines201 branch, or you can [download the ZIP file here](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial201.zip).

Notes:

* We have already set up this branch to contain the output from "composer create-project" and prepopulated settings.php with database
  configration and profile name.  Doing that set up from scratch will be the subject of a different tutorial.
* This branch is set up exclusively for Acquia Cloud.  To do additional development on it, enable Live Development on an environment
  running this branch, then run composer install in the livedev directory.
* An environment running this cannot currently be cloned into Dev Desktop due to a number of complex integration issues.  
 
After the Pipelines build is complete, you can install Lighting on Cloud via drush:

* drush @<<pipelinesdemo>>.test ac-code-path-deploy pipelines-build-master
* drush @<<pipelinesdemo>>.test ac-task-info <<14726779>>
* drush @<<pipelinesdemo>>.test ac-environment-livedev enable 1
* drush @<<pipelinesdemo>>.test.livedev site install
* drush @<<pipelinesdemo>>.test.livedev ac-domain-list

Take the username and password from the site install command response and enter it at the domain provided by ac-domain-list
