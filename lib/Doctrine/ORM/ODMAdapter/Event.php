<?php


namespace Doctrine\ORM\ODMAdapter;

/**
 * Events that occur while binding odm documents to an orm entity.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
final class Event {
    const preBindDocument = 'preBindDocument';
    const postBindDocument = 'postBindDocument';
    const postLoadDocument = 'postLoadDocument';
    const preUpdateDocument = 'preUpdateDocument';
    const postUpdateDocument = 'postUpdateDocument';
    const preRemoveDocument = 'preRemoveDocument';
    const postRemoveDocument = 'preRemoveDocument';
    const loadClassMetadata = 'loadClassMetadata';

    public static $lifecycleCallbacks = array();
} 