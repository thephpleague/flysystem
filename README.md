# League\Flysystem

[![Author](https://img.shields.io/badge/author-@frankdejonge-blue.svg?style=flat-square)](https://twitter.com/frankdejonge)
[![Build Status](https://img.shields.io/travis/thephpleague/flysystem/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/flysystem)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/league/flysystem.svg?style=flat-square)](https://packagist.org/packages/league/flysystem)
[![Total Downloads](https://img.shields.io/packagist/dt/league/flysystem.svg?style=flat-square)](https://packagist.org/packages/league/flysystem)

Flysystem is a filesystem abstraction which allows you to easily swap out a local filesystem for a remote one.

## Goals

* Have a generic API for handling common tasks across multiple file storage engines.
* Have consistent output which you can rely on.
* Integrate well with other packages/frameworks.
* Be cacheable.
* Emulate directories in systems that don't support them, like AWS S3.
* Support third party plugins.
* Make it easy to test your filesystem interactions.
* Support streams for big file handling.

## Installation

```
composer require league/flysystem
```

## Documentation

[Check out the documentation](https://flysystem.thephpleague.com/)

## Community Integrations

Want to get started quickly? Check out some of these integrations:

* Backup manager: https://github.com/backup-manager/backup-manager
* CakePHP integration: https://github.com/WyriHaximus/FlyPie
* Cilex integration: https://github.com/WyriHaximus/cli-fly
* Drupal: https://www.drupal.org/project/flysystem
* elFinder: https://github.com/barryvdh/elfinder-flysystem-driver
* Laravel integration: https://github.com/GrahamCampbell/Laravel-Flysystem
* Nette integration: https://github.com/contributte/flysystem
* Silex integration: https://github.com/WyriHaximus/SliFly
* Symfony integration: 
  * https://github.com/thephpleague/flysystem-bundle
  * https://github.com/1up-lab/OneupFlysystemBundle
* Yii 2 integration: https://github.com/creocoder/yii2-flysystem
* Zend Framework integration: https://github.com/bushbaby/BsbFlysystem
* PSR-11 containers: https://github.com/wshafer/psr11-flysystem

## Adapters

### Core
* Ftp
* Local
* NullAdapter

### Officially Supported
* Amazon Web Services - S3 V2: https://github.com/thephpleague/flysystem-aws-s3-v2
* Amazon Web Services - S3 V3: https://github.com/thephpleague/flysystem-aws-s3-v3
* Azure Blob Storage: https://github.com/thephpleague/flysystem-azure-blob-storage
* Memory: https://github.com/thephpleague/flysystem-memory
* PHPCR: https://github.com/thephpleague/flysystem-phpcr
* Rackspace Cloud Files: https://github.com/thephpleague/flysystem-rackspace
* Sftp (through phpseclib): https://github.com/thephpleague/flysystem-sftp
* WebDAV (through SabreDAV): https://github.com/thephpleague/flysystem-webdav
* Zip (through ZipArchive): https://github.com/thephpleague/flysystem-ziparchive

### Community Supported
* AliYun OSS Storage: https://github.com/xxtime/flysystem-aliyun-oss
* AliYun OSS Storage: https://github.com/kaysonwu/flysystem-aliyun-oss
* Amazon Cloud Drive - https://github.com/nikkiii/flysystem-acd
* AsyncAws - https://github.com/async-aws/flysystem-s3
* Azure File Storage: https://github.com/academe/flysystem-azure-file-storage
* Backblaze: https://github.com/mhetreramesh/flysystem-backblaze
* Chroot (filesystem from subfolder): https://github.com/fisharebest/flysystem-chroot-adapter
* ClamAV Scanner Adapter: https://github.com/mgriego/flysystem-clamav
* Citrix ShareFile: https://github.com/kapersoft/flysystem-sharefile
* Cloudinary: https://github.com/enl/flysystem-cloudinary
* Dropbox (with PHP 5.6 support): https://github.com/srmklive/flysystem-dropbox-v2
* Dropbox: https://github.com/spatie/flysystem-dropbox
* Fallback: https://github.com/Litipk/flysystem-fallback-adapter
* Gaufrette: https://github.com/jenkoian/flysystem-gaufrette
* GitHub: https://github.com/Radiergummi/flysystem-github-storage
* GitLab: https://github.com/RoyVoetman/Flysystem-Gitlab-storage
* Google Cloud Storage: https://github.com/Superbalist/flysystem-google-storage
* Google Drive: https://github.com/nao-pon/flysystem-google-drive
* Google Drive V2 (using regular paths): https://github.com/masbug/flysystem-google-drive-ext
* IBM Cloud Object Storage : https://github.com/tavux/flysystem-ibm-cos
* OneDrive: https://github.com/jacekbarecki/flysystem-onedrive
* OpenStack Swift: https://github.com/nimbusoftltd/flysystem-openstack-swift
* QiNiu OSS Storage: https://github.com/l396635210/flysystem-qiniu
* RAID: https://github.com/PHPGuus/flysystem-raid
* Redis (through Predis): https://github.com/danhunsaker/flysystem-redis
* Selectel Cloud Storage: https://github.com/ArgentCrusade/flysystem-selectel
* SinaAppEngine Storage: https://github.com/litp/flysystem-sae-storage
* SharePoint https://gitlab.com/cadix/flysystem-sharepoint-adapter
* PDO Database (optimised for use with large files when using the streams): https://github.com/phlib/flysystem-pdo
* PDO Database: https://github.com/IntegralSoftware/flysystem-pdo-adapter
* SSH/Shell: https://github.com/oliwierptak/flysystem-ssh-shell
* Tencent Cloud COS Storage: https://github.com/chunpat/flysystem-tencent-cos

## Caching (https://github.com/thephpleague/flysystem-cached-adapter)

* Adapter (using another Flysystem adapter)
* Memcached
* Memory (array caching)
* Redis (through Predis)
* Stash

## Security

If you discover any security related issues, please email info@frankdejonge.nl instead of using the issue tracker.

## For enterprise

Available as part of the Tidelift Subscription.

The maintainers of Flysystem and thousands of other packages are working with Tidelift to deliver commercial support and maintenance for the open source dependencies you use to build your applications. Save time, reduce risk, and improve code health, while paying the maintainers of the exact dependencies you use. [Learn more.](https://tidelift.com/subscription/pkg/packagist-league-flysystem?utm_source=packagist-league-flysystem&utm_medium=referral&utm_campaign=enterprise&utm_term=repo)

## Enjoy

Oh and if you've come down this far, you might as well follow me on [twitter](https://twitter.com/frankdejonge).
