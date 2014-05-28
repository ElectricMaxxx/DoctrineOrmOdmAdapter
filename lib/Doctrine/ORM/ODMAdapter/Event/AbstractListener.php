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
        return array('prePersist', 'preUpdate', 'preFlush', 'postLoad', 'preRemove', 'onClear');
    }

    /**
     * Detects if an object is mapped and if it isn't scheduled in the UoW as an reference to avoid
     * circular references.
     *
     * @param $object
     * @return bool
     */
    protected function isReferenceable($object)
    {
        return !$this->objectAdapterManager->hasValidMapping(get_class($object))
                || $this->objectAdapterManager->isReferenced($object)
                ? false : true;
    }
}
