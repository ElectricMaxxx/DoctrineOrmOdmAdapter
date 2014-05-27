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
                    Event::postBindReference,
                    Event::postLoadReference,
                    Event::preUpdateReference,
                    Event::postUpdateReference,
                    Event::preRemoveReference,
                    Event::postRemoveReference,
                    Event::preFlushReference,
                    Event::onFlushReference,
                    Event::postFlushReference,
                    Event::onClear,
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

        $this->assertTrue($listener->preBindReference);
        $this->assertFalse($listener->postBindReference);

        // flush will fire postBindReference and all flush events
        $this->em->flush();

        $this->assertTrue($listener->postBindReference);
        $this->assertTrue($listener->onFlushReference);
        $this->assertTrue($listener->preFlushReference);
        $this->assertTrue($listener->postFlushReference);

        // clear will fire onClear
        $this->em->clear();
        $this->assertTrue($listener->onClear);

        // all others shouldn't be fired
        $this->assertFalse($listener->preUpdateReference);
        $this->assertFalse($listener->postUpdateReference);
        $this->assertFalse($listener->preRemoveReference);
        $this->assertFalse($listener->postRemoveReference);

        /** @var ReferenceMappingObject $object */
        $object = $this->em->find(get_class($object), $object->id);
        /** @var InvertedReferenceMappingObject $referencedObject */
        $referencedObject = $object->referencedField;

        // btw postLoadReference should be fired too
        $this->assertTrue($listener->postLoadReference);

        $this->em->remove($object);

        // preRemove should be there, postRemove not
        $this->assertTrue($listener->preRemoveReference);
        $this->assertFalse($listener->postRemoveReference);

        // just when flush let postRemove be thrown
        $this->em->flush();

        $this->assertTrue($listener->postRemoveReference);
    }
}
