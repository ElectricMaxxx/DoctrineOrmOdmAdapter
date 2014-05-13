<?php

namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory;

/**
 * The ObjectAdapterManager will combine persistence operation
 * on orm and odm and provide a doctrine common interface.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ObjectAdapterManager
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var ClassMetadataFactory
     */
    protected $classMetdataFactory;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var UnitOfWork
     */
    protected $unitOfWork;

    /**
     * Both managers needs to be injected in service definition.
     *
     * @todo inject the manager as an collection
     * @param DocumentManager $dm
     * @param ObjectManager $em
     * @param Configuration $config
     * @param EventManager $evm
     */
    public function __construct(DocumentManager $dm, ObjectManager $em, Configuration $config = null, EventManager $evm = null)
    {
        $this->configuration = $config?: new Configuration();
        $this->eventManager = $evm ?: new EventManager();
        $this->dm = $dm;
        $this->em = $em;

        $classMetadataFactoryClass = $this->configuration->getClassMetadataFactoryName();
        $this->classMetdataFactory = new $classMetadataFactoryClass($this);

        $this->unitOfWork = new UnitOfWork($this);
    }

    /**
     * Factory method for a Document Manager.
     *
     * @param DocumentManager $dm
     * @param ObjectManager $em
     * @param Configuration $configuration
     * @param EventManager $evm
     *
     * @return ObjectAdapterManager
     */
    public static function create(DocumentManager $dm, ObjectManager $em, Configuration $configuration = null, EventManager $evm = null)
    {
        return new self($dm, $em, $configuration, $evm);
    }

    public function bindDocument($object)
    {
        // todo implement that
    }

    public function updateBoundDocument($object)
    {
        // todo implement that
    }

    public function removeDocument($object)
    {

    }

    /**
     * @param  string        $className
     * @return ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->classMetdataFactory->getMetadataFor($className);
    }

    /**
     * @param Configuration $config
     */
    public function setConfiguration($config)
    {
        $this->configuration = $config;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param EventManager $eventManager
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * This method makes the decision for the right manager depending
     * on the type of mapping and the fieldName.
     *
     * @param object $object
     * @param $fieldName
     * @throws Exception\MappingException
     * @return ObjectManager|DocumentManager
     */
    public function getManager($object, $fieldName)
    {
        $classMetdata = $this->getClassMetadata($object);
        $type = $classMetdata->getReferenceType($fieldName);
        if (!$type) {
            throw new MappingException(sprintf('No reference mapping on %s', get_class($object)));
        }

        $manager = null;
        switch ($type) {
            case Reference::PHPCR:
                $manager = $this->getDocumentManager();
                break;
            case Reference::DBAL_ORM:
                $manager = $this->getObjectManager();
                break;
        }

        if (null === $manager) {
            throw new MappingException(sprintf('No manager found for mapped reference type %s', $type));
        }

        return $manager;
    }

    /**
     * First persist the referenced object with its own manager an doing
     * the sync of the common fields.
     *
     * @param $object
     */
    public function bindReference($object)
    {
        $this->unitOfWork->persist($object);
    }

    /**
     * To update a referenced object on a given one with syncing the
     * common fields.
     *
     * @param $object
     */
    public function updateReference($object)
    {
        $this->unitOfWork->persist($object);
    }

    /**
     * To remove a referenced object from a given object.
     *
     * @param object $object
     */
    public function removeReference($object)
    {
        $this->unitOfWork->removeReferencedObject($object);
    }
}
