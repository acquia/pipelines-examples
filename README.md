# Encrypting data in Pipelines YAML

* [Download files](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial401.zip)
* [Watch video](https://player.vimeo.com/video/184398697)

This 401 tutorial shows you how to safely store encrypted data in the Pipelines YAML file so it will be accessible to your job.

You will use the ```pipelines encrypt``` command and give it a variable name and value to be encrypted. The sample YAML file creates a file ```secret.txt``` in your build artifact containing the decrypted value.

To get the files, clone this repository and look in the tutorial-401 folder, or you can [download the ZIP file here](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial401.zip).

The steps are:

* Clear out a branch in your Acquia Cloud repo so that only the tutorial files are included.
* Copy the files from this folder into your Cloud repository.
* Use the ```pipelines encrypt``` command to add the SSH key to the
acquia-pipelines.yml file:
```
  pipelines encrypt --add variables.global.secret
```
* Commit acquia-pipelines.yml and push it to your Cloud repository.
```
  git add acquia-pipelines.yml
  git commit -m 'encrypted variable'
  git push origin master
```
* Start a Pipelines job
```
   pipelines start
```
* When ```pipelines status``` shows the job is complete, checkout the master-build branch to see the secret.txt file:
```
   git fetch
   git checkout master-build
   cat secret.txt
```
