<?php

use League\Flysystem\PhpseclibV2\SftpConnectionProvider as V2Provider;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider as V3Provider;
use phpseclib3\Net\SFTP;

include __DIR__ . '/../vendor/autoload.php';

$providerName = class_exists(SFTP::class) ? V3Provider::class : V2Provider::class;
$connectionProvider = $providerName::fromArray(
    [
        'host' => 'localhost',
        'username' => 'foo',
        'password' => 'pass',
        'port' => 2222,
    ]
);

$start = time();
$connected = false;

while (time() - $start < 60) {
    try {
        $connectionProvider->provideConnection();
        $connected = true;
        break;
    } catch (Throwable $exception) {
        echo($exception);
        usleep(10000);
    }
}

if ( ! $connected) {
    fwrite(STDERR, "Unable to start SFTP server.\n");
    exit(1);
}

fwrite(STDOUT, "Detected SFTP server successfully.\n");
