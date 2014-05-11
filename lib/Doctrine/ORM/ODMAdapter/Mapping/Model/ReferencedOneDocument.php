<?php


namespace Doctrine\ORM\ODMAdapter\Mapping\Model;

/**
 * An OOP wrapper for the Referencing information for entities
 * that reference one document.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ReferencedOneDocument
{
    /**
     * @var string the FQCN for the referenced document
     */
    public $targetDocument;

    /**
     * @var string the field on the document to reference to.
     */
    public $referencedBy;

    /**
     * @var string the fieldName on entity where we will find the target document.
     */
    public $fieldName;

    /**
     * @var string inversed information on entity about the document's referencing field.
     */
    public $inversedBy;

    /**
     * @var string FQCN of the referencing entity.
     */
    public $referencingEntity;
} 