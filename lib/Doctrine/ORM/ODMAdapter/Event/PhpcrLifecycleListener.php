<?php

namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class PhpcrLifecycleListener extends AbstractListener
{
    public function prePersist(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isReferenceable($object)) {
            print("HOOK-persistPHPCR\n");
            $this->objectAdapterManager->persistReference($object);
        }

    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isReferenceable($object)) {
            $this->objectAdapterManager->persistReference($object);
        }
    }

    public function preRemove(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if ($this->isReferenceable($object)) {
            $this->objectAdapterManager->removeReference($object);
        }
    }

    public function onClear(ManagerEventArgs $event)
    {
        die("clear");
        $this->objectAdapterManager->clear();
    }

    public function preFlush(ManagerEventArgs $event)
    {
        die('hier');
        $this->objectAdapterManager->flushReference();
    }
}
