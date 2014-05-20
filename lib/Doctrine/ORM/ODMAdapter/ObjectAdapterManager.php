<?php

namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
use Doctrine\ORM\ODMAdapter\Exception\ObjectAdapterMangerException;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory;
use Doctrine\ORM\ODMAdapter\Proxy\ProxyFactory;
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
     * @var ClassMetadataFactory
     */
    protected $classMetadataFactory;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var UnitOfWork
     */
    protected $unitOfWork;

    /**
     * @param Configuration $config
     * @param EventManager $evm
     */
    public function __construct(Configuration $config = null, EventManager $evm = null)
    {
        $this->configuration = $config?: new Configuration();
        $this->eventManager = $evm ?: new EventManager();
        $classMetadataFactoryClass = $this->configuration->getClassMetadataFactoryName();
        $this->classMetadataFactory = new $classMetadataFactoryClass($this);

        $this->unitOfWork = new UnitOfWork($this);
    }

    /**
     * Factory method for a Document Manager.
     *
     * @param Configuration $configuration
     * @param EventManager $evm
     * @return ObjectAdapterManager
     */
    public static function create(Configuration $configuration = null, EventManager $evm = null)
    {
        return new self($configuration, $evm);
    }

    /**
     * Will trigger the persist call for the referenced objects on its managers.
     *
     * @param $object
     */
    public function persistReference($object)
    {
        $this->unitOfWork->persist($object);
    }

    /**
     * Will trigger a remove() call for all referenced objects on its managers.
     *
     * @param $object
     */
    public function removeReference($object)
    {
        $this->unitOfWork->remove($object);
    }

    /**
     * The referenced objects won't be loaded directly by find() (on the referenced managers),
     * getReference() will be called to avoid performance issues.
     *
     * @param $object
     */
    public function findReference($object)
    {
        $this->unitOfWork->loadReferences($object);
    }

    public function flushReference()
    {
        $this->unitOfWork->commit();
    }

    public function clear()
    {
        $this->unitOfWork->clear();
    }

    /**
     * @param  string        $className
     * @return ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->classMetadataFactory->getMetadataFor($className);
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
        $classMetdata = $this->getClassMetadata(get_class($object));
        $type = $classMetdata->getReferencedType($fieldName);
        if (!$type) {
            throw new MappingException(sprintf('No reference mapping on %s', get_class($object)));
        }

        /** @var ManagerRegistry $registry */
        $registry = $this->configuration->getRegistryByReferenceType($type);
        if (!$registry) {
            throw new MappingException(sprintf('No registry found for mapped reference type %s', $type));
        }

        // try to get the manager of a persisted object
        if ($manager = $registry->getManagerForClass(get_class($object))) {
            print("Class: ".get_class($manager)."\n");
            return $manager;
        }

        // return default instead
        // todo implement a manager mapping
        return $registry->getManager('default');
    }

    /**
     * @param  string $type
     * @return object
     * @throws Exception\ObjectAdapterMangerException
     */
    public function getManagerByType($type)
    {
        return $this->configuration->getRegistryByReferenceType($type);
    }

    /**
     * @return ClassMetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->classMetadataFactory;
    }

    /**
     * @return mixed
     */
    public function getUnitOfWork()
    {
        return $this->getUnitOfWork();
    }
}
