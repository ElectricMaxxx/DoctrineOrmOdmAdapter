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
    const postRemoveReference = 'postRemoveReference';
    const loadClassMetadata = 'loadClassMetadata';
    const preFlushReference = 'preFlushReference';
    const onFlushReference = 'onFlushReference';
    const postFlushReference = 'postFlushReference';
    const onClear = 'onClear';

    const preReferencing = 'preReferencing';
    const postReferencing = 'postReferencing';
    const postLoadReferencing = 'postLoadReferencing';
    const preRemoveReferencing = 'preRemoveReferencing';

    public static $lifecycleCallbacks = array();
}
