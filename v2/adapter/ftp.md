---
layout: default
title: FTP Adapter
permalink: /v2/docs/adapter/ftp/
---

Interacting with the local filesystem through Flysystem can be done
by using the `League\Flysystem\Local\LocalFilesystemAdapter`.

## Simple usage:

```php
// The internal adapter
$adapter = new League\Flysystem\Ftp\FtpAdapter(
    // Connection options
    League\Flysystem\Ftp\FtpConnectionOptions::fromArray([
        'host' => 'hostname', // required
        'root' => '/root/path/', // required
        'username' => 'username', // required
        'password' => 'password', // required
        'port' => 21,
        'ssl' => false,
        'timeout' => 90,
        'utf8' => false,
        'passive' => true,
        'transferMode' => FTP_BINARY,
        'systemType' => null, // 'windows' or 'unix'
        'ignorePassiveAddress' => null, // true or false
        'timestampsOnUnixListingsEnabled' => false, // true or false
        'recurseManually' => true // true 
    ])
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

## Advanced usage:

```php
// The internal adapter
$adapter = new League\Flysystem\Ftp\FtpAdapter(
    // Connection options
    League\Flysystem\Ftp\FtpConnectionOptions::fromArray([
        'host' => 'hostname', // required
        'root' => '/root/path/', // required
        'username' => 'username', // required
        'password' => 'password', // required
    ]),
    new League\Flysystem\Ftp\FtpConnectionProvider(),
    new League\Flysystem\Ftp\NoopCommandConnectivityChecker(),
    new League\Flysystem\UnixVisibility\PortableVisibilityConverter()
);

// The FilesystemOperator
$filesystem = new League\Flysystem\Filesystem($adapter);
```

### Connection provider

The `League\Flysystem\Ftp\ConnectionProvider` allows you change how a connection
is setup. If you have particular needs, or if your FTP server is exotic, this allows
you to modify this process.

### Connectivity Checker

The `League\Flysystem\Ftp\ConnectivityChecker` allows you to change how
a connection is determined to be _connected_. This is something that can vary between
FTP flavours, so being able to change it, based in your needs, can be crucial.

By default the `League\Flysystem\Ftp\NoopCommandConnectivityChecker` and
`League\Flysystem\Ftp\RawListFtpConnectivityChecker` are shipped, which
are the most common forms for these checks.

### Connection Failures

All connection failures result in exceptions. The exceptions thrown have a name that
corresponds with what happened. Every connection exception is marked with the
`League\Flsysytem\Ftp\FtpConnectionException` interface, which is an extension of the
`League\Flsysytem\FilesystemException` interface.

### Visibility Converter

If you want to learn more about the permissions for local adapters,
read the [docs about unix visibility](/v2/docs/usage/unix-visibility/).
