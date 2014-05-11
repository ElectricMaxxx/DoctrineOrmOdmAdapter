<?php


namespace Doctrine\ORM\ODMAdapter\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as CommonClassMetadata;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ORM\ODMAdapter\Event;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use Doctrine\ORM\ODMAdapter\Mapping\Model\ReferencedOneDocument;
use PHPCR\Util\UUIDHelper;
use ReflectionProperty;

/**
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ClassMetadata implements CommonClassMetadata
{
    /**
     * The class name.
     *
     * @var string
     */
    public $className;

    /**
     * The namespace of the current class.
     *
     * @var string
     */
    protected $namespace;

    /**
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    /**
     * READ-ONLY: The all mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * The mapping definition array has the following values:
     *
     * - <b>fieldName</b> (string)
     * The name of the field in the Document.
     *
     * - <b>id</b> (boolean, optional)
     * Marks the field as the primary key of the document.
     *
     * @var array
     */
    public $mappings = array();

    /**
     * READ-ONLY: The common field mappings of the class.
     *
     * @var array
     */
    public $commonFieldMappings = array();


    /**
     * READ-ONLY: The ReflectionProperty instances of the mapped class.
     *
     * @var ReflectionProperty[]
     */
    public $reflectionFields = array();

    /**
     * READ-ONLY: The registered lifecycle callbacks for documents of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = array();

    /**
     * Contains all referenced documents with the inverse info on entity
     * @var ReferencedOneDocument
     */
    public $referencedDocument;

    protected $prototype;


    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Initializes a new ClassMetadata instance that will hold the
     * object-relational mapping metadata of the class with the given name.
     *
     * @param ReflectionService $reflectionService
     */
    public function initializeReflection(ReflectionService $reflectionService)
    {
        $this->reflectionClass = $reflectionService->getClass($this->className);
        $this->namespace = $reflectionService->getClassNamespace($this->className);
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     *
     * @param ReflectionService $reflectionService
     */
    public function wakeupReflection(ReflectionService $reflectionService)
    {
        $this->reflectionClass = $reflectionService->getClass($this->className);
        $this->namespace = $reflectionService->getClassNamespace($this->className);
        $fieldNames = $this->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $reflectionField = isset($this->mappings[$fieldName]['declared'])
                ? new ReflectionProperty($this->mappings[$fieldName]['declared'], $fieldName)
                : $this->reflectionClass->getProperty($fieldName)
            ;
            $reflectionField->setAccessible(true);
            $this->reflectionFields[$fieldName] = $reflectionField;
        }
    }

    /**
     * Check if the given uuid is valid.
     *
     * @param $uuid
     * @return bool
     */
    public function isValidUuid($uuid)
    {
        return UUIDHelper::isUUID($uuid);
    }

    /**
     * Validate lifecycle callbacks
     *
     * @param ReflectionService $reflectionService
     *
     * @throws MappingException if a declared callback does not exist
     */
    public function validateLifecycleCallbacks(ReflectionService $reflectionService)
    {
        foreach ($this->lifecycleCallbacks as $callbacks) {
            foreach ($callbacks as $callbackFuncName) {
                if (!$reflectionService->hasPublicMethod($this->className, $callbackFuncName)) {
                    throw MappingException::lifecycleCallbackMethodNotFound($this->className, $callbackFuncName);
                }
            }
        }
    }

    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     *
     * @param string $lifecycleEvent
     *
     * @return boolean
     */
    public function hasLifecycleCallbacks($lifecycleEvent)
    {
        return isset($this->lifecycleCallbacks[$lifecycleEvent]);
    }

    /**
     * Gets the registered lifecycle callbacks for an event.
     *
     * @param string $event
     *
     * @return array
     */
    public function getLifecycleCallbacks($event)
    {
        return isset($this->lifecycleCallbacks[$event]) ? $this->lifecycleCallbacks[$event] : array();
    }

    /**
     * Adds a lifecycle callback for documents of this class.
     *
     * Note: If the same callback is registered more than once, the old one
     * will be overridden.
     *
     * @param string $callback
     * @param string $event
     * @throws MappingException
     */
    public function addLifecycleCallback($callback, $event)
    {
        if (!isset(Event::$lifecycleCallbacks[$event])) {
            throw new MappingException("$event is not a valid lifecycle callback event");
        }
        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for documents of this class.
     * Any previously registered callbacks are overwritten.
     *
     * @param array $callbacks
     */
    public function setLifecycleCallbacks(array $callbacks)
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * Gets the ReflectionProperties of the mapped class.
     *
     * @return array An array of \ReflectionProperty instances.
     */
    public function getReflectionProperties()
    {
        return $this->reflectionFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $fieldName
     *
     * @return \ReflectionProperty
     */
    public function getReflectionProperty($fieldName)
    {
        return $this->reflectionFields[$fieldName];
    }

    /**
     * Set the lifecycle callbacks from mapping.
     *
     * @param array $mapping
     */
    public function mapLifecycleCallbacks(array $mapping)
    {
        $this->setLifecycleCallbacks($mapping);
    }

    /**
     * Gets the fully-qualified class name of this persistent class.
     *
     * @return string
     */
    public function getName()
    {
        return $this->className;
    }


    /**
     * @param array         $mapping
     * @param ClassMetadata $inherited  same field of parent document, if any
     * @param bool          $isField    whether this is a field or an association
     * @param string        $phpcrLabel the name for the phpcr thing. usually property,
     *                                  except for child where this is name. referrers
     *                                  use false to not set anything.
     *
     * @return mixed
     *
     * @throws MappingException
     */
    protected function validateAndCompleteFieldMapping(array $mapping, ClassMetadata $inherited = null, $isField = true, $phpcrLabel = 'property')
    {
        if (empty($mapping['fieldName'])) {
            throw new MappingException("Mapping a property requires to specify the name in '{$this->className}'.");
        }

        if (!is_string($mapping['fieldName'])) {
            throw new MappingException("Attribute name must be of type string in '{$this->className}'.");
        }

        if (!$this->reflectionClass->hasProperty($mapping['fieldName'])) {
            throw MappingException::classHasNoField($this->className, $mapping['fieldName']);
        }

        if (empty($mapping['property'])) {
            $mapping['property'] = $mapping['fieldName'];
        }

        if ($phpcrLabel &&
            (!isset($mapping[$phpcrLabel]) || empty($mapping[$phpcrLabel]))
        ) {
            $mapping[$phpcrLabel] = $mapping['fieldName'];
        }

        if (isset($this->mappings[$mapping['fieldName']])) {
            if (!$isField
                || empty($mapping['type'])
                || empty($this->mappings[$mapping['fieldName']])
                || $this->mappings[$mapping['fieldName']]['type'] !== $mapping['type']
            ) {
                throw MappingException::duplicateFieldMapping($this->className, $mapping['fieldName']);
            }
        }

        if (!isset($mapping['type'])) {
            throw MappingException::missingTypeDefinition($this->className, $mapping['fieldName']);
        }

        if ($mapping['type'] === 'int') {
            $mapping['type'] = 'long';
        } elseif ($mapping['type'] === 'float') {
            $mapping['type'] = 'double';
        }

        $reflProp = $this->reflectionClass->getProperty($mapping['fieldName']);
        $reflProp->setAccessible(true);
        $this->reflectionFields[$mapping['fieldName']] = $reflProp;
        $this->mappings[$mapping['fieldName']] = $mapping;

        return $mapping;
    }

    public function mapRefereceOneDocument(array $mapping, ClassMetadata $inherit = null)
    {
        if (null !== $this->getReferencedDocument()) {
            throw new MappingException('It is allowed to map just one document by referenced-one-document');
        }

        if (!$mapping['type'] || $mapping['type'] !== 'reference-one-document') {
            throw new MappingException('Mapping type needs to be reference-one-document');
        }
        $referenceOneDocument = new ReferencedOneDocument();

        if (!isset($mapping['fieldName'])) {
            throw new MappingException('No field name for referenced document found');
        }
        $referenceOneDocument->fieldName = $mapping['fieldName'];
        $mapping['property'] = $mapping['fieldName'];

        if (!isset($mapping['referenced-by'])) {
            throw new MappingException('Documents mapping for referenced-by is missing');
        }
        $referenceOneDocument->referencedBy = (string) $mapping['referenced-by'];

        if (!isset($mapping['inversed-by'])) {
            throw new MappingException('Entities mapping for inversed-by is missing');
        }
        $referenceOneDocument->inversedBy = $mapping['inversed-by'];

        if (!isset($mapping['target-document'])) {
            throw new MappingException('No target-document found while reference-one-document mapping.');
        }

        $referenceOneDocument->targetDocument = $mapping['target-document'];
        $referenceOneDocument->referencingEntity = $mapping['inversed-entity'];

        $this->validateAndCompleteFieldMapping($mapping, $inherit, false, false);

        $this->referencedDocument = $referenceOneDocument;
    }

    /**
     * Map a field.
     *
     * - type - The Doctrine Type of this field.
     * - fieldName - The name of the property/field on the mapped php class
     * - name - The Property key of this field in the PHPCR document
     * - id - True for an ID field.
     *
     * @param array $mapping The mapping information.
     * @param ClassMetadata $inherited
     * @throws MappingException
     */
    public function mapCommonField(array $mapping, ClassMetadata $inherited = null)
    {
        if (!$mapping['type'] || $mapping['type'] !== 'common-field') {
            throw new MappingException('Wrong mapping type given.');
        }

        $this->validateAndCompleteFieldMapping($mapping, $inherited, false, false);

        $this->commonFieldMappings[] = $mapping['fieldName'];
    }

    /**
     * Finalize the mapping and make sure that it is consistent.
     *
     * @todo implement the validation
     * @throws MappingException if inconsistencies are discovered.
     */
    public function validateClassMetadata()
    {

    }

    /**
     * Gets the mapped identifier field name.
     *
     * The returned structure is an array of the identifier field names.
     *
     * @return array
     */
    public function getIdentifier()
    {
        // TODO: Implement getIdentifier() method.
    }

    /**
     * Gets the ReflectionClass instance for this mapped class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        return $this->reflectionClass;
    }

    /**
     * Checks if the given field name is a mapped identifier for this class.
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function isIdentifier($fieldName)
    {
        // TODO: Implement isIdentifier() method.
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName)
    {
        if (null === $fieldName) {
            return false;
        }
        return in_array($fieldName, $this->commonFieldMappings)
        || $this->uuidFieldName === $fieldName
        || $this->documentFieldName === $fieldName
            ;
    }

    /**
     * {@inheritDoc}
     */
    public function getField($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            throw MappingException::fieldNotFound($this->className, $fieldName);
        }

        return $this->mappings[$fieldName];
    }

    /**
     * Checks if the given field is a mapped association for this class.
     *
     * not implemented.
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function hasAssociation($fieldName)
    {
        return;
    }

    /**
     * Checks if the given field is a mapped single valued association for this class.
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function isSingleValuedAssociation($fieldName)
    {
        // TODO: Implement isSingleValuedAssociation() method.
    }

    /**
     * Checks if the given field is a mapped collection valued association for this class.
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        // TODO: Implement isCollectionValuedAssociation() method.
    }


    /**
     * {@inheritDoc}
     */
    public function getFieldNames()
    {
        $fields = $this->commonFieldMappings;
        if (null !== $this->getReferencedDocument()) {
            $fields[] = array(
                'type' => 'referenced-one-document',
                'property' => $this->referencedDocument->fieldName,
                'fieldName' => $this->referencedDocument->fieldName,
                'inversed-by' => $this->referencedDocument->inversedBy,
                'referenced-by' => $this->referencedDocument->referencedBy,
                'target-document' => $this->referencedDocument->targetDocument,
                'inversed-entity' => $this->referencedDocument->referencingEntity,
            );
        }

        return $fields;
    }

    /**
     * Returns an array of identifier field names numerically indexed.
     *
     * @return array
     */
    public function getIdentifierFieldNames()
    {
        // TODO: Implement getIdentifierFieldNames() method.
    }

    /**
     * Returns a numerically indexed list of association names of this persistent class.
     *
     * This array includes identifier associations if present on this class.
     *
     * @return array
     */
    public function getAssociationNames()
    {
        // TODO: Implement getAssociationNames() method.
    }

    /**
     * {@inheritDoc}
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->mappings[$fieldName]) ?
            $this->mappings[$fieldName]['type'] : null;
    }

    /**
     * Returns the target class name of the given association.
     *
     * @param string $assocName
     *
     * @return string
     */
    public function getAssociationTargetClass($assocName)
    {
        // TODO: Implement getAssociationTargetClass() method.
    }

    /**
     * Checks if the association is the inverse side of a bidirectional association.
     *
     * @param string $assocName
     *
     * @return boolean
     */
    public function isAssociationInverseSide($assocName)
    {
        // TODO: Implement isAssociationInverseSide() method.
    }

    /**
     * Returns the target field of the owning side of the association.
     *
     * @param string $assocName
     *
     * @return string
     */
    public function getAssociationMappedByTargetField($assocName)
    {
        // TODO: Implement getAssociationMappedByTargetField() method.
    }

    /**
     * Returns the identifier of this object as an array with field name as key.
     *
     * Has to return an empty array if no identifier isset.
     *
     * @param object $object
     *
     * @return array
     */
    public function getIdentifierValues($object)
    {
        // TODO: Implement getIdentifierValues() method.
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance()
    {
        if ($this->prototype === null) {
            $this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->className), $this->className));
        }

        return clone $this->prototype;
    }

    /**
     * @depends testClassName
     */
    public function testSerialize(ClassMetadata $cm)
    {
        $expected = 'O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":18:{s:8:"nodeType";s:15:"nt:unstructured";s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:2;s:8:"mappings";a:5:{s:2:"id";a:7:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:8:"assigned";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;s:8:"property";s:2:"id";}s:8:"username";a:5:{s:9:"fieldName";s:8:"username";s:8:"property";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:7:"created";a:5:{s:9:"fieldName";s:7:"created";s:8:"property";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:8:"property";s:6:"locale";}s:15:"translatedField";a:7:{s:9:"fieldName";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"translated";b:1;s:8:"property";s:15:"translatedField";s:10:"multivalue";b:0;s:5:"assoc";N;s:8:"nullable";b:0;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:51:"Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:18:"lifecycleCallbacks";a:1:{s:8:"postLoad";a:1:{i:0;s:8:"callback";}}s:13:"localeMapping";s:6:"locale";s:10:"translator";s:9:"attribute";s:18:"translatableFields";a:1:{i:0;s:15:"translatedField";}}';

        $cm->addLifecycleCallback('callback', 'postLoadDocument');
        $this->assertEquals($expected, serialize($cm));
    }

    public function testUnserialize()
    {
        $cm = unserialize('O:40:"Doctrine\ODM\PHPCR\Mapping\ClassMetadata":15:{s:8:"nodeType";s:15:"nt:unstructured";s:10:"identifier";s:2:"id";s:4:"name";s:39:"Doctrine\Tests\ODM\PHPCR\Mapping\Person";s:11:"idGenerator";i:1;s:8:"mappings";a:5:{s:2:"id";a:7:{s:9:"fieldName";s:2:"id";s:2:"id";b:1;s:8:"strategy";s:10:"repository";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;s:8:"property";s:2:"id";}s:8:"username";a:5:{s:9:"fieldName";s:8:"username";s:8:"property";s:8:"username";s:4:"type";s:6:"string";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:7:"created";a:5:{s:9:"fieldName";s:7:"created";s:8:"property";s:7:"created";s:4:"type";s:8:"datetime";s:10:"multivalue";b:0;s:8:"nullable";b:0;}s:6:"locale";a:3:{s:9:"fieldName";s:6:"locale";s:4:"type";s:6:"locale";s:8:"property";s:6:"locale";}s:15:"translatedField";a:7:{s:9:"fieldName";s:15:"translatedField";s:4:"type";s:6:"string";s:10:"translated";b:1;s:8:"property";s:15:"translatedField";s:10:"multivalue";b:0;s:5:"assoc";N;s:8:"nullable";b:0;}}s:13:"fieldMappings";a:4:{i:0;s:2:"id";i:1;s:8:"username";i:2;s:7:"created";i:3;s:15:"translatedField";}s:17:"referenceMappings";a:0:{}s:17:"referrersMappings";a:0:{}s:22:"mixedReferrersMappings";a:0:{}s:16:"childrenMappings";a:0:{}s:13:"childMappings";a:0:{}s:25:"customRepositoryClassName";s:51:"Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository";s:18:"isMappedSuperclass";b:1;s:11:"versionable";b:1;s:18:"lifecycleCallbacks";a:1:{s:8:"postLoad";a:1:{i:0;s:8:"callback";}}}');

        $this->assertInstanceOf('Doctrine\ODM\PHPCR\Mapping\ClassMetadata', $cm);

        $this->assertEquals(array('callback'), $cm->getLifecycleCallbacks('postLoad'));
        $this->assertTrue($cm->isMappedSuperclass);
        $this->assertTrue($cm->versionable);
        $this->assertEquals('Doctrine\Tests\ODM\PHPCR\Mapping\DocumentRepository', $cm->customRepositoryClassName);
    }

    /**
     * @return ReferencedOneDocument
     */
    public function getReferencedDocument()
    {
        return $this->referencedDocument;
    }


}
