<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model;

use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;
use Doctrine\ORM\Mapping as ORM;

/**
 * A class with no explicitly set properties for testing default values.
 *
 * @ODMAdapter\ObjectAdapter
 * @ORM\Entity(table="orm_odm_tests")
 */
class ReferenceMappingObject
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    public $uuid;

    /**
     * @ODMAdapter\ReferencePhpcr(
     *  referencedBy="uuid",
     *  inversedBy="uuid",
     *  targetObject="Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\Document",
     *  name="referencedField",
     *  commonField={
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
