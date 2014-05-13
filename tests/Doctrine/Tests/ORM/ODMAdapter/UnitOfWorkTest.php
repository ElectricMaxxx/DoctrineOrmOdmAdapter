<?php

namespace Doctrine\Tests\ORM\ODMAdapter;
use Doctrine\ORM\ODMAdapter\UnitOfWork;
use Doctrine\Tests\Models\ECommerce\Product;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\CommonFieldMappingObject;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\DefaultMappingObject;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferencedObject;

/**
 * Test for the complete UnitOfWork.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@onit-gmbh.de>
 */
class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    private $objectAdapterManager;

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
        $this->objectAdapterManager = $this->getMockBuilder('Doctrine\ORM\ODMAdapter\ObjectAdapterManager')
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
        $this->objectAdapterManager->expects($this->any())
                                     ->method('getDocumentManager')
                                     ->will($this->returnValue($this->documentManager));
        $this->objectAdapterManager->expects($this->any())
                                     ->method('getObjectManager')
                                     ->will($this->returnValue($this->objectManager));
        $this->objectAdapterManager->expects($this->any())
                                     ->method('getEventManager')
                                     ->will($this->returnValue($this->eventManager));
        $this->objectAdapterManager->expects($this->any())
                                     ->method('getClassMetadata')
                                     ->will($this->returnValue($this->classMetadata));

        $this->UoW = new UnitOfWork($this->objectAdapterManager);
    }


    public function testPersistNew()
    {
        // pre conditions
        $object = new DefaultMappingObject();
        $testReferencedObject = new Product();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->uuid = 'test-uuid';

        $reference = array(
            'inversed-by' => 'uuid',
            'referenced-by' => 'uuid',
            'target-object' => get_class($testReferencedObject),
            'fieldName' => 'referencedField',
            'sync-type'       => 'from-reference',
        );
        // common fields for the pure referecne are from-reference by default for entiy -> document mapping
        $referenceCommonField = array(
            'referenced-by' => 'uuid',
            'inversed-by'   => 'uuid',
            'target-field'  => 'referencedField',
            'sync-type'     => 'from-reference',
        );
        $mapping = array(

            'referencedField' => $reference,
        );
        $commonFieldMappings = array('uuid' => $referenceCommonField);
        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($mapping));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));
        $this->documentManager->expects($this->once())
                              ->method('persist')
                              ->with($this->equalTo($testReferencedObject));

        $this->UoW->persist($object);

        $this->assertEquals('test-uuid', $object->uuid);
    }

    public function testPersistNewWithCommonFieldsToEntity()
    {
        // pre conditions
        $object = new CommonFieldMappingObject();
        $testReferencedObject = new ReferencedObject();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->uuid = 'test-uuid';
        $testReferencedObject->docName = 'doc-value';

        $reference = array(
            'inversed-by'     => 'uuid',
            'referenced-by'   => 'uuid',
            'fieldName'       => 'referencedField',
            'target-object' => get_class($testReferencedObject),
        );
        $referenceMappings = array(
            'referencedField' => $reference,
        );
        // common fields for the pure referecne are from-reference by default for entiy -> document mapping
        $referenceCommonField = array(
            'referenced-by' => 'uuid',
            'inversed-by'   => 'uuid',
            'target-field'  => 'referencedField',
            'sync-type'     => 'from-reference',
        );
        $commonField = array(
            'referenced-by'   => 'docName',
            'inversed-by'     => 'entityName',
            'target-field'    => 'referencedField',
            'sync-type'       => 'from-reference',
        );

        $commonFieldMappings = array('entityName' => $commonField, 'uuid' => $referenceCommonField);
        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMappings));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));

        $this->documentManager->expects($this->once())
                              ->method('persist')
                              ->with($this->equalTo($testReferencedObject));

        $this->UoW->persist($object);

        $this->assertEquals('test-uuid', $object->uuid);
        $this->assertEquals('doc-value', $object->entityName);
    }

    public function testPersistNewWithCommonFieldsToObject()
    {
        // pre conditions
        $object = new CommonFieldMappingObject();
        $object->entityName = 'entity-value';
        $testDocument = new ReferencedObject();
        $object->referencedField = $testDocument;
        $testDocument->uuid = 'test-uuid';

        $reference = array(
            'inversed-by'     => 'uuid',
            'referenced-by'   => 'uuid',
            'fieldName'       => 'referencedField',
            'target-object' => get_class($testDocument),
        );
        $referenceMappings = array(
            'referencedField' => $reference,
        );
        // common fields for the pure referecne are from-reference by default for entiy -> document mapping
        $referenceCommonField = array(
            'referenced-by' => 'uuid',
            'inversed-by'   => 'uuid',
            'target-field'  => 'referencedField',
            'sync-type'     => 'from-reference',
        );
        $commonField = array(
            'referenced-by' => 'docName',
            'inversed-by' => 'entityName',
            'target-field' => 'referencedField',
            'sync-type'       => 'to-reference',
        );
        $commonFieldMappings = array('entityName' => $commonField, 'uuid' => $referenceCommonField);

        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMappings));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));

        $this->documentManager->expects($this->once())
                              ->method('persist')
                              ->with($this->equalTo($testDocument));

        $this->UoW->persist($object);

        $this->assertEquals('test-uuid', $object->uuid);
        $this->assertEquals('entity-value', $testDocument->docName);
    }
}
