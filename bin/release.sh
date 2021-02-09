#!/usr/bin/env bash

set -e

# Make sure the release tag is provided.
if (( "$#" != 1 && "$#" != 2 ))
then
    echo "Tag has to be provided."
    exit 1
fi

php bin/check-versions.php $1

RELEASE_BRANCH="2.x"
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
VERSION=$1

# Make sure current branch and release branch match.
if [[ "$RELEASE_BRANCH" != "$CURRENT_BRANCH" ]]
then
    echo "Release branch ($RELEASE_BRANCH) does not match the current active branch ($CURRENT_BRANCH)."
    exit 1
fi

# Make sure the working directory is clear.
if [[ ! -z "$(git status --porcelain)" ]]
then
    echo "Your working directory is dirty. Did you forget to commit your changes?"
    exit 1
fi

# Make sure latest changes are fetched first.
git fetch origin

# Make sure that release branch is in sync with origin.
if [[ $(git rev-parse HEAD) != $(git rev-parse origin/$RELEASE_BRANCH) ]]
then
    echo "Your branch is out of date with its upstream. Did you forget to pull or push any changes before releasing?"
    exit 1
fi

# Strip prepended "v", if present
if [[ $VERSION == v* || $VERSION == V*  ]]
then
    VERSION="${VERSION:1}"
fi

if [[ "$2" == "--dry-run" ]]
then
    echo "Dry run: aborting now."
    exit 0
fi

# Tag Core
git tag $VERSION
git push origin --tags

# Tag Components
for REMOTE in memory sftp ftp aws-s3-v3 async-aws-s3 ziparchive adapter-test-utilities google-cloud-storage
do
    echo ""
    echo ""
    echo "Releasing $REMOTE";

    TMP_DIR="/tmp/flysystem-split"
    REMOTE_URL="git@github.com:thephpleague/flysystem-$REMOTE.git"

    rm -rf $TMP_DIR;
    mkdir $TMP_DIR;

    (
        cd $TMP_DIR;

        git clone $REMOTE_URL .
        git checkout "$RELEASE_BRANCH";

        git tag $VERSION
        git push origin --tags
    )
done
