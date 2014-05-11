<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Common\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\ORM\ODMAdapter\DocumentAdapterManager;
use Doctrine\ORM\ODMAdapter\Event;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ODMAdapter\Mapping\Driver\AnnotationDriver;

class ClassMetadataFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $documentManager;

    private $objectManager;

    /**
     * @var DocumentAdapterManager
     */
    private $documentAdapterManager;

    /**
     * @param $fqn
     *
     * @return ClassMetadata
     */
    protected function getMetadataFor($fqn)
    {
        $cache = new ArrayCache();
        $reader = new AnnotationReader($cache);
        $annotationDriver = new AnnotationDriver($reader);
        $annotationDriver->addPaths(array(__DIR__ . '/Model'));
        $this->documentAdapterManager->getConfiguration()->setMetadataDriverImpl($annotationDriver);

        $cmf = new ClassMetadataFactory($this->documentAdapterManager);
        $meta = $cmf->getMetadataFor($fqn);
        return $meta;
    }

    public function setUp()
    {
        $this->documentManager = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $this->documentAdapterManager = DocumentAdapterManager::create($this->documentManager, $this->objectManager);
    }

    public function testNotMappedThrowsException()
    {
        $cmf = new ClassMetadataFactory($this->documentAdapterManager);

        $this->setExpectedException('Doctrine\ORM\ODMAdapter\Exception\MappingException');
        $cmf->getMetadataFor('unknown');
    }

    public function testGetMapping()
    {
        $cmf = new ClassMetadataFactory($this->documentAdapterManager);

        $cm = new \Doctrine\ODM\PHPCR\Mapping\ClassMetadata('stdClass');

        $cmf->setMetadataFor('stdClass', $cm);

        $this->assertTrue($cmf->hasMetadataFor('stdClass'));
        $this->assertSame($cm, $cmf->getMetadataFor('stdClass'));
    }

    public function testGetAllMetadata()
    {
        $driver = new PHPDriver(array(__DIR__ . '/Driver/Model/php'));
        $this->documentAdapterManager->getConfiguration()->setMetadataDriverImpl($driver);

        $cmf = new ClassMetadataFactory($this->documentAdapterManager);

        $cm = new ClassMetadata('stdClass');
        $cmf->setMetadataFor('stdClass', $cm);

        $metadata = $cmf->getAllMetadata();

        $this->assertTrue(is_array($metadata));
    }

    public function testCacheDriver()
    {
        $this->markTestIncomplete('Test cache driver setting and handling.');
    }

    public function testLoadClassMetadataEvent()
    {
        $listener = new Listener;
        $evm = $this->documentAdapterManager->getEventManager();
        $evm->addEventListener(array(Event::loadClassMetadata), $listener);

        $meta = $this->getMetadataFor('Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\DefaultMappingObject');
        $this->assertTrue($listener->called);
        $this->assertSame($this->documentAdapterManager, $listener->dma);
        $this->assertSame($meta, $listener->meta);
    }
}

class Listener
{
    public $dma;
    public $meta;
    public $called = false;

    public function loadClassMetadata(Event\LoadClassMetadataEventArgs $args)
    {
        $this->called = true;
        $this->dma = $args->getDocumentAdapterManager();
        $this->meta = $args->getClassMetadata();
    }
}
 