The purpose of the 301 tutorial is demonstrate the ability to use composer to
include a Drupal module from a private repository into your build
artifact. Because the repository is private, this requires adding an SSH key
with access to the repository to your Pipelines job.

This branch contains:

* A composer.json that requires the "my_company/my_module" package, which is a
  Drupal module, from the private-pipelines301 branch of this
  repository. Because this is a private repository, Composer running on
  Pipelines will not have access to it without proper authentication with an
  SSH key.
* An acquia-pipeliens.yml file which simply runs "composer install", thus
  attempting to pull in the branch of the private repository.
* ssh.key, a private SSH key without a passphrase that has read access to this
  repository. Normally, one would never commit an unencrypted SSH key to a
  repository. We did it here to ensure this demo works for you. This repository
  only contains sample code, and this key can only read this repository, so
  there is no security risk.

To make this work, you will need to add the SSH key to the acquia-pipelines.yml
file that you copy from here into your Cloud repository.  The steps are:

* Copy the files from this branch into your Cloud repository.
* Use the "pipelines encrypt" command to add the SSH key to the
acquia-pipelines.yml file: ```cat ssh.key | pipelines encrypt - --add
ssh-keys.my-key```
* Commit composer.json and acquia-pipelines.yml and push them to your Cloud
  repository. (You do not need to commit ssh.key; you've added an encrypted
  copy of it to acquia-pipelines.yml.)
* Run "pipelines start".

When the job succeeds, run "pipelines logs" and you should see Composer pulling
in the my_company/my_module package:

```
	Updating dependencies (including require-dev)
	  - Installing my_company/my_module (dev-private-pipelines301 801f5a4)
	    Cloning 801f5a4cea644ee9257c8085f4d9c6f573568d2e
```
