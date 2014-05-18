<?php


namespace Doctrine\Tests\ORM\ODMAdapter;


use Doctrine\ORM\ODMAdapter\Configuration;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\Reference;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\DefaultMappingObject;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject;

class ObjectAdapterManagerTest extends \PHPUnit_Framework_TestCase
{

    private $documentManager;
    private $objectManager;
    private $eventManager;
    private $classMetadata;

    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    private $classMetadataFactory;
    private $container;

    public function setUp()
    {
        $this->documentManager = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $this->eventManager = $this->getMockBuilder('Doctrine\Common\EventManager')
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->classMetadata = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $configuration = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\Configuration')
                              ->disableOriginalConstructor()
                              ->getMock();

        $configuration = new Configuration();
        $configuration->setDefaultManagerServices(
            array(
                Reference::DBAL_ORM => $this->objectManager,
                Reference::PHPCR    => $this->documentManager,
            )
        );
        $configuration->setClassMetadataFactoryName('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory');
        $this->objectAdapterManager = new ObjectAdapterManager($configuration);
    }

    public function testGetManagerByType()
    {
        $this->assertEquals($this->objectManager, $this->objectAdapterManager->getManagerByType('reference-dbal-orm'));
        $this->assertEquals($this->documentManager, $this->objectAdapterManager->getManagerByType('reference-phpcr'));
    }

    /**
     * @expectedException \Doctrine\ORM\ODMAdapter\Exception\ObjectAdapterMangerException
     * @expectedExceptionMessage Can not find a manager for reference type some-type
     */
    public function testGetMangerByTypeThrowsException()
    {
        $this->objectAdapterManager->getManagerByType('some-type');
    }

    /**
     * @expectedException \Doctrine\ORM\ODMAdapter\Exception\MappingException
     * @expectedExceptionMessage No reference mapping on stdClass
     */
    public function testGetManagerObjectWithoutTypeThrowsException()
    {
        $this->objectAdapterManager->getManager(new \stdClass(), 'test-field');
    }
}
