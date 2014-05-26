<?php

namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;

class OrmLifecycleListener extends AbstractListener
{
    public function prePersist(LifecycleEventArgs $event)
    {
        $object = $event->getEntity();
        if ($this->isManagedByBridge($object)) {
            $this->objectAdapterManager->persistReference($object);
        }

    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isManagedByBridge($object)) {
            $this->objectAdapterManager->persistReference($object);
        }
    }

    public function preRemove(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isManagedByBridge($object)) {
            $this->objectAdapterManager->removeReference($object);
        }
    }

    public function onClear(ManagerEventArgs $event)
    {
        $this->objectAdapterManager->clear();
    }

    public function preFlush(FlushEventArguments $event)
    {
        $this->objectAdapterManager->flushReference();
    }
}