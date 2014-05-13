<?php


namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;

/**
 * A method invoker based on document lifecycle.
 *
 * @since  1.1
 * @author Fabio B. Silva      <fabio.bat.silva@gmail.com>
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ListenersInvoker
{
    const INVOKE_NONE       = 0;
    // Actually not uses in the phpcr-odm
    const INVOKE_LISTENERS  = 1;
    const INVOKE_CALLBACKS  = 2;
    const INVOKE_MANAGER    = 4;

    /**
     * The EventManager used for dispatching events.
     *
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var DocumentManager
     */
    private $objectAdapterManager;

    /**
     * Initializes a new ListenersInvoker instance.
     *
     * @param ObjectAdapterManager $objectAdapterManager
     */
    public function __construct(ObjectAdapterManager $objectAdapterManager)
    {
        $this->eventManager = $objectAdapterManager->getEventManager();
        $this->objectAdapterManager = $objectAdapterManager;
    }

    /**
     * Get the subscribed event systems
     *
     * @param ClassMetadata $metadata
     * @param string        $eventName The entity lifecycle event.
     *
     * @return integer                 Bitmask of subscribed event systems.
     */
    public function getSubscribedSystems(ClassMetadata $metadata, $eventName)
    {
        $invoke = self::INVOKE_NONE;

        if ($metadata && isset($metadata->lifecycleCallbacks[$eventName])) {
            $invoke |= self::INVOKE_CALLBACKS;
        }

        /*
         * Not implemented for phpcr-odm at the moment.
         *
        if (isset($metadata->documentListeners[$eventName])) {
            $invoke |= self::INVOKE_LISTENERS;
        }
        */

        if ($this->eventManager->hasListeners($eventName)) {
            $invoke |= self::INVOKE_MANAGER;
        }

        return $invoke;
    }

    /**
     * Dispatches the lifecycle event of the given object.
     *
     * @param ClassMetadata $metadata The entity metadata.
     * @param string $eventName The entity lifecycle event.
     * @param object $object The Entity on which the event occurred.
     * @param EventArgs $event The Event args.
     * @param $invoke
     */
    public function invoke(ClassMetadata $metadata, $eventName, $object, EventArgs $event, $invoke)
    {
        if ($invoke & self::INVOKE_CALLBACKS) {
            foreach ($metadata->lifecycleCallbacks[$eventName] as $callback) {
                $object->$callback($event);
            }
        }

        /*
         * Not implemented for phpcr-odm at the moment.
         *
        if ($invoke & self::INVOKE_LISTENERS) {
            foreach ($metadata->documentListeners[$eventName] as $listener) {
                $class      = $listener['class'];
                $method     = $listener['method'];
                $instance   = $this->resolver->resolve($class);

                $instance->$method($document, $event);
            }
        }
        */

        if ($invoke & self::INVOKE_MANAGER) {
            $this->eventManager->dispatchEvent($eventName, $event);
        }
    }
}
