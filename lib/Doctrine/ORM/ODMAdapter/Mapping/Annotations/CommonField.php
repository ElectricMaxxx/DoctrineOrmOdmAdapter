<?php

namespace Doctrine\ORM\ODMAdapter\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
final class CommonField
{
    /**
     * @var string
     */
    public $referencedBy;

    /**
     * @var string
     */
    public $inversedBy;
}
