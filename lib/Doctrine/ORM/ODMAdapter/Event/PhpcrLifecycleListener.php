<?php

namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\OnClearEventArgs;

class PhpcrLifecycleListener extends AbstractListener
{
    public function prePersist(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isReferenceable($object)) {
            $this->objectAdapterManager->persistReference($object);
        }

    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isReferenceable($object)) {
            #$this->objectAdapterManager->persistReference($object);
        }
    }

    public function postLoad(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isReferenceable($object) && !$this->objectAdapterManager->isSleepingProxy($object)) {
            $this->objectAdapterManager->findReference($object);
        }
    }

    public function preRemove(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isReferenceable($object)) {
            $this->objectAdapterManager->removeReference($object);
        }
    }

    public function onClear(OnClearEventArgs $event)
    {
        $this->objectAdapterManager->clear();
    }

    public function preFlush(ManagerEventArgs $event)
    {
        $this->objectAdapterManager->flushReference();
    }
}
