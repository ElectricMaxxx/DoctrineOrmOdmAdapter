<?php

namespace Doctrine\ORM\ODMAdapter\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Reference
{
    /**
     * @var string
     */
    public $targetObject;

    /**
     * @var string
     */
    public $referencedBy;

    /**
     * @var string
     */
    public $inversedBy;

    /**
     * @var string
     */
    public $manager;

    /**
     * @var array
     */
    public $commonFields;
}
