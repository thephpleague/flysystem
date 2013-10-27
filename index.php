<?php

namespace Flysystem;

use Aws\S3\S3Client;
use Aws\Common\Enum\Region;

include './vendor/autoload.php';


// $client = S3Client::factory(array(
// 	'key'    => 'AKIAIXAHCYKGCJGPTEIQ',
// 	'secret' => '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',
// 	'region' => Region::EU_WEST_1, //'eu-west-1',
// ));


//$adapter = new Adapter\AwsS3($client, 'frenky.io-filemanager', null, ['ACL' => 'public-read']);
$adapter = new Adapter\Local(__DIR__.'/resources');
$cache = new Cache\Predis(null, 'flysystem.local');
$cache->flush();
$filesystem = new Filesystem($adapter, $cache);

echo json_encode($filesystem->listContents(), JSON_PRETTY_PRINT);

// $exists = $filesystem->has('nested/test.txt');
// var_dump($exists);
// if ( ! $exists) {
// 	$filesystem->write('nested/test.txt', 'Once there was a timestamp:');
// }
// $filesystem->append('nested/test.txt', PHP_EOL.time());
// var_dump($filesystem->getMetadata('nested/test.txt'));
