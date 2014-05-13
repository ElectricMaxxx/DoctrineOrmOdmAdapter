<?php


namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;

class LifecycleEventArgs extends EventArgs
{
    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;
    /**
     * @var null
     */
    private $referencedObject;
    /**
     * @var null
     */
    private $object;

    public function __construct(ObjectAdapterManager $objectAdapterManager, $document = null, $object = null)
    {
        $this->objectAdapterManager = $objectAdapterManager;
        $this->referencedObject = $document;
        $this->object = $object;
    }

    /**
     * @return null
     */
    public function getReferencedObject()
    {
        return $this->referencedObject;
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