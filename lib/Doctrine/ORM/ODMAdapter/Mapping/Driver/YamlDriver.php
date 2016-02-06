<?php


namespace Doctrine\ORM\ODMAdapter\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Persistence\Mapping\MappingException as DoctrineMappingException;

class YamlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.yml';

    /**
     * {@inheritdoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION)
    {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $classMetadata)
    {
        /** @var $classMetadata \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata */
        try {
            $element = $this->getElement($className);
        } catch (DoctrineMappingException $e) {
            // Convert Exception type for consistency with other drivers
            throw new MappingException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$element) {
            return;
        }

        foreach ($element as $fieldName => $reference) {
            $this->extractReferencedObjects($reference, $classMetadata, $className, $fieldName);
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function loadMappingFile($file)
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException(sprintf('File "%s" not found', $file));
        }

        return Yaml::parse(file_get_contents($file));
    }

    /**
     * @param array $root
     * @param \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata|ClassMetadata $class
     * @param $className
     * @param $targetReferencedObjectField
     */
    public function extractReferencedObjects(array $root, ClassMetadata $class, $className, $targetReferencedObjectField)
    {
        $mapping = array('name' => $targetReferencedObjectField, 'inversed-entity' => $className);
        foreach ($root as $key => $value) {
            if (is_string($value)) {
                $value = 'null' !== $value ? $value : null;
                $mapping[$key] = (string) $value;
            }
        }

        $mapping['fieldName'] = (string) $mapping['name'];

        if (isset($root['common-fields'])) {
            $this->extractCommonFields($root['common-fields'], $class, $className, $targetReferencedObjectField);
        }

        $class->mapReferencedObject($mapping);
    }

    /**
     * @param array $root
     * @param \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata|ClassMetadata $class
     * @param $className
     * @param $targetReferencedObjectField
     */
    public function extractCommonFields(array $root, ClassMetadata $class, $className, $targetReferencedObjectField)
    {
        foreach ($root as $field) {
            $mapping = array('type' => 'common-field');
            foreach ($field as $key => $value) {
                if (is_string($value)) {
                    $value = 'null' !== $value ? $value : null;
                    $mapping[$key] = (string) $value;
                }
            }

            $mapping['target-field'] = $targetReferencedObjectField;

            $class->mapCommonField($mapping);
        }
    }
}
