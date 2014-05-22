<?php

namespace Doctrine\Tests\ORM\ODMAdapter\Functions;

use Doctrine\Tests\Models\InvertedReferenceMappingObject;
use Doctrine\Tests\Models\ReferenceMappingObject;

class ObjectAdapterManagerTest extends BaseFunctionalTestCase
{
    public function testGetMangerByType()
    {
        $object = new ReferenceMappingObject();
        $actual = $this->objectAdapterManager->getManager($object, 'referencedField');

        $this->assertInstanceOf(get_class($this->dm), $actual);

        $invertedObject = new InvertedReferenceMappingObject();
        $actual = $this->objectAdapterManager->getManager($invertedObject, 'referencedField');

        $this->assertInstanceOf(get_class($this->em), $actual);
    }

    public function testPersistObject()
    {
        $object = new ReferenceMappingObject();
        $object->id = 'some-id';
        $object->entityName = 'test-name';
        $referencedObject = new InvertedReferenceMappingObject();
        $referencedObject->docName = 'test-name-on-reference';
        $referencedObject->name = 'test-doc';
        $referencedObject->parentDocument = $this->base;
        $object->referencedField = $referencedObject;

        $this->em->persist($object);
        $this->objectAdapterManager->persistReference($object);
        $this->em->flush();
        $this->objectAdapterManager->flushReference();
        $this->objectAdapterManager->clear();
        $this->em->clear();

        $object = $this->em->find(get_class($object), $object->id);
        $referencedObject = $this->dm->find(get_class($referencedObject), $referencedObject->id);

        $this->assertNotNull($object);
        $this->assertNotNull($referencedObject);
        $this->assertEquals($object->id, $referencedObject->objectId);
        $this->assertEquals('test-name-on-reference', $referencedObject->docName);
        $this->assertEquals('test-name-on-reference', $object->entityName);
    }

    public function testPersistInvertedObject()
    {
        $object = new InvertedReferenceMappingObject();
        $object->docName = 'test-document';
        $object->name = 'test-doc';
        $object->parentDocument = $this->base;
        $referencedObject = new ReferenceMappingObject();
        $referencedObject->entityName = 'test name on orm';
        $referencedObject->id = '1';
        $object->referencedField = $referencedObject;

        $this->dm->persist($object);
        $this->objectAdapterManager->persistReference($object);
        $this->dm->flush();
        $this->objectAdapterManager->flushReference();
        $this->dm->clear();
        $this->objectAdapterManager->clear();

        $referencedObject = $this->em->find(get_class($referencedObject), $referencedObject->id);
        $object = $this->dm->find(null, $object->id);

        $this->assertNotNull($object);
        $this->assertNotNull($referencedObject);
        $this->assertEquals($object->objectId, $referencedObject->id);
        $this->assertEquals('test name on orm', $referencedObject->entityName);
        $this->assertEquals('test name on orm', $object->docName);
    }
}
