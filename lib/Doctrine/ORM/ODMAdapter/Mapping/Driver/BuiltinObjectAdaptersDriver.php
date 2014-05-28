<?php


namespace Doctrine\ORM\ODMAdapter\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * The BuiltinDocumentAdaptersDriver is used internally to make sure
 * that the mapping for the built-in document adapters can be loaded
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Uwe Jäger <uwej711e@googlemail.com>
 * @author      Maximilian Berghoff <maximilian.berghoff@gmx.de>
 */
class BuiltinObjectAdaptersDriver implements MappingDriver
{
    /**
     * namespace of built-in documents
     */
    const NAME_SPACE = 'Doctrine\ODM\PHPCR\Document';

    /**
     * @var MappingDriver
     */
    private $wrappedDriver;

    /**
     * @var AnnotationDriver
     */
    private $builtinDriver;

    /**
     * Create with a driver to wrap
     *
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver $wrappedDriver
     */
    public function __construct(MappingDriver $wrappedDriver)
    {
        $this->wrappedDriver = $wrappedDriver;

        $reader = new AnnotationReader();
        $this->builtinDriver = new AnnotationDriver($reader, array(realpath(__DIR__.'/../../Object')));
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $classMetadata)
    {
        if (strpos($className, self::NAME_SPACE) === 0) {
            $this->builtinDriver->loadMetadataForClass($className, $classMetadata);

            return;
        }

        $this->wrappedDriver->loadMetadataForClass($className, $classMetadata);
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        return array_merge(
            $this->builtinDriver->getAllClassNames(),
            $this->wrappedDriver->getAllClassNames()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($className)
    {
        if (strpos($className, self::NAME_SPACE) === 0) {
            return $this->builtinDriver->isTransient($className);
        }

        return $this->wrappedDriver->isTransient($className);
    }
} 