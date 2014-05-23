<?php

namespace Doctrine\Tests\ORM\ODMAdapter\Functions;

use Doctrine\Tests\Models\InvertedReferenceMappingObject;
use Doctrine\Tests\Models\ReferenceMappingObject;

class ObjectAdapterManagerTest extends BaseFunctionalTestCase
{
    protected $object;
    protected $referencedObject;

    public function testGetMangerByType()
    {
        $object = new ReferenceMappingObject();
        $actual = $this->objectAdapterManager->getManager($object, 'referencedField');

        $this->assertInstanceOf(get_class($this->dm), $actual);

        $invertedObject = new InvertedReferenceMappingObject();
        $actual = $this->objectAdapterManager->getManager($invertedObject, 'referencedField');

        $this->assertInstanceOf(get_class($this->em), $actual);
    }

    protected function persistObject()
    {
        $this->object = new ReferenceMappingObject();
        $this->object->id = 'some-id';
        $this->object->entityName = 'test-name';
        $this->referencedObject = new InvertedReferenceMappingObject();
        $this->referencedObject->docName = 'test-name-on-reference';
        $this->referencedObject->name = 'test-doc';
        $this->referencedObject->parentDocument = $this->base;
        $this->object->referencedField = $this->referencedObject;

        $this->em->persist($this->object);
        $this->objectAdapterManager->persistReference($this->object);
        $this->em->flush();
        $this->objectAdapterManager->flushReference();
        $this->objectAdapterManager->clear();
        $this->em->clear();
    }

    protected function persistInvertedObject()
    {
        $this->object = new InvertedReferenceMappingObject();
        $this->object->docName = 'test-document';
        $this->object->name = 'test-doc';
        $this->object->parentDocument = $this->base;
        $this->referencedObject = new ReferenceMappingObject();
        $this->referencedObject->entityName = 'test name on orm';
        $this->referencedObject->id = '1';
        $this->object->referencedField = $this->referencedObject;

        $this->dm->persist($this->object);
        $this->objectAdapterManager->persistReference($this->object);
        $this->dm->flush();
        $this->objectAdapterManager->flushReference();
        $this->dm->clear();
        $this->objectAdapterManager->clear();
    }

    public function testPersistObject()
    {
        $this->persistObject();

        $object = $this->em->find(get_class($this->object), $this->object->id);
        $referencedObject = $this->dm->find(get_class($this->referencedObject), $this->referencedObject->id);

        $this->assertNotNull($object);
        $this->assertNotNull($referencedObject);
        $this->assertEquals($this->object->uuid, $referencedObject->uuid);
        $this->assertEquals('test-name-on-reference', $referencedObject->docName);
        $this->assertEquals('test-name-on-reference', $object->entityName);
    }

    public function testPersistInvertedObject()
    {
        $this->persistInvertedObject();

        $referencedObject = $this->em->find(get_class($this->referencedObject), $this->referencedObject->id);
        $object = $this->dm->find(null, $this->object->id);

        $this->assertNotNull($object);
        $this->assertNotNull($referencedObject);
        $this->assertEquals($object->objectId, $referencedObject->id);
        $this->assertEquals('test name on orm', $referencedObject->entityName);
        $this->assertEquals('test name on orm', $object->docName);
    }

    public function testUpdateObject()
    {
        $this->persistObject();

        $object = $this->em->find(get_class($this->object), $this->object->id);

        $this->objectAdapterManager->findReference($object);

        $referencedObject = $object->referencedField;
        $this->assertNotNull($referencedObject);

        $referencedObject->docName = 'updated doc name';


        $this->objectAdapterManager->persistReference($object);
        $this->em->flush();
        $this->objectAdapterManager->flushReference();
        $this->em->clear();
        $this->objectAdapterManager->clear();

        $object = $this->em->find(get_class($this->object), $this->object->id);
        $referencedObject = $this->dm->find(get_class($this->referencedObject), $this->referencedObject->id);

        $this->assertNotNull($object);
        $this->assertNotNull($referencedObject);
        $this->assertEquals($this->object->uuid, $referencedObject->uuid);
        $this->assertEquals('updated doc name', $object->entityName);
        $this->assertEquals('updated doc name', $referencedObject->docName);
    }

    public function testUpdateInvertedObject()
    {
        $this->persistInvertedObject();

        $object = $this->dm->find(get_class($this->object), $this->object->id);

        $this->objectAdapterManager->findReference($object);

        $referencedObject = $object->referencedField;
        $this->assertNotNull($referencedObject);

        $referencedObject->entityName = 'updated entity name';


        $this->objectAdapterManager->persistReference($object);
        $this->dm->flush();
        $this->objectAdapterManager->flushReference();
        $this->dm->clear();
        $this->objectAdapterManager->clear();

        $referencedObject = $this->em->find(get_class($this->referencedObject), $this->referencedObject->id);
        $object = $this->dm->find(get_class($this->object), $this->object->id);

        $this->assertNotNull($object);
        $this->assertNotNull($referencedObject);
        $this->assertEquals($this->object->objectId, $referencedObject->id);
        $this->assertEquals('updated entity name', $object->docName);
        $this->assertEquals('updated entity name', $referencedObject->entityName);
    }

    public function testRemoveObject()
    {
        $this->persistObject();

        $object = $this->em->find(get_class($this->object), $this->object->id);
        $this->objectAdapterManager->findReference($object);

        $this->em->remove($object);
        $this->objectAdapterManager->removeReference($object);
        $this->em->flush();
        $this->objectAdapterManager->flushReference();
        $this->em->clear();
        $this->objectAdapterManager->clear();

        $object = $this->em->find(get_class($this->object), $this->object->id);
        $referencedObject = $this->dm->find(get_class($this->referencedObject), $this->referencedObject->id);

        $this->assertNull($object);
        $this->assertNull($referencedObject);
    }
}
