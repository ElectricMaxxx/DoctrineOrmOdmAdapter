<?php

namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use Doctrine\ORM\ODMAdapter\Exception\ObjectAdapterMangerException;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The ObjectAdapterManager will combine persistence operation
 * on orm and odm and provide a doctrine common interface.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ObjectAdapterManager
{
    /**
     * List of all available managers with the reference type as key.
     *
     * @var array
     */
    protected $manager;

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
     * @var ContainerInterface
     */
    private $container;

    /**
     * Both managers needs to be injected in service definition.
     *
     * @todo inject the manager as an collection
     * @param ContainerInterface $container
     * @param Configuration $config
     * @param EventManager $evm
     * @internal param array $manager
     * @internal param \Doctrine\ODM\PHPCR\DocumentManager $dm
     * @internal param \Doctrine\Common\Persistence\ObjectManager $em
     */
    public function __construct(ContainerInterface $container, Configuration $config = null, EventManager $evm = null)
    {
        $this->configuration = $config?: new Configuration();
        $this->eventManager = $evm ?: new EventManager();
        $this->container = $container;
        $classMetadataFactoryClass = $this->configuration->getClassMetadataFactoryName();
        $this->classMetdataFactory = new $classMetadataFactoryClass($this);

        $this->unitOfWork = new UnitOfWork($this);

        $this->setupMangerList();
    }

    /**
     * Method checks the managers that are injected in constructor.
     *
     * Mangers with right type will be stored in an array to use them.
     *
     */
    private function setupMangerList()
    {
        $referenceTypes = array(Reference::DBAL_ORM, Reference::PHPCR);
        $managers = $this->configuration->getDefaultManagerServices();
        foreach ($managers as $referenceType => $managerServiceId) {
            if (!in_array($referenceType, $referenceTypes)) {
                continue;
            }

            if (!$this->container->has($managerServiceId)) {
                continue;
            }

            $this->manager[$referenceType] = $this->container->get($managerServiceId);
        }
    }

    /**
     * Factory method for a Document Manager.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param Configuration $configuration
     * @param EventManager $evm
     * @return ObjectAdapterManager
     */
    public static function create(ContainerInterface $container, Configuration $configuration = null, EventManager $evm = null)
    {
        return new self($container, $configuration, $evm);
    }

    public function persistReference($object)
    {
        $this->unitOfWork->persist($object);
    }

    public function removeReferencce($object)
    {
        $this->unitOfWork->removeReferencedObject($object);
    }

    public function findReference($object)
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
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
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

        $manager = in_array($type, $this->manager) ? $this->manager[$type] : null;

        if (null === $manager) {
            throw new MappingException(sprintf('No manager found for mapped reference type %s', $type));
        }

        return $manager;
    }

    /**
     * @param  string $type
     * @return object
     * @throws Exception\ObjectAdapterMangerException
     */
    public function getManagerByType($type)
    {
        if (!array_key_exists($type, $this->manager)) {
            throw new ObjectAdapterMangerException('Can not find a manager for reference type '.$type);
        }

        return $this->manager[$type];
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
