<?php

namespace Doctrine\Tests\Models;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class with no explicitly set properties for testing default values.
 * @ODMAdapter\ObjectAdapter
 * @PHPCRODM\Document(referenceable=true)
 */
class InvertedReferenceMappingObject
{

    /**
     * @PHPCRODM\Id
     */
    public $id;

    /**
     * @PHPCRODM\Uuid()
     */
    public $uuid;

    /**
     * @PHPCRODM\Node
     */
    public $node;

    /**
     *  @PHPCRODM\ParentDocument
     */
    public $parentDocument;

    /**
     * @PHPCRODM\Nodename
     */
    public $name;

    /**
     * @PHPCRODM\String(nullable=true)
     */
    public $objectId;

    /**
     * @ODMAdapter\ReferenceDbalOrm(
     *  referencedBy="id",
     *  inversedBy="objectId",
     *  targetObject="Doctrine\Tests\Models\ReferenceMappingObject",
     *  commonFields={
     *      @ODMAdapter\CommonField(referencedBy="entityName", inversedBy="docName")
     *  }
     * )
     */
    public $referencedField;

    /**
     * @PHPCRODM\String
     */
    public $docName;
}
