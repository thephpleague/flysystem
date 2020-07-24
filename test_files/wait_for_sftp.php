<?php

use League\Flysystem\PHPSecLibV2\SftpConnectionProvider;

include __DIR__ . '/../vendor/autoload.php';

$connectionProvider = SftpConnectionProvider::fromArray(
    [
        'host' => 'localhost',
        'username' => 'foo',
        'password' => 'pass',
        'port' => 2222,
    ]
);

$start = time();
$connected = false;

while (time() - $start < 15) {
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
