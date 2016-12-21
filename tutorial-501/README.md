# Web requests and Behat tests

* [Download ZIP](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial501.zip)
* [Watch video](https://player.vimeo.com/video/184399322)

The purpose of the 501 tutorial is to demonstrate the Pipelines web env and show how you can run Behat as an example use of the web
environment.

Copy the files and directories from this directory into your Acquia Cloud repository. To get the files, clone this repository and look in the tutorial-501 folder, or you can [download the ZIP file here](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial501.zip).

The Drupal 8 composer.json has been modified to include behat packages and the installation directories and script handler use docroot for Acquia Cloud instead of the default Drupal composer directory web.

The steps are:

* Clear out a branch in your Acquia Cloud repo so that only the tutorial files are included.
* Commit the sample files to your Cloud repo.
* Commit the changed YAML file and push to Cloud:
```
   git commit -a -m 'web environment test'
   git push origin master
```
* Run ```pipelines start```.
* When ```pipelines status``` shows the job is complete, run ```pipeline logs``` to see the results of the Behat tests.
