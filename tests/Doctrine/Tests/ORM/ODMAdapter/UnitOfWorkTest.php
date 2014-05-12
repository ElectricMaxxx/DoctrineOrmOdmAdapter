<?php

namespace Doctrine\Tests\ORM\ODMAdapter;
use Doctrine\ORM\ODMAdapter\UnitOfWork;
use Doctrine\Tests\Models\ECommerce\Product;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\DefaultMappingObject;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;

/**
 * Test for the complete UnitOfWork.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@onit-gmbh.de>
 */
class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    private $documentAdapterManager;

    /**
     * @var UnitOfWork
     */
    private $UoW;
    private $documentManager;
    private $objectManager;
    private $eventManager;
    private $classMetadata;

    public function setUp()
    {
        // set up the mocks
        $this->documentAdapterManager = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\DocumentAdapterManager')
                                             ->disableOriginalConstructor()
                                             ->getMock();
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

        // expected getter on document adapter manager
        $this->documentAdapterManager->expects($this->any())
                                     ->method('getDocumentManager')
                                     ->will($this->returnValue($this->documentManager));
        $this->documentAdapterManager->expects($this->any())
                                     ->method('getObjectManager')
                                     ->will($this->returnValue($this->objectManager));
        $this->documentAdapterManager->expects($this->any())
                                     ->method('getEventManager')
                                     ->will($this->returnValue($this->eventManager));
        $this->documentAdapterManager->expects($this->any())
                                     ->method('getClassMetadata')
                                     ->will($this->returnValue($this->classMetadata));

        $this->UoW = new UnitOfWork($this->documentAdapterManager);
    }


    public function testPersistNew()
    {
        // pre conditions
        $object = new DefaultMappingObject();
        $testDocument = new Product();
        $object->document = $testDocument;
        $testDocument->uuid = 'test-uuid';

        $reference = array('inversed-by' => 'uuid', 'referenced-by' => 'uuid');
        $mapping = array(
            'document' => $reference,
        );
        $this->classMetadata->expects($this->any())
                            ->method('getReferencedDocuments')
                            ->will($this->returnValue($mapping));
        $this->classMetadata->expects($this->once())
                            ->method('getReferencedDocument')
                            ->with($this->equalTo('document'))
                            ->will($this->returnValue($reference));

        $this->documentManager->expects($this->once())
                              ->method('persist')
                              ->with($this->equalTo($testDocument));

        $this->UoW->persistNew($object);

        $this->assertEquals('test-uuid', $object->uuid);
    }
}
