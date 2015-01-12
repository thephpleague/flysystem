<?php

use League\Flysystem\Event\After;
use League\Flysystem\Event\Before;
use League\Flysystem\EventableFilesystem;

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

    public function testFilesystemCreation()
    {
        $injected = $this->getMockeryMock('adapter');
        $filesystem = new EventableFilesystem($injected);
        $this->assertNotSame($filesystem->getFilesystem(), $injected);
    }

    public function methodCallsProvider()
    {
        return [
            ['read', ['path.txt'], ['contents' => 'contents'], 'contents'],
            ['write', ['path.txt', 'contents'], ['contents' => 'contents'], true, false],
            ['update', ['path.txt', 'contents'], ['path' => 'path.txt'], true],
            ['readStream', ['path.txt'], ['stream' => 'stream'], 'stream'],
            ['writeStream', ['path.txt', tmpfile()], ['stream' => tmpfile()], true, false],
            ['updateStream', ['path.txt', tmpfile()], ['stream' => tmpfile()], true],
            ['delete', ['path.txt'], true, true],
            ['deleteDir', ['path.txt'], true, true],
            ['createDir', ['path'], ['path' => 'path'], true],
            ['has', ['path'], true, true],
            ['getMetadata', ['path'], ['mimetype' => 'plain/text'], ['mimetype' => 'plain/text',]],
            ['getSize', ['path'], ['size' => 1], 1],
            ['getTimestamp', ['path'], ['timestamp' => 1], 1],
            ['getMimetype', ['path'], ['mimetype' => 'type'], 'type'],
            ['getVisibility', ['path'], ['visibility' => 'public'], 'public'],
            ['setVisibility', ['path', 'public'], true, true],
            ['listContents', [''], [['path' => 'path', 'type' => 'file']], [[
                'path' => 'path',
                'type' => 'file',
            ]]],
        ];
    }

    public function testPut()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $filesystem = new EventableFilesystem($adapter);
        $adapter->shouldReceive('has')->andReturn(false);
        $adapter->shouldReceive('write')->andReturn(['contents' => 'contents']);
        $this->assertTrue($filesystem->put('file', 'contents'));
    }

    public function testPutStream()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $filesystem = new EventableFilesystem($adapter);
        $adapter->shouldReceive('has')->andReturn(false);
        $adapter->shouldReceive('writeStream')->andReturn(['contents' => 'contents']);
        $this->assertTrue($filesystem->putStream('file', $stream = tmpfile()));
        fclose($stream);
    }

    public function testReadAndDelete()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->andReturn(true);
        $adapter->shouldReceive('read')->andReturn(['contents' => 'contents']);
        $adapter->shouldReceive('delete')->andReturn(true);
        $filesystem = new EventableFilesystem($adapter);
        $this->assertEquals('contents', $filesystem->readAndDelete('filename'));
    }

    public function testGet()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $handler = Mockery::mock('League\Flysystem\Handler');
        $handler->shouldReceive('setFilesystem');
        $handler->shouldReceive('setPath');
        $adapter->shouldReceive('has')->andReturn(true);
        $filesystem = new EventableFilesystem($adapter);
        $result = $filesystem->get('path', $handler);
        $this->assertInstanceOf('League\Flysystem\Handler', $result);
    }

    public function testCopy()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->with('old')->andReturn(true);
        $adapter->shouldReceive('has')->with('new')->andReturn(false);
        $adapter->shouldReceive('copy')->with('old', 'new')->andReturn(true);
        $filesystem = new EventableFilesystem($adapter);
        $this->assertTrue($filesystem->copy('old', 'new'));
    }

    public function testRename()
    {
        $adapter = Mockery::mock('League\Flysystem\AdapterInterface');
        $adapter->shouldReceive('has')->with('old')->andReturn(true);
        $adapter->shouldReceive('has')->with('new')->andReturn(false);
        $adapter->shouldReceive('rename')->with('old', 'new')->andReturn(true);
        $filesystem = new EventableFilesystem($adapter);
        $this->assertTrue($filesystem->rename('old', 'new'));
    }

    /**
     * @dataProvider methodCallsProvider
     */
    public function testMethodCalls($method, $arguments, $response, $expected, $has = true)
    {
        $mock = $this->getMockeryMock('adapter');
        $mock->shouldReceive('has')->with($arguments[0])->andReturn($has);
        $mock->shouldReceive($method)->andReturn($response);
        $filesystem = new EventableFilesystem($mock);
        $result = call_user_func_array([$filesystem, $method], $arguments);
        $this->assertEquals($expected, $result);
    }

    public function testAddPlugin()
    {
        $mock = $this->getMockeryMock('adapter');
        $config = [];
        $plugin = Mockery::mock('League\Flysystem\PluginInterface');
        $plugin->shouldReceive('getMethod')->andReturn('methodName');
        $mock->shouldReceive('addPlugin')->with($plugin, $config)->andReturn($mock);
        $filesystem = new EventableFilesystem($mock);
        $filesystem->addPlugin($plugin, $config);
    }

    public function testBeforeEventAbort()
    {
        $mock = $this->getMockeryMock('adapter');
        $filesystem = new EventableFilesystem($mock);
        $filesystem->addListener('before.read', function ($event) {
            $event->cancelOperation('altered response');
        });

        $response = $filesystem->read('path');
        $this->assertEquals($response, 'altered response');
    }

    public function testSilentCall()
    {
        $mock = $this->getMockeryMock('adapter');
        $mock->shouldReceive('has')->andReturn(true);
        $filesystem = new EventableFilesystem($mock);
        $filesystem->addListener('before.read', function () {
            throw new Exception('The test failed');
        });
        $mock->shouldReceive('read')->andReturn(['contents' => 'contents']);
        $result = $filesystem->read('path', ['silent' => true]);
        $this->assertEquals('contents', $result);
    }

    public function testBeforeSetArgument()
    {
        $filesystem = new EventableFilesystem($mock = $this->getMockeryMock('adapter'));
        $filesystem->addListener('before.read', function ($event) {
            $event->setArgument('path', 'altered');
        });
        $mock->shouldReceive('has')->andReturn(true);
        $mock->shouldReceive('read')
            ->with('altered')
            ->andReturn(['contents' => 'contents']);
        $result = $filesystem->read('original');
        $this->assertEquals('contents', $result);
    }

    public function testBeforeSetArgumentArray()
    {
        $filesystem = new EventableFilesystem($mock = $this->getMockeryMock('adapter'));
        $filesystem->addListener('before.read', function ($event) {
            $event->setArguments(['path' => 'altered']);
        });
        $config = [];
        $mock->shouldReceive('has')->andReturn('true');
        $call = $mock->shouldReceive('read')->with('altered');
        $call->andReturn(['contents' => 'contents']);
        $result = $filesystem->read('original', $config);
        $this->assertEquals($result, 'contents');
    }

    public function testBeforeEvent()
    {
        $before = new Before($filesystem = $this->getMockeryMock('filesystem'), 'methodName', $arguments = ['argu' => 'ment']);
        $this->assertSame($filesystem, $before->getFilesystem());
        $this->assertEquals('methodName', $before->getMethod());
        $this->assertEquals($arguments, $before->getArguments());
        $this->assertEquals('ment', $before->getArgument('argu'));
        $this->assertEquals('unknown', $before->getArgument('invalid', 'unknown'));
    }

    public function testAfterEvent()
    {
        $arguments = ['argu' => 'ment'];
        $after = new After($filesystem = $this->getMockeryMock('filesystem'), 'methodName', 'result', $arguments);
        $this->assertEquals($arguments, $after->getArguments());
        $this->assertEquals($arguments['argu'], $after->getArgument('argu'));
        $this->assertSame($filesystem, $after->getFilesystem());
    }

    public function testAfterEventGetArgument()
    {
        $arguments = ['argu' => 'ment'];
        $after = new After($filesystem = $this->getMockeryMock('filesystem'), 'methodName', 'result', $arguments);
        $this->setExpectedException('ErrorException');
        $after->getArgument('unknown');
    }

    public function testAfterSetResult()
    {
        $filesystem = new EventableFilesystem($mock = $this->getMockeryMock('adapter'));
        $filesystem->addListener('after.read', function ($event) {
            $event->setResult('injected');
        });
        $arguments = ['original'];
        $mock->shouldReceive('has')->andReturn(true);
        $call = $mock->shouldReceive('read');
        call_user_func_array([$call, 'with'], $arguments);
        $call->andReturn(['contents' => 'contents']);
        $result = call_user_func_array([$filesystem, 'read'], $arguments);
        $this->assertEquals('injected', $result);
    }
}
