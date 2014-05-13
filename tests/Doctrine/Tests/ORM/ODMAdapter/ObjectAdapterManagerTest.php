<?php


namespace Doctrine\Tests\ORM\ODMAdapter;


use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\DefaultMappingObject;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject;

class ObjectAdapterManagerTest extends \PHPUnit_Framework_TestCase {

    private $documentManager;
    private $objectManager;
    private $eventManager;
    private $classMetadata;

    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    private $classMetadataFactory;

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
        $this->classMetadataFactory = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory')
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $this->classMetadataFactory->expects($this->any())
                                   ->method('getMetadataFor')
                                   ->will($this->returnValue($this->classMetadata));
        $configuration->expects($this->once())
                      ->method('getClassMetadataFactoryName')
                      ->will($this->returnValue(get_class($this->classMetadataFactory)));

        $this->objectAdapterManager = new ObjectAdapterManager($this->documentManager, $this->objectManager, $configuration);
    }

    public function testGetManager()
    {
        $this->markTestSkipped('Will do later, need to do some work on it');
    }

}
 