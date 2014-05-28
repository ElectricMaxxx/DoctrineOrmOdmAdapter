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

    public function setUp()
    {
        $this->documentManager = $this->getMockBuilder('Doctrine\ODM\PHPCR\DocumentManager')
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $this->objectManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $this->eventManager = $this->getMockBuilder('Doctrine\Common\EventManager')
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->classMetadata = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata')
                                    ->disableOriginalConstructor()
                                    ->getMock();

        $this->objectManager
            ->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($this->eventManager));

        $this->documentManager
            ->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($this->eventManager));

        $this->eventManager
            ->expects($this->any())
            ->method('addEventSubscriber');

        $configuration = new Configuration();
        $configuration->setManagers(
            array(
                Reference::DBAL_ORM => array('default' => $this->objectManager),
                Reference::PHPCR    => array('default' => $this->documentManager),
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
     * @expectedException \Doctrine\ORM\ODMAdapter\Exception\ConfigurationException
     * @expectedExceptionMessage No manager found for type some-type and manager name default.
     */
    public function testGetMangerByTypeThrowsException()
    {
        $this->objectAdapterManager->getManagerByType('some-type');
    }

    /**
     * @expectedException \Doctrine\ORM\ODMAdapter\Exception\ConfigurationException
     * @expectedExceptionMessage No manager found for type some-type and manager name manager.
     */
    public function testGetManagerByTypeAndNameThrowsException()
    {
        $this->objectAdapterManager->getManagerByType('some-type', 'manager');
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
