<?php


namespace Doctrine\ORM\ODMAdapter;

/**
 * Events that occur while binding odm documents to an orm entity.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
final class Event {
    const preBindReference = 'preBindReference';
    const postBindReference = 'postBindReference';
    const postLoadReference = 'postLoadReference';
    const preUpdateReference = 'preUpdateReference';
    const postUpdateReference = 'postUpdateReference';
    const preRemoveReference = 'preRemoveReference';
    const postRemoveReference = 'preRemoveReference';
    const loadClassMetadata = 'loadClassMetadata';
    const preFlushReference = 'preFlush';
    const onFlushReference = 'onFlush';
    const postFlushReference = 'postFlush';

    public static $lifecycleCallbacks = array();
}
