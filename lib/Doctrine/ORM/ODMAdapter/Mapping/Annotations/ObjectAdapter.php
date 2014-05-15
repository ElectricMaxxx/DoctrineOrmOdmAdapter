<?php


namespace Doctrine\ORM\ODMAdapter\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class ObjectAdapter
{
    /**
     * @var string
     */
    public $name;
}
