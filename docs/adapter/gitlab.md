---
layout: default
permalink: /docs/adapter/gitlab-storage/
redirect_from: /adapter/gitlab-storage/
title: Gitlab Storage Adapter
---

## Installation
```bash
composer require royvoetman/flysystem-gitlab-storage
```

## Usage
```php
// Create a Gitlab Client to talk with the API
$client = new Client('personal-access-token', 'project-id', 'branch', 'base-url');
   
// Create the Adapter that implentents Flysystems AdapterInterface
$adapter = new GitlabAdapter($this->getClientInstance());

// Create FileSystem
$filesystem = new Filesystem($adapter);

// write a file
$filesystem->write('path/to/file.txt', 'contents');

// update a file
$filesystem->update('path/to/file.txt', 'new contents');

// read a file
$contents = $filesystem->read('path/to/file.txt');
```

### Access token (required for private projects)
Gitlab supports server side API authentication with Personal Access tokens

For more information on how to create your own Personal Access token: [Gitlab Docs](https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html)

### Project ID
Every project in Gitlab has its own Project ID. It can be found at to top of the frontpage of your repository. [See](https://stackoverflow.com/questions/39559689/where-do-i-find-the-project-id-for-the-gitlab-api#answer-53126068)

### Base URL
This will be the URL where you host your gitlab server (e.g. https://gitlab.com)

## Usage
Google Cloud Storage requires Service Account Credentials, which can be generated in the [Cloud Console](https://console.cloud.google.com/apis/credentials). Read more in [the official documentation](https://cloud.google.com/docs/authentication/production).

See the [project README](https://github.com/RoyVoetman/Flysystem-Gitlab-storage) for additional usage examples.
