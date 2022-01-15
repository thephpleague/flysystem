<?php

use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\FtpConnectionProvider;

include __DIR__ . '/../vendor/autoload.php';

$options = FtpConnectionOptions::fromArray([
   'host' => 'localhost',
   'port' => (int) ($argv[1] ?? 2122),
   'root' => '/',
   'username' => 'foo',
   'password' => 'pass',
]);

$provider = new FtpConnectionProvider();
$start = time();
$connected = false;

while (time() - $start < 60) {
    try {
        $provider->createConnection($options);
        $connected = true;
        break;
    } catch (Throwable $exception) {
        if (time() - $start < 30) {
            fwrite(STDOUT, "Exception while trying to connect:'\n");
            fwrite(STDOUT, (string) $exception);
            fwrite(STDOUT, "\n\n");
        }
        usleep(10000);
    }
}

if ( ! $connected) {
    fwrite(STDERR, "Unable to start FTP server.\n");
    exit(1);
}

fwrite(STDOUT, "Detected FTP server successfully.\n");
