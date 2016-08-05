The purpose of the 301 tutorial is demonstrate the ability to access a private repository using a private SSH key from the Pipelines container. This branch contains two files:  

* A composer.json that references a private repository. The private repository that it references is this one, the pipelinestutorial repository. When you copy this composer.json into your own Cloud repo, it will try to pull in a branch of this pipelinestutorial repository, which it cannot do without proper authentication with an SSH key.
* A .acquia.yaml file which simply runs "composer install", thus attempting to pull in the branch of the private repository.

To make this work, you will need to add an SSH key to the .acquia.yaml file that you copy from here into your Cloud repository. The steps are:
* Copy the files from this branch into your Cloud repository.
* Use the "pipeline encrypt" command to add your SSH key to the .acquia.yaml file.
* Commit all the files and push them to your Cloud repository.
* Run "pipeline start"
