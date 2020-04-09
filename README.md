# Pipelines and JavaScript Behat Tests

* [Download ZIP](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial801.zip)

The purpose of the 801 tutorial is to demonstrate the ability of Pipelines to support JavaScript testing.

Copy the files and directories from this directory into your Acquia Cloud repository. To get the files, clone this repository and look in the tutorial-801 folder.

The Drupal 8 composer.json has been modified to include behat packages, PhantomJS, and the Lightning project. The installation directories and script handler use docroot for Acquia Cloud instead of the default Drupal composer directory web.

The steps are:

* Clear out a branch in your Acquia Cloud repo so that only the tutorial files are included.
* Commit the sample files to your Cloud repo.
* Commit the changed YAML file and push to Cloud:
```
   git commit -a -m 'javascript test on lightning'
   git push origin master
```
* Run ```pipelines start```.
* When ```pipelines status``` shows the job is complete, run ```pipeline logs``` to see the results of the Behat tests.
