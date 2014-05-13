<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;

/**
 * A class with no explicitly set properties for testing default values.
 * @ODMAdapter\DocumentAdapter
 */
class DefaultMappingDocument
{
    public $objectId;

    public $referencedField;
}
