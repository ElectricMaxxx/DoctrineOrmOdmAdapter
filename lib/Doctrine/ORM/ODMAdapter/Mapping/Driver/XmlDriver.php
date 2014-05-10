<?php


namespace Doctrine\ORM\ODMAdapter\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ORM\ODMAdapter\Mapping\MappingException;
use Doctrine\Common\Persistence\Mapping\MappingException as DoctrineMappingException;

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

        foreach (array('document-adapter') as $type) {
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
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $class
     * @throws \Doctrine\ORM\ODMAdapter\Mapping\MappingException
     * @internal param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForClass($className, ClassMetadata $class)
    {
        /** @var $class \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata */
        try {
            $xmlRoot = $this->getElement($className);
        } catch (DoctrineMappingException $e) {
            // Convert Exception type for consistency with other drivers
            throw new MappingException($e->getMessage(), $e->getCode(), $e);
        }

        if (!$xmlRoot) {
            return;
        }

        // extract the uuid field name
        if (isset($xmlRoot->uuid)) {
            $class->mapUuid(array('fieldName' => (string) $xmlRoot->uuid->attributes()->name));
        }

        // extract the document field name
        if (isset($xmlRoot->document)) {
            $class->mapDocument(array('fieldName' => (string) $xmlRoot->document->attributes()->name));
        }

        // extract the common fields
        if (isset($xmlRoot->{'common-field'})) {
            foreach ($xmlRoot->{'common-field'} as $field) {
                $mapping = array('type' => 'common-field');
                $attributes = $field->attributes();
                foreach ($attributes as $key => $value) {
                    $mapping[$key] = (string)$value;
                }

                if (!$mapping['document-name']) {
                    throw new MappingException(sprintf('Missing document-name attribute for field of %s', $className));
                }

                if (!$mapping['name']) {
                    throw new MappingException(sprintf('Missing name attribute for field on entity of %s', $className));
                }

                $mapping['fieldName'] = $mapping['name'];

                $class->mapCommonField($mapping);
            }
        }
    }
}