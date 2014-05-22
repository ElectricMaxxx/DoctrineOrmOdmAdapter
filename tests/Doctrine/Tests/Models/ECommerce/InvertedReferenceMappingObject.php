<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * A class with no explicitly set properties for testing default values.
 * @ODMAdapter\ObjectAdapter
 * @PHPCRODM\Document
 */
class InvertedReferenceMappingObject
{

    /**
     * @PHPCRODM\Id
     */
    protected $id;

    /**
     * @PHPCRODM\Node
     */
    protected $node;

    /**
     *  @PHPCRODM\ParentDocument
     */
    protected $parentDocument;

    /**
     * @PHPCRODM\Nodename
     */
    protected $name;

    /**
     * @PHPCRODM\String
     */
    public $objectId;

    /**
     * @ODMAdapter\ReferenceDbalOrm(
     *  referencedBy="id",
     *  inversedBy="objectId",
     *  targetObject="Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\Object",
     *  name="referencedField",
     *  commonField={
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
