<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;

/**
 * A class with no explicitly set properties for testing default values.
 * @ODMAdapter\ObjectAdapter
 */
class ReferenceMappingObject
{
    public $uuid;

    /**
     * @ODMAdapter\ReferencePhpcr(
     *  referencedBy="uuid",
     *  inversedBy="uuid",
     *  targetObject="Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\Document",
     *  name="referencedField",
     *  manager="manager",
     *  commonField={
     *      @ODMAdapter\CommonField(referencedBy="docName", inversedBy="entityName", syncType="from-reference")
     *  }
     * )
     */
    public $referencedField;

    public $entityName;
}
