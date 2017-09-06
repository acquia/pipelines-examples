# NodeJs Integration

The Node.js platform initiative uses pipelines to build zip files of Node.js apps, then pipelines should upload them into s3, then tell Node.js platform that the artifact is ready.

The purpose of the 901 tutorial is to demonstrate how to create and upload an artifact using the command `pipelines-artifact`.

# How to use it
To use this tutorial, the steps are:

* Create a feature branch in your Git repo.

* Copy the files from this directory into your repository.

* Commit directory content to the repo and push it:

```
  git add .
  git commit -m 'Init Node.js integration test'
  git push origin branch-name
```
This will trigger a job immediately. Otherwise, you can run ```pipelines start``` or click the Start Job button from the Pipelines UI.

* Once the job is finished, you will see in the log :

```
   Artifact created
   ...
   Artifact successfully uploaded
   ...
   Updated with status: Success
```
