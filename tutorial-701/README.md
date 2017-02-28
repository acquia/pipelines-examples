# Deploying to on-demand environments

* [TODO Download ZIP](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial501.zip)

This tutorial demonstrates how to automatically deploy feature branches and (if
you use GitHub) pull requests to Acquia Cloud On Demand Environments (ODEs).

The steps are:

* Create a feature branch in your Git repo.  Since this tutorial demonstrates Pipelines deployment, call the branch pipelines-deploy:
  ```
  git checkout master
  git checkout -b pipelines-deploy
  ```
* Copy the `acquia-pipelines.yaml` file from this tutorial into your Git repo (now on the pipelines-deploy branch). You can:
  * cut and paste the [contents of the file](https://raw.githubusercontent.com/acquia/pipelines-examples/master/tutorial-701/acquia-pipelines.yaml), or
  * [download a ZIP file](http://tutorials.pipeline-dev.services.acquia.io/pipelinestutorial701.zip) containing the file, or
  * clone this repository and look in the tutorial-701 folder.
* Add your Acquia Cloud credentials to `acquia-pipelines.yaml` in your repo.
  * Paste your n3_key from ~/.acquia/pipelines/credentials after `N3_KEY`.
  * Run `awk '/n3_secret/ { printf("%s", $2) }' ~/.acquia/pipelines/credentials | pipelines encrypt - --add variables.global.N3_SECRET` to securely add your secret key.
* Commit `acquia-pipelines.yaml` to the repo and push it:
  ```
  git add acquia-pipelines.yaml
  git commit -m 'Add Pipelines YAML file'
  git push origin pipelines-deploy
  ```
* If you [use GitHub with Acquia Pipelines](https://docs.acquia.com/pipelines/github), a job will start immediately.  Otherwise, run ```pipelines start```.
* When ```pipelines status``` shows the job is complete, visit your site on Acquia Cloud. A new environment named 'pipelines-build-pipelines-deploy' will exist, running the build of the pipelines-deploy branch.
* Merge the pipelines-deploy branch into master:
  ```
  git checkout master
  git merge pipelines-deploy
  git branch -d pipelines-deploy
  ```
* Now, all of your new feature branche and pull requests will get their own Cloud environment.
