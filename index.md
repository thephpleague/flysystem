---
layout: default
permalink: /
title: Introduction
---

# Introduction

Flysystem is a filesystem abstraction which allows you to easily swap out a local filesystem for a remote one. Reducing technical debt and chance of vendor lock-in.

[![Build Status](https://img.shields.io/travis/thephpleague/flysystem/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/flysystem)
[![Coverage Status](https://img.shields.io/coveralls/thephpleague/flysystem.svg?style=flat-square)](https://coveralls.io/r/thephpleague/flysystem)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/flysystem.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/flysystem)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](http://github.com/thephpleague/flysystem/blob/master/LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/league/flysystem.svg?style=flat-square)](https://packagist.org/packages/league/flysystem)
[![Total Downloads](https://img.shields.io/packagist/dt/league/flysystem.svg?style=flat-square)](https://packagist.org/packages/league/flysystem)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9820f1af-2fd0-4ab6-b42a-03e0c821e0af/big.png)](https://insight.sensiolabs.com/projects/9820f1af-2fd0-4ab6-b42a-03e0c821e0af)

# Goals

* Have a generic API for handling common tasks across multiple file storage engines.
* Have consistent output which you can rely on.
* Integrate well with other packages/frameworks.
* Be cacheable.
* Emulate directories in systems that support none, like AwsS3.
* Support third party plugins.
* Make it easy to test your filesystem interactions.
* Support streams for big file handling

# Questions?

Flysystem was created by Frank de Jonge, follow him on twitter for updates: [@frankdejonge](http://twitter.com/frankdejonge)
