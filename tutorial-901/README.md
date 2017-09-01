# Deploying to on-demand environments

The purpose of the 901 tutorial is to demonstrate how to create and upload an artifact using the command `pipelines-artifact`.

The nodejs platform initiative uses pipelines to build zip files of nodejs apps, then pipelines should upload them into s3, then tell Hosting API that the artifact is ready


# How to use it
To use this tutorial, the steps are:

* Create a feature branch in your Git repo.

* Copy the files from this directory into your repository.

* Commit acquia-pipelines.yaml to the repo and push it:

```
  git add .
  git commit -m 'Init nodejs integration test'
  git push origin branch-name
```
* If you [use GitHub with Acquia Pipelines](https://docs.acquia.com/pipelines/github), a job will start immediately.  Otherwise, you can run ```pipelines start``` or click the Start Job button from the Pipelines UI.

* Once the job is finished, you will see in the log :

```
   Artifact created
   ...
   Artifact successfully uploaded
   ...
   Updated with status: Success
```
