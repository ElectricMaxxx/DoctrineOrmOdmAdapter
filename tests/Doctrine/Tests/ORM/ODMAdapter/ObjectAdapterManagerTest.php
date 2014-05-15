<?php


namespace Doctrine\Tests\ORM\ODMAdapter;


use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\Reference;
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
        $this->classMetadataFactory = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory')
                                           ->disableOriginalConstructor()
                                           ->getMock();
        $this->classMetadataFactory->expects($this->any())
                                   ->method('getMetadataFor')
                                   ->will($this->returnValue($this->classMetadata));
        $configuration->expects($this->once())
                      ->method('getClassMetadataFactoryName')
                      ->will($this->returnValue(get_class($this->classMetadataFactory)));
        $configuration->expects($this->any())
                      ->method('getDefaultManagerServices')
                      ->will($this->returnValue(
                          array(
                                Reference::DBAL_ORM => 'doctrine.orm.entity_manager',
                                Reference::PHPCR    => 'doctrine_phpcr.odm.default_document_manager',
                          )
                      ));
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
                                ->disableOriginalConstructor()
                                ->getMock();
        $this->container->expects($this->any())->method('has')->will($this->returnValue(true));
        $this->container->expects($this->any())
                        ->method('get')
                        ->with($this->logicalOr(
                            $this->equalTo('doctrine.orm.entity_manager'),
                            $this->equalTo('doctrine_phpcr.odm.default_document_manager')
                        ))
                        ->will($this->returnCallback(function ($value) {
                            return 'doctrine.orm.entity_manager' == $value
                                ? $this->objectManager : $this->documentManager;
                        }));
        $this->objectAdapterManager = new ObjectAdapterManager($this->container, $configuration);
    }

    public function testGetManagerByType()
    {
        $this->assertEquals($this->objectManager, $this->objectAdapterManager->getManagerByType('reference-dbal-orm'));
        $this->assertEquals($this->documentManager, $this->objectAdapterManager->getManagerByType('reference-phpcr'));
    }

    /**
     * @expectedException \Doctrine\ORM\ODMAdapter\Exception\ObjectAdapterMangerException
     */
    public function testGetMangerByTypeThrowsException()
    {
        $this->objectAdapterManager->getManagerByType('some-type');
    }
}
