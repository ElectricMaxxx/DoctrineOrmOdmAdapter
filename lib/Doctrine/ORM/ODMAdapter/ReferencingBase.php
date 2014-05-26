<?php


namespace Doctrine\ORM\ODMAdapter;

/**
 * There are use cases when it is great to know how the referencing system is.
 *
 * One of those use cases are the event listeners. This library needs to hook on the
 * events of the referencing object to trigger the equal methods on the ObjectAdapterManager.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ReferencingBase
{
    /**
     * The referencing object is mapped as an entity by doctrine-orm.
     */
    const DBAL_ORM = 'dbal_orm';

    /**
     * The referencing object is mapped as an document by the phpcr-odm;
     */
    const PHPCR = 'phpcr';
} 