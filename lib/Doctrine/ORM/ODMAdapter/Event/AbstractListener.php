<?php

namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;

abstract class AbstractListener implements EventSubscriber
{
    /**
     * @var ObjectAdapterManager
     */
    protected $objectAdapterManager;

    public function __construct(ObjectAdapterManager $oam)
    {
        $this->objectAdapterManager = $oam;
    }
    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        array('prePersist', 'preUpdate', 'preFlush', 'preRemove', 'onClear');
    }

    /**
     * Detects if an object is mapped or not.
     *
     * @param $object
     * @return bool
     */
    protected function isManagedByBridge($object)
    {
        $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));
        return null === $classMetadata ? false : true;
    }
} 