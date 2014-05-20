<?php

namespace Doctrine\Tests\ORM\ODMAdapter;
use Doctrine\ORM\ODMAdapter\UnitOfWork;
use Doctrine\Tests\Models\ECommerce\ProductDocument;
use Doctrine\Tests\Models\ECommerce\ProductObject;
use Doctrine\Tests\ODM\PHPCR\Mapping\Model\FieldMappingObject;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\DefaultMappingDocument;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\InvertedReferenceMappingObject;
use Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject;
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
        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
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
                                     ->method('getEventManager')
                                     ->will($this->returnValue($this->eventManager));
        $this->objectAdapterManager->expects($this->any())
                                     ->method('getClassMetadata')
                                     ->will($this->returnValue($this->classMetadata));

        $this->UoW = new UnitOfWork($this->objectAdapterManager);
    }

    public function testPersistNewReference()
    {
        // pre conditions
        $object = new ReferenceMappingObject();
        $object->entityName = 'entity name on object';
        $testReferencedObject = new ProductDocument();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->uuid = 'test-uuid';
        $testReferencedObject->docName = 'Name on document';

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'uuid',
                'referenced-by' => 'uuid',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );
        $commonFieldMappings = array(
            'uuid'      => array(
                'referenced-by' => 'uuid',
                'inversed-by'   => 'uuid',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
                ),
            'entityName' => array(
                'referenced-by' => 'docName',
                'inversed-by'   => 'entityName',
                'target-field'  => 'referencedField',
                'sync-type'     => 'to-reference',
            ),
        );
        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));
        $this->objectAdapterManager->expects($this->once())
                                   ->method('getManager')
                                   ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                   ->will($this->returnValue($this->documentManager));
        $this->documentManager->expects($this->once())
                              ->method('persist')
                              ->with($this->equalTo($testReferencedObject));

        $this->UoW->persist($object);

        $this->assertEquals('test-uuid', $object->uuid);
        $this->assertEquals('entity name on object', $testReferencedObject->docName);

        // check setting on scheduled lists
        $this->UoW->getScheduledObjectForInsert($object, 'referencedField');
        $this->assertEquals($testReferencedObject, $this->UoW->getScheduledObjectForInsert($object, 'referencedField'));

        $expectedReferences = array(
            spl_object_hash($object) => array(
                'referencedField' => $testReferencedObject,
            ),
        );
        $this->assertEquals($expectedReferences, $this->UoW->getScheduledReferencesForInsert());
        $this->assertEquals(array(), $this->UoW->getScheduledReferencesForUpdate());
        $this->assertEquals(array(), $this->UoW->getScheduledReferencesForRemove());


        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testPersistNewInvertedReference()
    {
        // pre conditions
        $object = new InvertedReferenceMappingObject();
        $object->docName = 'name on document';
        $testReferencedObject = new ProductObject();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->id = 'test-id';

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'objectId',
                'referenced-by' => 'id',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
            ),
        );
        $commonFieldMappings = array(
            'objectId'      => array(
                'referenced-by' => 'id',
                'inversed-by'   => 'objectId',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
            'entityName' => array(
                'referenced-by' => 'entityName',
                'inversed-by'   => 'docName',
                'target-field'  => 'referencedField',
                'sync-type'     => 'to-reference',
            ),
        );

        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));
        $this->objectAdapterManager->expects($this->once())
                                   ->method('getManager')
                                   ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                   ->will($this->returnValue($this->objectManager));
        $this->objectManager->expects($this->once())
                              ->method('persist')
                              ->with($this->equalTo($testReferencedObject));

        $this->UoW->persist($object);

        $this->assertEquals('test-id', $object->objectId);
        $this->assertEquals('name on document', $testReferencedObject->entityName);

        // check setting on scheduled lists
        $this->UoW->getScheduledObjectForInsert($object, 'referencedField');
        $this->assertEquals($testReferencedObject, $this->UoW->getScheduledObjectForInsert($object, 'referencedField'));

        $expectedReferences = array(
            spl_object_hash($object) => array(
                'referencedField' => $testReferencedObject,
            ),
        );
        $this->assertEquals($expectedReferences, $this->UoW->getScheduledReferencesForInsert());

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testUpdateReference()
    {
        // pre conditions
        $object = new ReferenceMappingObject();
        $object->entityName = 'entity name on object';
        $object->uuid = 'i am there';
        $testReferencedObject = new ProductDocument();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->uuid = 'test-uuid';
        $testReferencedObject->docName = 'Name on document';

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'uuid',
                'referenced-by' => 'uuid',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );
        $commonFieldMappings = array(
            'uuid'      => array(
                'referenced-by' => 'uuid',
                'inversed-by'   => 'uuid',
                'target-field'  => 'referencedField',
                'sync-type'     => 'to-reference',
            ),
            'entityName' => array(
                'referenced-by' => 'docName',
                'inversed-by'   => 'entityName',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );
        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));
        $this->objectAdapterManager->expects($this->any())
                                   ->method('getManager')
                                   ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                   ->will($this->returnValue($this->documentManager));
        $this->documentManager->expects($this->any())
                              ->method('persist')
                              ->with($this->equalTo($testReferencedObject));
        $this->documentManager->expects($this->any())
                              ->method('getReference')
                              ->with($this->equalTo(get_class($testReferencedObject)), $this->equalTo('i am there'))
                              ->will($this->returnValue($testReferencedObject));
        $this->UoW->persist($object);
        $this->UoW->loadReferences($object);

        $updatedReferencedObject = $object->referencedField;
        $updatedReferencedObject->docName = 'name to update';

        $this->UoW->persist($object);

        $this->assertEquals('name to update', $object->entityName);

        // check setting on scheduled lists
        $this->assertEquals($testReferencedObject, $this->UoW->getScheduledObjectForUpdate($object, 'referencedField'));

        $this->assertCount(1, $this->UoW->getScheduledReferencesForInsert());
        $this->assertCount(0, $this->UoW->getScheduledReferencesForRemove());
        $this->assertCount(1, $this->UoW->getScheduledReferencesForUpdate());

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testUpdateInvertedReference()
    {
        // pre conditions
        $object = new InvertedReferenceMappingObject();
        $object->objectId = 'i am still there';
        $object->docName = 'name on document';
        $testReferencedObject = new ProductObject();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->id = 'test-id';

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'objectId',
                'referenced-by' => 'id',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
            ),
        );
        $commonFieldMappings = array(
            'objectId'      => array(
                'referenced-by' => 'id',
                'inversed-by'   => 'objectId',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
            'entityName' => array(
                'referenced-by' => 'entityName',
                'inversed-by'   => 'docName',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );

        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));
        $this->objectAdapterManager->expects($this->any())
                                   ->method('getManager')
                                   ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                   ->will($this->returnValue($this->objectManager));
        $this->objectManager->expects($this->any())
                              ->method('persist')
                              ->with($this->equalTo($testReferencedObject));
        $this->objectManager->expects($this->any())
                              ->method('getReference')
                              ->with($this->equalTo(get_class($testReferencedObject)), $this->equalTo('test-id'))
                              ->will($this->returnValue($testReferencedObject));
        $this->UoW->persist($object);
        $this->UoW->loadReferences($object);

        $updatedReferencedObject = $object->referencedField;
        $updatedReferencedObject->entityName = 'name to update';

        $this->UoW->persist($object);

        $this->assertEquals('name to update', $object->docName);

        // check setting on scheduled lists
        $this->assertEquals($testReferencedObject, $this->UoW->getScheduledObjectForUpdate($object, 'referencedField'));

        $this->assertCount(1, $this->UoW->getScheduledReferencesForInsert());
        $this->assertCount(0, $this->UoW->getScheduledReferencesForRemove());
        $this->assertCount(1, $this->UoW->getScheduledReferencesForUpdate());

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testRemoveReference()
    {
        // pre conditions
        $object = new ReferenceMappingObject();
        $testReferencedObject = new ProductDocument();
        $object->referencedField = $testReferencedObject;

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'uuid',
                'referenced-by' => 'uuid',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );

        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->objectAdapterManager->expects($this->once())
                                  ->method('getManager')
                                  ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                  ->will($this->returnValue($this->documentManager));
        $this->documentManager->expects($this->once())
                              ->method('remove')
                              ->with($this->equalTo($testReferencedObject));

        $this->UoW->remove($object);

        // check setting on scheduled lists
        $this->UoW->getScheduledObjectForInsert($object, 'referencedField');
        $this->assertEquals($testReferencedObject, $this->UoW->getScheduledObjectForRemove($object, 'referencedField'));

        $expectedReferences = array(
            spl_object_hash($object) => array(
                'referencedField' => $testReferencedObject,
            ),
        );
        $this->assertEquals($expectedReferences, $this->UoW->getScheduledReferencesForRemove());
        $this->assertEquals(array(), $this->UoW->getScheduledReferencesForInsert());
        $this->assertEquals(array(), $this->UoW->getScheduledReferencesForUpdate());

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testRemoveInvertedReference()
    {
        // pre conditions
        $object = new InvertedReferenceMappingObject();
        $testReferencedObject = new ProductObject();
        $object->referencedField = $testReferencedObject;

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'objectId',
                'referenced-by' => 'id',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
            ),
        );

        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->objectAdapterManager->expects($this->once())
                                  ->method('getManager')
                                  ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                  ->will($this->returnValue($this->objectManager));
        $this->objectManager->expects($this->once())
                              ->method('remove')
                              ->with($this->equalTo($testReferencedObject));

        $this->UoW->remove($object);

        // check setting on scheduled lists
        $this->UoW->getScheduledObjectForInsert($object, 'referencedField');
        $this->assertEquals($testReferencedObject, $this->UoW->getScheduledObjectForRemove($object, 'referencedField'));

        $expectedReferences = array(
            spl_object_hash($object) => array(
                'referencedField' => $testReferencedObject,
            ),
        );
        $this->assertEquals($expectedReferences, $this->UoW->getScheduledReferencesForRemove());
        $this->assertEquals(array(), $this->UoW->getScheduledReferencesForInsert());
        $this->assertEquals(array(), $this->UoW->getScheduledReferencesForUpdate());

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testLoadReference()
    {
        // pre conditions
        $object = new ReferenceMappingObject();
        $object->entityName = 'Name on document';
        $object->uuid = 'test-uuid';
        $testReferencedObject = new ProductDocument();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->uuid = 'test-uuid';
        $testReferencedObject->docName = 'Name on document';

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'uuid',
                'referenced-by' => 'uuid',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );

        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->objectAdapterManager->expects($this->once())
                                   ->method('getManager')
                                   ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                   ->will($this->returnValue($this->documentManager));
        $this->documentManager->expects($this->once())
                              ->method('getReference')
                              ->with($this->equalTo(get_class($testReferencedObject)), $this->equalTo('test-uuid'))
                              ->will($this->returnValue($testReferencedObject));

        $this->UoW->loadReferences($object);

        $this->assertEquals($testReferencedObject, $object->referencedField);

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testLoadInvertedReference()
    {
        // pre conditions
        $object = new InvertedReferenceMappingObject();
        $object->docName = 'Name on document';
        $object->objectId = 'test-id';
        $testReferencedObject = new ProductObject();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->id = 'test-id';
        $testReferencedObject->entityName = 'Name on document';

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'objectId',
                'referenced-by' => 'id',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );

        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->objectAdapterManager->expects($this->once())
                                   ->method('getManager')
                                   ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                   ->will($this->returnValue($this->objectManager));
        $this->objectManager->expects($this->once())
                              ->method('getReference')
                              ->with($this->equalTo(get_class($testReferencedObject)), $this->equalTo('test-id'))
                              ->will($this->returnValue($testReferencedObject));

        $this->UoW->loadReferences($object);

        $this->assertEquals($testReferencedObject, $object->referencedField);

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    public function testCommitReference()
    {
        // pre conditions
        $object = new ReferenceMappingObject();
        $object->entityName = 'entity name on object';
        $testReferencedObject = new ProductDocument();
        $object->referencedField = $testReferencedObject;
        $testReferencedObject->uuid = 'test-uuid';
        $testReferencedObject->docName = 'Name on document';

        $referenceMapping = array(
            'referencedField'   => array(
                'inversed-by'   => 'uuid',
                'referenced-by' => 'uuid',
                'target-object' => get_class($testReferencedObject),
                'fieldName'     => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
        );
        $commonFieldMappings = array(
            'uuid'      => array(
                'referenced-by' => 'uuid',
                'inversed-by'   => 'uuid',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
            'entityName' => array(
                'referenced-by' => 'docName',
                'inversed-by'   => 'entityName',
                'target-field'  => 'referencedField',
                'sync-type'     => 'to-reference',
            ),
        );
        $this->classMetadata->expects($this->any())
                            ->method('getReferencedObjects')
                            ->will($this->returnValue($referenceMapping));
        $this->classMetadata->expects($this->any())
                            ->method('getCommonFields')
                            ->will($this->returnValue($commonFieldMappings));
        $this->objectAdapterManager->expects($this->any())
                                   ->method('getManager')
                                   ->with($this->equalTo($object), $this->equalTo('referencedField'))
                                   ->will($this->returnValue($this->documentManager));
        $this->documentManager->expects($this->once())
                              ->method('persist')
                              ->with($this->equalTo($testReferencedObject));

        $this->UoW->persist($object);

        // check setting on scheduled lists
        $this->UoW->getScheduledObjectForInsert($object, 'referencedField');
        $this->assertEquals($testReferencedObject, $this->UoW->getScheduledObjectForInsert($object, 'referencedField'));

        $expectedReferences = array(
            spl_object_hash($object) => array(
                'referencedField' => $testReferencedObject,
            ),
        );
        $this->assertEquals($expectedReferences, $this->UoW->getScheduledReferencesForInsert());

        $this->documentManager->expects($this->once())
                              ->method('flush');

        $this->UoW->commit();

        $this->UoW->clear();
        $this->assertThatItsClear();
    }

    private function assertThatItsClear()
    {
        $this->assertCount(0, $this->UoW->getScheduledReferencesForUpdate());
        $this->assertCount(0, $this->UoW->getScheduledReferencesForRemove());
        $this->assertCount(0, $this->UoW->getScheduledReferencesForInsert());
        $this->assertCount(0, $this->UoW->getScheduledReferencesByManager());
    }
}
