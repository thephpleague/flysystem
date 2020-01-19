---
layout: default
permalink: /v1/docs/adapter/gitlab/
redirect_from:
    - /docs/adapter/gitlab/
    - /adapter/gitlab/
title: GitLab Adapter
---

## Installation
```bash
composer require royvoetman/flysystem-gitlab-storage
```

## Usage
```php
// Create a GitLab Client to talk with the API
$client = new Client('personal-access-token', 'project-id', 'branch', 'base-url');
   
// Create the Adapter that implements Flysystems AdapterInterface
$adapter = new GitlabAdapter($client);

// Create FileSystem
$filesystem = new Filesystem($adapter);

// Write a file
$filesystem->write('path/to/file.txt', 'contents');

// Update a file
$filesystem->update('path/to/file.txt', 'new contents');

// Read a file
$contents = $filesystem->read('path/to/file.txt');
```

### Access token (required for private projects)
GitLab supports server side API authentication with Personal Access tokens.

For more information on how to create your own Personal Access token: [GitLab Docs](https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html)

### Project ID
Every project in GitLab has its own Project ID. It can be found at to top of the frontpage of your repository. [See](https://stackoverflow.com/questions/39559689/where-do-i-find-the-project-id-for-the-gitlab-api#answer-53126068)

### Base URL
This will be the URL where you host your GitLab server (e.g. https://gitlab.com).

> See the [project README](https://github.com/RoyVoetman/Flysystem-Gitlab-storage) for additional usage examples.
