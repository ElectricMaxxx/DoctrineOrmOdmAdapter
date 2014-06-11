<?php

namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;

class ReferencingLifecycleEventArgs extends EventArgs
{
    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    /**
     * @var null
     */
    private $object;

    public function __construct(ObjectAdapterManager $objectAdapterManager, $object = null)
    {
        $this->objectAdapterManager = $objectAdapterManager;
        $this->object = $object;
    }


    /**
     * @return ObjectAdapterManager
     */
    public function getObjectAdapterManager()
    {
        return $this->objectAdapterManager;
    }

    /**
     * @return null
     */
    public function getObject()
    {
        return $this->object;
    }
}
