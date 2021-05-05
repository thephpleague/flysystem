---
layout: default
title: Gitlab Filesystem Adapter
permalink: /v2/docs/adapter/gitlab/
---

Interacting with a gitlab repo through Flysystem can be done
by using the `RoyVoetman\FlysystemGitlab\GitlabAdapter`.

## Installation
```bash
composer require royvoetman/flysystem-gitlab-storage
```

## Usage
```php
// Create a Gitlab Client to talk with the API
$client = new Client('project-id', 'branch', 'base-url', 'personal-access-token');
   
// Create the Adapter that implements Flysystems AdapterInterface
$adapter = new GitlabAdapter(
    // Gitlab API Client
    $client,
    // Optional path prefix
    'path/prefix',
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

### Project ID
Every project in Gitlab has its own Project ID. It can be found at the top of the frontpage of your repository. [See](https://stackoverflow.com/questions/39559689/where-do-i-find-the-project-id-for-the-gitlab-api#answer-53126068)

### Base URL
This will be the URL where you host your gitlab server (e.g. https://gitlab.com)

### Access token (required for private projects)
Gitlab supports server side API authentication with Personal Access tokens

For more information on how to create your own Personal Access token: [Gitlab Docs](https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html)


