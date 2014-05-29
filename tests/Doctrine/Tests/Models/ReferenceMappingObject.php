<?php

namespace Doctrine\Tests\Models;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;
use Doctrine\ORM\Mapping as ORM;

/**
 * A class with no explicitly set properties for testing default values.
 *
 * @ORM\Entity
 * @ORM\Table(name="objects")
 * @ODMAdapter\ObjectAdapter
 */
class ReferenceMappingObject
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $uuid;

    /**
     * @ODMAdapter\ReferencePhpcr(
     *  referencedBy="uuid",
     *  inversedBy="uuid",
     *  targetObject="Doctrine\Tests\Models\InvertedReferenceMappingObject",
     *  commonFields={
     *      @ODMAdapter\CommonField(referencedBy="docName", inversedBy="entityName")
     *  }
     * )
     */
    public $referencedField;

    /**
     * @ORM\Column(type="string")
     */
    public $entityName;
}
