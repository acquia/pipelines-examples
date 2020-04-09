#!/bin/bash
set -e
# This is a simple example to copy files from any source environment to target CDE.
# Note: This script doesn't handle the token expiry or any API exceptions/errors.

SOURCE_ENV_NAME='test'

# Get Cloud Authentication token. For more details: https://docs.acquia.com/acquia-cloud/develop/api/auth/
TOKEN=$(curl -sS -X POST -u "${CLOUD_API_KEY}:${CLOUD_API_SECRET}" -d "grant_type=client_credentials" https://accounts.acquia.com/api/auth/oauth/token | python -c "import sys, json; print json.load(sys.stdin)['access_token']")

# Get CDE Name created by the Pipelines Job.
CDE_NAME=$(cat $PIPELINES_SCRIPT_DATA_FILE | python -c "import sys, json; print json.load(sys.stdin)['environment']['PIPELINES_DEPLOYMENT_NAME']")

# Get target environment Id using the CDE name.
TARGET_ENV_ID=$(curl -sS -X GET "https://cloud.acquia.com/api/applications/$PIPELINE_APPLICATION_ID/environments" -H "Content-Type: application/json" -H "Authorization: Bearer ${TOKEN}" | python -c "import sys, json; envs=json.load(sys.stdin)['_embedded']['items']; print [x for x in envs if x['name'] == '$CDE_NAME'][0]['id']")

# Get Source (i.e, Stage in this example) environment Id.
SOURCE_ENV_ID=$(curl -sS -X GET "https://cloud.acquia.com/api/applications/$PIPELINE_APPLICATION_ID/environments" -H "Content-Type: application/json" -H "Authorization: Bearer ${TOKEN}" | python -c "import sys, json; envs=json.load(sys.stdin)['_embedded']['items']; print [x for x in envs if x['name'] == '$SOURCE_ENV_NAME'][0]['id']")

# Copy Files from CDE to Source envronment to target. Use the notification url returned to get the tasks's status.
NOTIFICATION_LINK=$(curl -sS -X POST -d "{\"source\":\"$SOURCE_ENV_ID\"}" "https://cloud.acquia.com/api/environments/$TARGET_ENV_ID/files" -H "Content-Type: application/json" -H "Authorization: Bearer ${TOKEN}" | python -c "import sys, json; print json.load(sys.stdin)['_links']['notification']['href']")

# Wait for 'FilesCopied' task to finish.
# Poll NOTIFICATION_LINK to know the task status, the status will be 'in-progress' until the task is finished. For more details: https://cloudapi-docs.acquia.com/#/Notifications/getNotificationByUuid
COPY_STATUS='in-progress'

while [ $COPY_STATUS == 'in-progress' ]; do
  sleep 10;
  COPY_STATUS=$(curl -sS -X GET $NOTIFICATION_LINK -H "Content-Type: application/json" -H "Authorization: Bearer ${TOKEN}" | python -c "import sys, json; print json.load(sys.stdin)['status']");
  echo "Waiting for the files to be copied, current status: $COPY_STATUS.";
done

# Exit with 1 if the final status is 'failed'. Do nothing if the final status is 'completed' which mean the files copied successfully.
if [ $COPY_STATUS == 'failed' ]
then
  echo "Failed to copy the files, check task log for more details at https://cloud.acquia.com/app/develop/applications/$PIPELINE_APPLICATION_ID"
  exit 1
fi
