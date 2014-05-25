<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Functions;


use Doctrine\ORM\ODMAdapter\Event;
use Doctrine\Tests\Models\InvertedReferenceMappingObject;
use Doctrine\Tests\Models\ReferenceMappingObject;
use Doctrine\Tests\ORM\ODMAdapter\Functions\Model\TestEventListener;

class EventManagerTest extends BaseFunctionalTestCase
{
    public function testTriggerEvents()
    {
        $listener = new TestEventListener();
        $this->objectAdapterManager
            ->getEventManager()
            ->addEventListener(
                array(
                    Event::preBindReference,
                ),
                $listener
            );

        $object = new ReferenceMappingObject();
        $referencedObject = new InvertedReferenceMappingObject();
        $referencedObject->name = 'event-test';
        $referencedObject->parentDocument = $this->base;
        $referencedObject->docName = 'name on entity';
        $object->referencedField = $referencedObject;
        $object->entityName = 'name on entity';
        $object->id = 'test-id';

        $this->em->persist($object);
        $this->objectAdapterManager->persistReference($object);

        $this->assertTrue($listener->preBindReference);

    }
}
