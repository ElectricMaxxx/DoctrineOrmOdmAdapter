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

    private $ormRegistry;
    private $phpcrRegistry;

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
        $this->ormRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->phpcrRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->ormRegistry->expects($this->any())
                          ->method('getManagerForClass')
                          ->will($this->returnValue($this->objectManager));
        $this->phpcrRegistry->expects($this->any())
                            ->method('getManagerForClass')
                            ->will($this->returnValue($this->documentManager));

        $configuration = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\Configuration')
                              ->disableOriginalConstructor()
                              ->getMock();

        $configuration = new Configuration();
        $configuration->setRegistries(
            array(
                Reference::DBAL_ORM => $this->ormRegistry,
                Reference::PHPCR    => $this->phpcrRegistry,
            )
        );
        $configuration->setClassMetadataFactoryName('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory');
        $this->objectAdapterManager = new ObjectAdapterManager($configuration);
    }

    public function testGetManagerByType()
    {
        $this->assertEquals($this->ormRegistry, $this->objectAdapterManager->getManagerByType('reference-dbal-orm'));
        $this->assertEquals($this->phpcrRegistry, $this->objectAdapterManager->getManagerByType('reference-phpcr'));
    }

    /**
     * @expectedException \Doctrine\ORM\ODMAdapter\Exception\ConfigurationException
     * @expectedExceptionMessage No registry found for type some-type.
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
