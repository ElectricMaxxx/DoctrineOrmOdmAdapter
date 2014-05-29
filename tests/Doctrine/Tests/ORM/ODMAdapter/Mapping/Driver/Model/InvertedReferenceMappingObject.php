<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;

/**
 * A class with no explicitly set properties for testing default values.
 * @ODMAdapter\ObjectAdapter
 */
class InvertedReferenceMappingObject
{
    public $objectId;

    /**
     * @ODMAdapter\ReferenceDbalOrm(
     *  referencedBy="id",
     *  inversedBy="objectId",
     *  targetObject="Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\Object",
     *  name="referencedField",
     *  manager="manager",
     *  commonFields={
     *      @ODMAdapter\CommonField(referencedBy="entityName", inversedBy="docName")
     *  }
     * )
     */
    public $referencedField;


    public $docName;
}
