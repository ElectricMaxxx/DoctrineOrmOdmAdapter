<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;

/**
 * A class with no explicitly set properties for testing default values.
 * @ODMAdapter\ObjectAdapter
 */
class DefaultMappingDocument
{
    public $objectId;

    /**
     * @ODMAdapter\ReferencePhpcr()
     */
    public $referencedField;
}
