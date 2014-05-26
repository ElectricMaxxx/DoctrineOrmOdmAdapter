<?php

namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ManagerRegistry;
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

        $this->addListenersToEventManagers();
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

        // todo implement a manager mapping
        /** @var ManagerRegistry $manager */
        $manager = $this->configuration->getManagerByReferenceType($type);
        if (!$manager) {
            throw new MappingException(
                sprintf('No manager found for mapped reference type %s and manager name %s.', $type, 'default')
            );
        }

        return $manager;
    }

    /**
     * @param  string $type
     * @param string $managerName
     * @return object
     */
    public function getManagerByType($type, $managerName = 'default')
    {
        return $this->configuration->getManagerByReferenceType($type, $managerName );
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

    private function addListenersToEventManagers()
    {
        $managers = $this->configuration->getManagers();
        $typeBaseMapping = array(
            Reference::PHPCR => ReferencingBase::PHPCR,
            Reference::DBAL_ORM => ReferencingBase::DBAL_ORM,
        );

        foreach ($managers as $referenceType => $managerList) {
            if (!isset($typeBaseMapping[$referenceType])) {
                continue;
            }

            $listerClassName = $this->configuration->getReferencingBaseListenerByType($typeBaseMapping[$referenceType]);
            foreach ($managerList as $manager) {
                /** @var EventManager $eventManager */
                $eventManager = $manager->getEventManager();
                $eventManager->addEventSubscriber(new $listerClassName($this));
            }
        }
    }
}
