<?php


namespace Doctrine\ORM\ODMAdapter\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use Doctrine\ORM\ODMAdapter\Mapping\Annotations as Adapter;
use Doctrine\ORM\ODMAdapter\Reference;

class AnnotationDriver extends AbstractAnnotationDriver implements MappingDriver
{
    protected $entityAnnotationClasses = array(
        'Doctrine\ORM\ODMAdapter\Mapping\Annotations\ObjectAdapter' => 0,
    );

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $metadata
     * @throws \Doctrine\ORM\ODMAdapter\Exception\MappingException
     * @return void
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /** @var $class \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata */
        $reflClass = $metadata->getReflectionClass();

        $documentAnnots = array();
        foreach ($this->reader->getClassAnnotations($reflClass) as $annot) {
            foreach ($this->entityAnnotationClasses as $annotClass => $i) {
                if ($annot instanceof $annotClass) {
                    $documentAnnots[$i] = $annot;
                }
            }
        }

        if (!$documentAnnots) {
            throw MappingException::classIsNotAValidDocument($className);
        }

        $metadata->className = $className;


        foreach ($reflClass->getProperties() as $property) {
            if ($metadata->className !== $property->getDeclaringClass()->getName()) {
                continue;
            }

            $mapping = array();
            $mapping['fieldName'] = $property->getName();

            foreach ($this->reader->getPropertyAnnotations($property) as $propertyAnnotation) {
                if ($propertyAnnotation instanceof Adapter\ReferencePhpcr) {
                    $mapping['type'] = Reference::PHPCR;
                    $this->extractReferencedObjects($propertyAnnotation, $metadata, $className, $mapping);
                }
                if ($propertyAnnotation instanceof Adapter\ReferenceDbalOrm) {
                    $mapping['type'] = Reference::DBAL_ORM;
                    $this->extractReferencedObjects($propertyAnnotation, $metadata, $className, $mapping);
                }
            }
        }
    }

    protected function extractReferencedObjects($annotation, ClassMetadata $class, $className, $mapping)
    {
        $mapping['inversed-entity'] = $className;

        // set different fields
        if (isset($annotation->targetObject)) {
            $mapping['target-object'] = $annotation->targetObject;
        }
        if (isset($annotation->referencedBy)) {
            $mapping['referenced-by'] = $annotation->referencedBy;
        }
        if (isset($annotation->inversedBy)) {
            $mapping['inversed-by'] = $annotation->inversedBy;
        }
        if (isset($annotation->name)) {
            $mapping['name'] = $annotation->name;
        }

        if (!isset($mapping['name'])) {
            throw new MappingException('Attribute name needs to be set for reference mapping');
        }

        $mapping['fieldName'] = (string) $mapping['name'];

        if (isset($annotation->commonField)) {
            $this->extractCommonField($annotation->commonField, $class, $className, $mapping['fieldName']);
        }

        $class->mapReferencedObject($mapping);
    }

    protected function extractCommonField($annotations, ClassMetadata $class, $className, $targetReferencedObjectField)
    {
        if (!is_array($annotations)) {
            return;
        }

        foreach ($annotations as $annotation) {
            $mapping = array('type' => 'common-field');
            if (isset($annotation->referencedBy)) {
                $mapping['referenced-by'] = $annotation->referencedBy;
            }
            if (isset($annotation->inversedBy)) {
                $mapping['inversed-by'] = $annotation->inversedBy;
            }
            if (isset($annotation->syncType)) {
                $mapping['sync-type'] = $annotation->inversedBy;
            }
            $mapping['target-field'] = $targetReferencedObjectField;

            $class->mapCommonField($mapping);
        }
    }

    /**
     * Returns whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a MappedSuperclass.
     *
     * @param string $className
     *
     * @return boolean
     */
    public function isTransient($className)
    {
        // TODO: Implement isTransient() method.
    }
}