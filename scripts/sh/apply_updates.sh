#!/bin/bash

# export SITE_REPO_CLONE_URL=ssh://codeserver.dev.******@*****:2222/~/repository.git
# export UPSTREAM_REPO_URL=https://github.com/pantheon-upstreams/drupal-composer-managed
# export UPSTREAM_REPO_BRANCH=main
export UPDATE_BEHAVIOR=procedural
export WORKING_DIR=/tmp/php-site-repository-tool/apply_updates

if [ -z "${SITE_REPO_CLONE_URL}" ]; then
    echo "Missing SITE_REPO_CLONE_URL"
    exit 1
fi

if [ -d "${WORKING_DIR}" ]; then
  echo "Deleting existing site dir ${WORKING_DIR}..."
  rm -rf "${WORKING_DIR}"
fi

echo "********************************************************************************"
echo "Executing apply_upstream command..."
EXE_PATH=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )

php "${EXE_PATH}/../../site-repository-tool" apply_upstream \
--site-repo-url "${SITE_REPO_CLONE_URL}" \
--upstream-repo-url "${UPSTREAM_REPO_URL}" \
--upstream-repo-branch "${UPSTREAM_REPO_BRANCH}" \
--update-behavior "${UPDATE_BEHAVIOR}" \
--work-dir "${WORKING_DIR}" \
--no-push \
--site-repo-branch master \
--strategy-option theirs \
--no-ff \
--verbose

echo "********************************************************************************"
echo "Printing two most recent site's git commits (git -C ${WORKING_DIR} log -n 2) ..."
git -C "${WORKING_DIR}" log -n 2

echo "********************************************************************************"
echo "Printing two most recent upstream's git commits (git -C ${WORKING_DIR} log upstream/${UPSTREAM_REPO_BRANCH} -n 2) ..."
git -C "${WORKING_DIR}" log upstream/"${UPSTREAM_REPO_BRANCH}" -n 2
