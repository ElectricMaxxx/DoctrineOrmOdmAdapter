<?php


namespace Doctrine\ORM\ODMAdapter\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use Doctrine\Common\Persistence\Mapping\MappingException as DoctrineMappingException;
use Doctrine\ORM\ODMAdapter\Reference;
use SimpleXMLElement;

class XmlDriver extends FileDriver{

    const DEFAULT_FILE_EXTENSION = '.dcm.xml';

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
    protected function loadMappingFile($file)
    {
        $result = array();
        $entity = libxml_disable_entity_loader(true);
        $xmlElement = simplexml_load_string(file_get_contents($file));
        libxml_disable_entity_loader($entity);

        foreach (array('object-adapter') as $type) {
            if (isset($xmlElement->$type)) {
                foreach ($xmlElement->$type as $documentElement) {
                    $className = (string) $documentElement['name'];
                    $result[$className] = $documentElement;
                }
            }
        }

        return $result;
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string $className
     * @param ClassMetadata $classMetadata
     * @throws \Doctrine\ORM\ODMAdapter\Exception\MappingException
     *
     * @return void
     */
    public function loadMetadataForClass($className, ClassMetadata $classMetadata)
    {
        /** @var $classMetadata \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata */
        try {
            $xmlRoot = $this->getElement($className);
        } catch (DoctrineMappingException $e) {
            // Convert Exception type for consistency with other drivers
            throw new MappingException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$xmlRoot) {
            return;
        }

        $rootElement = null;
        $mapping = array();
        $types = array(Reference::DBAL_ORM, Reference::PHPCR);

        foreach ($types as $type) {
            if (isset($xmlRoot->{$type})) {
                $rootElement = $xmlRoot->{$type};
                $mapping['type'] = $type;
                break;
            }
        }

        // not supported types won't be parsed
        if ($rootElement instanceof \SimpleXMLElement) {
            $this->extractReferencedObjects($rootElement, $classMetadata, $className, $mapping);
        }
    }

    /**
     * @param SimpleXMLElement $xmlRoot
     * @param \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata|ClassMetadata $class
     * @param $className
     * @param $targetReferencedObjectField
     */
    protected function extractCommonFields(SimpleXMLElement $xmlRoot, ClassMetadata $class, $className, $targetReferencedObjectField)
    {
        if (!isset($xmlRoot->{'common-field'})) {
            return;
        }

        foreach ($xmlRoot->{'common-field'} as $field) {
            $mapping = array('type' => 'common-field');
            $attributes = $field->attributes();
            foreach ($attributes as $key => $value) {
                $mapping[$key] = (string)$value;
            }

            $mapping['target-field'] = $targetReferencedObjectField;

            $class->mapCommonField($mapping);
        }
    }

    /**
     * @param SimpleXMLElement $xmlRoot
     * @param \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata|ClassMetadata $class
     * @param string $className
     * @param array  $mapping
     * @throws \Doctrine\ORM\ODMAdapter\Exception\MappingException
     */
    protected function extractReferencedObjects(SimpleXMLElement $xmlRoot, ClassMetadata $class, $className, $mapping)
    {
        $referenceAttributes = $xmlRoot->attributes();
        foreach ($referenceAttributes as $key => $value) {
            $value = 'null' !== $value ? $value : null;
            $mapping[$key] = (string) $value;
        }

        $mapping['inversed-entity'] = $className;

        if (!isset($mapping['name'])) {
            throw new MappingException('Attribute name needs to be set for reference mapping');
        }

        $mapping['fieldName'] = (string) $mapping['name'];

        if (isset($xmlRoot->{'common-field'})) {
            $this->extractCommonFields($xmlRoot, $class, $className, $mapping['fieldName']);
        }

        $class->mapReferencedObject($mapping);
    }
}
