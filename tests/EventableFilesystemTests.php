<?php

use League\Flysystem\EventableFilesystem;
use League\Flysystem\Config;

class EventableFilesystemTests extends PHPUnit_Framework_TestCase
{
    public function getMockeryMock($type)
    {
        $types = [
            'adapter' => 'League\\Flysystem\\AdapterInterface',
            'cache' => 'League\\Flysystem\\CacheInterface',
            'filesystem' => 'League\\Flysystem\\FilesystemInterface',
        ];

        return Mockery::mock($types[$type]);
    }
    public function testFilesystemInjection()
    {
        $injected = $this->getMockeryMock('filesystem');
        $filesystem = new EventableFilesystem($injected);
        $this->assertSame($filesystem->getFilesystem(), $injected);
    }

    public function testFilesystemCreation()
    {
        $injected = $this->getMockeryMock('adapter');
        $filesystem = new EventableFilesystem($injected);
        $this->assertNotSame($filesystem->getFilesystem(), $injected);
    }

    public function methodCallsProvider()
    {
        return [
            ['read', ['path.txt']],
            ['write', ['path.txt', 'contents']],
            ['update', ['path.txt', 'contents']],
            ['put', ['path.txt', 'contents']],
            ['readStream', ['path.txt']],
            ['putStream', ['path.txt', 'contents']],
            ['writeStream', ['path.txt', 'contents']],
            ['updateStream', ['path.txt', 'contents']],
            ['delete', ['path.txt']],
            ['deleteDir', ['path.txt']],
            ['createDir', ['path.txt']],
            ['readAndDelete', ['path.txt']],
            ['listPaths', ['path', true]],
            ['listContents', ['path', true]],
            ['listWith', [['key'], 'path', true]],
            ['getWithMetadata', ['path', []]],
            ['get', ['path', Mockery::mock('League\Flysystem\Handler')]],
            ['flushCache', []],
            ['addPlugin', [Mockery::mock('League\Flysystem\PluginInterface')]],
            ['has', ['path']],
            ['getMetadata', ['path']],
            ['getSize', ['path']],
            ['getTimestamp', ['path']],
            ['getMimetype', ['path']],
            ['getVisibility', ['path']],
            ['setVisibility', ['path', 'public']],
            ['copy', ['path', 'path']],
            ['rename', ['path', 'path']],
        ];
    }

    /**
     * @dataProvider methodCallsProvider
     */
    public function testMethodCalls($method, $arguments, $expected = '__mocked_response__')
    {
        $arguments[] = new Config;
        $mock = $this->getMockeryMock('filesystem');
        $call = $mock->shouldReceive($method);
        $call = call_user_func_array([$call, 'with'], $arguments);
        $call->andReturn($expected);
        $filesystem = new EventableFilesystem($mock);
        $result = call_user_func_array([$filesystem, $method], $arguments);
        $this->assertEquals($expected, $result);
    }

    public function testBeforeEventAbort()
    {
        $mock = $this->getMockeryMock('filesystem');
        $filesystem = new EventableFilesystem($mock);
        $filesystem->addListener('before.read', function ($event) {
            $event->cancelOperation('altered response');
        });

        $response = $filesystem->read('path');
        $this->assertEquals($response, 'altered response');
    }

    public function testPrepareArguments()
    {
        $filesystem = new EventableFilesystem($this->getMockeryMock('filesystem'));
        $arguments = ['config' => ['option' => true]];
        $prepared = $filesystem->prepareArguments($arguments);
        $this->assertInstanceOf('League\\Flysystem\\Config', $prepared['config']);
    }

    public function testSilentCall()
    {
        $filesystem = new EventableFilesystem($mock = $this->getMockeryMock('filesystem'));
        $filesystem->addListener('before.read', function () {
            throw new Exception('The test failed');
        });
        $mock->shouldReceive('read')->andReturn('contents');
        $result = $filesystem->read('path', ['silent' => true]);
        $this->assertEquals('contents', $result);
    }

    public function testBeforeSetArgument()
    {
        $filesystem = new EventableFilesystem($mock = $this->getMockeryMock('filesystem'));
        $filesystem->addListener('before.read', function ($event) {
            $event->setArgument('path', 'altered');
        });
        $arguments = ['original', new Config];
        $call = $mock->shouldReceive('read');
        call_user_func_array([$call, 'with'], $arguments);
        $call->andReturn(true);
        $result = call_user_func_array([$filesystem, 'read'], $arguments);
        $this->assertTrue($result);
    }

    public function testBeforeSetArgumentArray()
    {
        $filesystem = new EventableFilesystem($mock = $this->getMockeryMock('filesystem'));
        $filesystem->addListener('before.read', function ($event) {
            $event->setArguments(['path' => 'altered']);
        });
        $arguments = ['original', new Config];
        $call = $mock->shouldReceive('read');
        call_user_func_array([$call, 'with'], $arguments);
        $call->andReturn(true);
        $result = call_user_func_array([$filesystem, 'read'], $arguments);
        $this->assertTrue($result);
    }

    public function testBeforeEvent()
    {
        $before = new League\Flysystem\Event\Before($filesystem = $this->getMockeryMock('filesystem'), 'methodName', $arguments = ['argu' => 'ment']);
        $this->assertSame($filesystem, $before->getFilesystem());
        $this->assertEquals('methodName', $before->getMethod());
        $this->assertEquals($arguments, $before->getArguments());
        $this->assertEquals('ment', $before->getArgument('argu'));
        $this->assertEquals('unknown', $before->getArgument('invalid', 'unknown'));
    }

    public function testAfterEvent()
    {
        $before = new League\Flysystem\Event\After($filesystem = $this->getMockeryMock('filesystem'), 'methodName', $arguments = ['argu' => 'ment']);
        $this->assertSame($filesystem, $before->getFilesystem());
    }

    public function testAfterSetResult()
    {
        $filesystem = new EventableFilesystem($mock = $this->getMockeryMock('filesystem'));
        $filesystem->addListener('after.read', function ($event) {
            $event->setResult('injected');
        });
        $arguments = ['original', new Config];
        $call = $mock->shouldReceive('read');
        call_user_func_array([$call, 'with'], $arguments);
        $call->andReturn(true);
        $result = call_user_func_array([$filesystem, 'read'], $arguments);
        $this->assertEquals('injected', $result);
    }
}
