<?php

use Guzzle\Http\Exception\ClientErrorResponseException;
use League\Flysystem\Adapter\Rackspace;

class RackspaceTests extends PHPUnit_Framework_TestCase
{
    public function getContainerMock()
    {
        return Mockery::mock('OpenCloud\ObjectStore\Resource\Container');
    }

    public function getDataObjectMock($filename)
    {
        $mock = Mockery::mock('OpenCloud\ObjectStore\Resource\DataObject');
        $mock->shouldReceive('getName')->andReturn($filename);
        $mock->shouldReceive('getContentType')->andReturn('; plain/text');
        $mock->shouldReceive('getLastModified')->andReturn('2014-01-01');
        $mock->shouldReceive('getContentLength')->andReturn(4);

        return $mock;
    }

    public function testHas()
    {
        $container = $this->getContainerMock();
        $dataObject = $this->getDataObjectMock('filename.ext');
        $dataObject->shouldReceive('getContent')->andReturn('file contents');
        $container->shouldReceive('getObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->read('filename.ext'));
    }

    public function testRead()
    {
        $container = $this->getContainerMock();
        $dataObject = $this->getDataObjectMock('filename.ext');

        $container->shouldReceive('getObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->has('filename.ext'));
    }

    public function testHasFail()
    {
        $container = $this->getContainerMock();
        $container->shouldReceive('getObject')->andThrow('Guzzle\Http\Exception\ClientErrorResponseException');
        $adapter = new Rackspace($container);
        $this->assertFalse($adapter->has('filename.ext'));
    }

    public function testHasNotFound()
    {
        $container = $this->getContainerMock();
        $container->shouldReceive('getObject')->andThrow('OpenCloud\ObjectStore\Exception\ObjectNotFoundException');
        $adapter = new Rackspace($container);
        $this->assertFalse($adapter->has('filename.ext'));
    }

    public function testWrite()
    {
        $container = $this->getContainerMock();
        $dataObject = $this->getDataObjectMock('filename.ext');
        $container->shouldReceive('uploadObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->write('filename.ext', 'content'));
    }

    public function testWriteStream()
    {
        $container = $this->getContainerMock();
        $dataObject = $this->getDataObjectMock('filename.ext');
        $container->shouldReceive('uploadObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->writeStream('filename.ext', 'content'));
    }

    public function testUpdateFail()
    {
        $container = $this->getContainerMock();
        $dataObject = Mockery::mock('OpenCloud\ObjectStore\Resource\DataObject');
        $dataObject->shouldReceive('getLastModified')->andReturn(false);
        $dataObject->shouldReceive('setContent');
        $dataObject->shouldReceive('setEtag');
        $dataObject->shouldReceive('update')->andReturn(Mockery::self());
        $container->shouldReceive('getObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertFalse($adapter->update('filename.ext', 'content'));
    }

    public function testUpdate()
    {
        $container = $this->getContainerMock();
        $dataObject = $this->getDataObjectMock('filename.ext');
        $dataObject->shouldReceive('setContent');
        $dataObject->shouldReceive('setEtag');
        $dataObject->shouldReceive('update')->andReturn(Mockery::self());
        $container->shouldReceive('getObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->update('filename.ext', 'content'));
    }

    public function testUpdateStream()
    {
        $container = $this->getContainerMock();
        $dataObject = $this->getDataObjectMock('filename.ext');
        $dataObject->shouldReceive('setContent');
        $dataObject->shouldReceive('setEtag');
        $dataObject->shouldReceive('update')->andReturn(Mockery::self());
        $container->shouldReceive('getObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $resource = tmpfile();
        $this->assertInternalType('array', $adapter->updateStream('filename.ext', $resource));
    }

    public function testCreateDir()
    {
        $container = $this->getContainerMock();
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->createDir('dirname'));
    }

    public function getterProvider()
    {
        return array(
            array('getTimestamp'),
            array('getSize'),
            array('getMimetype'),
        );
    }

    /**
     * @dataProvider  getterProvider
     */
    public function testGetters($function)
    {
        $container = $this->getContainerMock();
        $dataObject = $this->getDataObjectMock('filename.ext');
        $container->shouldReceive('getObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->{$function}('filename.ext'));
    }

    public function deleteProvider()
    {
        return array(
            array(204, true),
            array(500, false),
        );
    }

    /**
     * @dataProvider  deleteProvider
     */
    public function testDelete($status, $expected)
    {
        $container = $this->getContainerMock();
        $dataObject = Mockery::mock('OpenCloud\ObjectStore\Resource\DataObject');
        $dataObject->shouldReceive('delete')->andReturn(Mockery::self());
        $dataObject->shouldReceive('getStatusCode')->andReturn($status);
        $container->shouldReceive('getObject')->andReturn($dataObject);
        $adapter = new Rackspace($container);
        $this->assertEquals($expected, $adapter->delete('filename.ext'));
    }

    public function renameProvider()
    {
        return array(
            array(201, true),
            array(500, false),
        );
    }

    /**
     * @dataProvider  renameProvider
     */
    public function testRename($status, $expected)
    {
        $container = $this->getContainerMock();
        $container->shouldReceive('getName')->andReturn('container_name');
        $dataObject = Mockery::mock('OpenCloud\ObjectStore\Resource\DataObject');
        $dataObject->shouldReceive('copy')->andReturn(Mockery::self());
        $dataObject->shouldReceive('getStatusCode')->andReturn($status);
        $container->shouldReceive('getObject')->andReturn($dataObject);

        if ($expected) {
            $dataObject->shouldReceive('delete');
        }

        $adapter = new Rackspace($container);
        $this->assertEquals($expected, $adapter->rename('filename.ext', 'other.ext'));
    }

    public function deleteDirProvider()
    {
        return array(
            array(200, true),
            array(500, false),
        );
    }

    /**
     * @dataProvider  deleteDirProvider
     */
    public function testDeleteDir($status, $expected)
    {
        $container = $this->getContainerMock();
        $container->shouldReceive('getName')->andReturn('container_name');
        $dataObject = Mockery::mock('OpenCloud\ObjectStore\Resource\DataObject');
        $dataObject->shouldReceive('getName')->andReturn('filename.ext');
        $container->shouldReceive('objectList')->andReturn(array($dataObject));
        $container->shouldReceive('getService')->andReturn($container);
        $container->shouldReceive('bulkDelete')->andReturn($container);
        $container->shouldReceive('getStatusCode')->andReturn($status);
        $adapter = new Rackspace($container);
        $this->assertEquals($expected, $adapter->deleteDir(''));
    }

    public function testListContents()
    {
        $container = $this->getContainerMock();
        $container->shouldReceive('getName')->andReturn('container_name');
        $dataObject = $this->getDataObjectMock('filename.ext');
        $container->shouldReceive('objectList')->andReturn(new ArrayIterator(array($dataObject)));
        $adapter = new Rackspace($container);
        $this->assertInternalType('array', $adapter->listContents('', true));
    }
}
