The purpose of the 301 tutorial is demonstrate the ability to access a private
repository using a private SSH key from the Pipelines container. This branch
contains three files:

* A composer.json that references a private repository. The private
  repository that it references is this one, the pipelinestutorial
  repository. When you copy this composer.json into your own Cloud
  repo, it will try to pull in a branch of this pipelinestutorial
  repository, which it cannot do without proper authentication with an
  SSH key.
* A .acquia.yaml file which simply runs "composer install", thus attempting to
pull in the branch of the private repository.
* id\_pipelinestutorial, a private SSH key without a passphrase that has read
  access to this repository. Normally, one would never commit an unencrypted SSH key to a repository. We did it here to ensure you have access to an SSH key that can read this repository in order to perform the steps of this demo. This repository only contains sample code, and this key can only read this repository, so there is no security risk.

To make this work, you will need to add an SSH key to the .acquia.yaml file
that you copy from here into your Cloud repository. The steps are:

* Copy the files from this branch into your Cloud repository.
* Use the "pipeline encrypt" command to add your SSH key to the .acquia.yaml
file: ```cat id\_pipelinestutorial | pipeline encrypt - --add ssh-keys.my-key```
* Commit composer.json and .acquia.yaml and push them to your Cloud repository. (You do not need to commit id\_pipelinestutorial; you've added an encrypted copy of it to .acquia.yaml.)
* Run "pipeline start".
