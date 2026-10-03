<?php

declare(strict_types=1);

namespace League\Flysystem\AsyncAwsS3;

use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\S3\Result\PutObjectOutput;
use AsyncAws\S3\S3Client;
use AsyncAws\SimpleS3\SimpleS3Client;
use League\Flysystem\Config;
use PHPUnit\Framework\TestCase;

class AsyncAwsS3AdapterUnitTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider conditionalWriteOptions
     */
    public function write_forwards_conditional_options_to_s3_client(string $option, string $value): void
    {
        $s3Client = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['putObject'])
            ->getMock();
        $s3Client->expects(self::once())
            ->method('putObject')
            ->with(self::callback(static fn (array $input): bool => $value === ($input[$option] ?? null)))
            ->willReturn(ResultMockFactory::create(PutObjectOutput::class));

        $filesystem = new AsyncAwsS3Adapter($s3Client, 'bucket');
        $filesystem->write('file.txt', 'contents', new Config([$option => $value]));
    }

    public static function conditionalWriteOptions(): iterable
    {
        yield 'IfMatch' => ['IfMatch', '"etag"'];
        yield 'IfNoneMatch' => ['IfNoneMatch', '*'];
    }

    /**
     * @test
     */
    public function write_stream_forwards_conditional_options_to_simple_s3_client(): void
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'contents');
        rewind($stream);

        $s3Client = $this->getMockBuilder(SimpleS3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['upload'])
            ->getMock();
        $s3Client->expects(self::once())
            ->method('upload')
            ->with(
                'bucket',
                'prefix/file.txt',
                $stream,
                self::callback(static fn (array $options): bool => '"etag"' === ($options['IfMatch'] ?? null)),
            );

        $filesystem = new AsyncAwsS3Adapter($s3Client, 'bucket', 'prefix');

        try {
            $filesystem->writeStream('file.txt', $stream, new Config(['IfMatch' => '"etag"']));
        } finally {
            fclose($stream);
        }
    }
}
