<?php

namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;

/**
 * Provides an event for all flush events, which just serve the
 * ObjectAdapterManager.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@onit-gmbh.de>
 */
class FlushEventArguments extends EventArgs
{
    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    public function __construct(ObjectAdapterManager $objectAdapterManager)
    {
        $this->objectAdapterManager = $objectAdapterManager;
    }

    /**
     * @return ObjectAdapterManager
     */
    public function getObjectAdapterManager()
    {
        return $this->objectAdapterManager;
    }
}
