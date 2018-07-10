---
layout: default
title: Filesystem abstraction for PHP
permalink: /docs/
redirect_from: /
---

[![Author](//img.shields.io/badge/author-@frankdejonge-blue.svg?style=flat-square)](//twitter.com/frankdejonge)
[![Source Code](//img.shields.io/badge/source-thephpleague/flysystem-blue.svg?style=flat-square)](//github.com/thephpleague/flysystem)
[![Latest Version](//img.shields.io/github/tag/thephpleague/flysystem.svg?style=flat-square)](//github.com/thephpleague/flysystem/releases)
[![Software License](//img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](//github.com/thephpleague/flysystem/blob/master/LICENSE)
[![Build Status](//img.shields.io/travis/thephpleague/flysystem/master.svg?style=flat-square)](//travis-ci.org/thephpleague/flysystem)
[![Coverage Status](//img.shields.io/scrutinizer/coverage/g/thephpleague/flysystem.svg?style=flat-square)](//scrutinizer-ci.com/g/thephpleague/flysystem/code-structure)
[![Quality Score](//img.shields.io/scrutinizer/g/thephpleague/flysystem.svg?style=flat-square)](//scrutinizer-ci.com/g/thephpleague/flysystem)
[![Total Downloads](//img.shields.io/packagist/dt/league/flysystem.svg?style=flat-square)](//packagist.org/packages/league/flysystem)
![php 5.5.9+](//img.shields.io/badge/php-min%205.5.9-red.svg?style=flat-square)

## About Flysystem

Flysystem is a filesystem abstraction library for PHP. By providing a unified interface
for many different filesystems you're able to swap out filesystems without application wide
rewrites.

Using Flysystem can eliminate vendor-lock in, reduce technical debt, and improve the testability
of your code.

## Gold Sponsor(s)

<div class="flex my-6 max-w-sm">
    <a target="_blank" href="https://laravel.com" class="flex-no-grow w-1/3 bg-white rounded shadow-md mr-4 overflow-hidden">
        <img src="/img/laravel.svg" class="w-full" alt="Laravel.com"/>
    </a>
    <a target="_blank" href="https://azure.microsoft.com/free/?utm_source=flysystem&utm_medium=banner&utm_campaign=flysystem_sponsorship" class="flex-no-grow w-1/3 bg-white rounded shadow-md mr-4 overflow-hidden">
        <img src="/img/azure.svg" class="max-w-full m-6" alt="Microsoft Azure"/>
    </a>
</div>

View all the <a href="/docs/sponsors/">sponsors</a>.

## Getting Started

* **[Architecture](/docs/architecture/)**: Flysystem's internal architecture
* **[Setup/Bootstrap](/docs/usage/setup/)**: Load Flysystem and set up your first adapter
* **[Flysystem API](/docs/usage/filesystem-api/)**: How to interact with your Flysystem instance

### Commonly-Used Adapters

* **[AWS S3](/docs/adapter/aws-s3/)**
* **[Azure](/docs/adapter/azure/)**
* **[Digital Ocean Spaces](/docs/adapter/digitalocean-spaces/)**
* **[Local](/docs/adapter/local/)**
* **[Memory](/docs/adapter/memory/)**
* **[Creating An Adapter](/docs/advanced/creating-an-adapter/)**
