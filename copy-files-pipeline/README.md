## Copy files from source environment to CDE created by Pipelines Job

This is a example illustrating how to copy files from any source environment to target CDE using acquia-pipelines.yaml.

# How to use it
This example assumes that you know how to automatically deploy feature
branches and (if you use GitHub/BitBucket) pull requests to Acquia Cloud On
Demand Environments (ODEs) using the Pipelines Deploy tool. You can find the tutorial 
here: https://github.com/acquia/pipelines-examples/tree/master/tutorial-701.

The bash script `copy-files.sh` requires `CLOUD_API_KEY` and `CLOUD_API_SECRET` environment variables to make the necessary cloud API requests. Please read https://docs.acquia.com/acquia-cloud/develop/api/auth/ for more details on how to create API key/secret.

Update the the `CLOUD_API_KEY` and `CLOUD_API_SECRET` secure variables in the acquia-pipelines.yaml. For more information on encrypting variables and adding to the pipelines yaml, read https://docs.acquia.com/acquia-cloud/develop/pipelines/encrypt/. Please make sure that the API key/secret added has the neccessary permissions for eg., copy files, get environment details. 

The source environment from where the files to be copied can be changed by updating `SOURCE_ENV_NAME` in `copy-files.sh`. 

Note: The bash script `copy-files.sh` doesn't handle the token expiry or any API exceptions/errors.
