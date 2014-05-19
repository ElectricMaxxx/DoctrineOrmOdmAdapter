<?php


namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\ORM\ODMAdapter\Event\LifecycleEventArgs;
use Doctrine\ORM\ODMAdapter\Event\ListenersInvoker;
use Doctrine\ORM\ODMAdapter\Event;
use Doctrine\ORM\ODMAdapter\Exception\UnitOfWorkException;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;

/**
 * Unit of work class
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class UnitOfWork
{
    /**
     * An object state for referenced objects, means when fields for inversed-by
     * mappings are set.
     */
    const STATE_REFERENCED = 1;

    /**
     * An object state, when one or more references arn't set.
     */
    const OBJECT_STATE_NEW = 2;

    /**
     * An object state, when it is managed.
     */
    const OBJECT_STATE_MANAGED = 4;

    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * List of managed or new states for the objects.
     *
     * @var array
     */
    private $objectState = array();

    /**
     * List of all referenced objects.
     *
     * @var array
     */
    private $referencedObjectState = array();

    /**
     * The list of referenced objects contains all of them sorted by the oid
     * of its containing object and fieldName. So we will be able to commit
     * them all depending on their managers.
     *
     * @var array
     */
    private $referencedObjects = array();

    /**
     * The list of all objects that are handled by this UnitOfWork.
     *
     * @var array
     */
    private $objects;

    /**
     * @param ObjectAdapterManager $objectAdapterManager
     */
    public function __construct(ObjectAdapterManager $objectAdapterManager)
    {

        $this->objectAdapterManager = $objectAdapterManager;
        $this->eventManager = $objectAdapterManager->getEventManager();
        $this->eventListenersInvoker = new ListenersInvoker($objectAdapterManager);
    }

    /**
     * Persist a referenced document on an object as part of the current unit of work.
     *
     * @param $object
     */
    public function persist($object)
    {
        $this->doPersist($object);
    }

    private function doPersist($object)
    {
        $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));
        $objectState = $this->getObjectState($object, $classMetadata);

        switch ($objectState) {
            case self::STATE_REFERENCED:    // this object is still managed and got its reference
                $this->updateReference($object, $classMetadata);
                break;
            case self::OBJECT_STATE_NEW:           // complete new reference
                $this->persistNew($object, $classMetadata);
                break;
        }
    }

    /**
     * This method will get the object's document reference by its field
     * mapping, persist that one and store the document's uuid on the object.
     *
     * @param $object
     * @param $classMetadata
     */
    private function persistNew($object, $classMetadata)
    {
        $oid = spl_object_hash($object);
        $this->objects[$oid] = $object;

        foreach ($this->extractReferencedObjects($object, $classMetadata) as $fieldName => $referencedObject) {
            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::preBindDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::preBindDocument,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            $manager = $this->objectAdapterManager->getManager($object, $fieldName);
            $manager->persist($referencedObject);

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::postBindDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::postBindDocument,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            $this->syncCommonFields($object, $referencedObject, $classMetadata);

            $this->referencedObjectState[spl_object_hash($referencedObject)] = self::STATE_REFERENCED;

            $this->scheduleForReference($object, $referencedObject, $fieldName);
        }

        $this->objectState[$oid] = self::OBJECT_STATE_MANAGED;
    }

    /**
     * Depending on the reference type mapping there can
     * be several mapped documents. This method tries to extract them based on
     * the mapping from the object and return
     * it as an array to persist or work on them.
     *
     * @param object        $object
     * @param ClassMetadata $classMetadata
     * @return array
     * @throws Exception\UnitOfWorkException
     */
    private function extractReferencedObjects($object, ClassMetadata $classMetadata)
    {
        $referencedObjetMappings = $classMetadata->getReferencedObjects();
        $referencedObjects = array();

        foreach ($referencedObjetMappings as $fieldName => $reference) {
            $objectReflection = new \ReflectionClass($object);
            $property = $objectReflection->getProperty($fieldName);
            $property->setAccessible(true);
            $referencedObject = $property->getValue($object);

            if (!$referencedObject) {
                throw new UnitOfWorkException(
                    sprintf(
                        'No object found on %s with mapped reference object field %s',
                        get_class($object),
                        $fieldName
                    )
                );
            }

            $referencedObjects[$fieldName] = $referencedObject;
        }

        return $referencedObjects;
    }


    public function removeReferencedObject($object)
    {
        $oid = spl_object_hash($object);
        $this->objects[$oid] = $object;
        $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));
        $references = $classMetadata->getReferencedObjects();

        $this->objectState[spl_object_hash($object)] = self::OBJECT_STATE_MANAGED;

        $objectReflection = new \ReflectionClass($object);
        foreach ($references as $fieldName => $reference) {
            $objectProperty = $objectReflection->getProperty($fieldName);
            $objectProperty->setAccessible(true);
            $referencedObject = $objectProperty->getValue($object);
            if (!$referencedObject) {
                continue;
            }

            // call the remove method on the right manager
            $this->objectAdapterManager->getManager($object, $fieldName)->remove($referencedObject);

            $this->referencedObjectState[spl_object_hash($referencedObject)] = self::STATE_REFERENCED;

            $this->scheduleForReference($object, $referencedObject, $fieldName);
        }

        $this->objectState[spl_object_hash($object)] = self::OBJECT_STATE_MANAGED;
    }

    /**
     * This method does the update of a reference.
     *
     * @todo do i really need a separate method? the only difference are the events.
     * @param $object
     * @param ClassMetadata $classMetadata
     */
    private function updateReference($object, ClassMetadata $classMetadata)
    {
        $oid = spl_object_hash($object);
        $this->objects[$oid] = $object;

        foreach ($this->extractReferencedObjects($object, $classMetadata) as $fieldName => $referencedObject) {
            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::preUpdateDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::preUpdateDocument,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            $this->objectAdapterManager->getManager($object, $fieldName)->persist($referencedObject);

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::postUpdateDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::postUpdateDocument,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            $this->syncCommonFields($object, $referencedObject, $classMetadata);

            $this->scheduleForReference($object, $referencedObject, $fieldName);
        }

        $this->objectState[$oid] = self::OBJECT_STATE_MANAGED;
    }

    /**
     * Wil set return a state of the object depending on the value of the inversed-by field.
     *
     * @param  object        $object
     * @return int
     */
    private function getObjectState($object)
    {
        $oid = spl_object_hash($object);

        if (!isset($this->objectState[$oid])) {
            return self::OBJECT_STATE_NEW;
        }

        return $this->objectState[$oid];
    }

    /**
     * This method will have a look into the mappings and figure out if the current
     * document has got some mapped common fields and sync them by the mapped sync-type.
     *
     * @param $object
     * @param $referencedObject
     * @param ClassMetadata $classMetadata
     * @throws Exception\MappingException
     */
    private function syncCommonFields($object, $referencedObject, ClassMetadata $classMetadata)
    {
        $referencedObjets = array_filter(
            $classMetadata->getReferencedObjects(),
            function ($refObject) use ($referencedObject) {
                return $refObject['target-object'] === get_class($referencedObject);
            }
        );

        if (!$referencedObjets || !is_array($referencedObjets)) {
            return;
        }

        $objectReflection = new \ReflectionClass($object);
        foreach ($referencedObjets as $fieldName => $reference) {
            $commonFieldMappings = $classMetadata->getCommonFields();
            if (!$commonFieldMappings) {
                continue;
            }

            $commonFieldMappings = array_filter($commonFieldMappings, function ($field) use ($fieldName) {
                 return $field['target-field'] === $fieldName;
            });

            $referencedObjectReflection = new \ReflectionClass($referencedObject);

            foreach ($commonFieldMappings as $commonField) {
                $referencedObjectProperty = $referencedObjectReflection->getProperty($commonField['referenced-by']);
                $referencedObjectProperty->setAccessible(true);

                $objectProperty = $objectReflection->getProperty($commonField['inversed-by']);
                $objectProperty->setAccessible(true);

                if ($commonField['sync-type'] === 'to-reference') {
                    $value = $objectProperty->getValue($object);
                    $referencedObjectProperty->setValue($referencedObject, $value);
                } elseif ($commonField['sync-type'] === 'from-reference') {
                    $value = $referencedObjectProperty->getValue($referencedObject);
                    $objectProperty->setValue($object, $value);
                }
            }

        }
    }

    /**
     * Loads a referenced object by its value on inversed-by property of the object holding the reference.
     *
     * @param $object
     */
    public function loadReferences($object)
    {
        $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));
        $referencedObjects = $classMetadata->getReferencedObjects();

        $objectReflection = new \ReflectionClass($object);
        foreach ($referencedObjects as $fieldName => $reference) {
            $objectProperty = $objectReflection->getProperty($reference['inversed-by']);
            $objectProperty->setAccessible(true);
            $objectValue = $objectProperty->getValue($object);

            $referencedObject = $this->objectAdapterManager
                                     ->getManager($object, $fieldName)
                                     ->getReference($reference['target-object'], $objectValue);

            $objectProperty = $objectReflection->getProperty($fieldName);
            $objectProperty->setAccessible(true);
            $objectProperty->setValue($object, $referencedObject);

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::postLoadDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::postLoadDocument,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }
        }
    }

    /**
     * Will commit all scheduled stuff.
     *
     * That means all managers needs to be flushed. So we need to get all
     * referenced objects with its managers
     *
     * @todo add the events for that
     */
    public function commit()
    {
        $managedList = $this->getScheduledReferencesByManager();

        foreach ($managedList as $value) {
            foreach ($value['referenced-objects'] as $referencedObject) {
                // todo fire preFlush event here

            }

            $value['manager']->flush();
        }
    }

    /**
     * Checks if the object is still scheduled and will insert it to the list.
     *
     * @param $object
     * @param $referencedObject
     * @param $fieldName
     * @throws Exception\UnitOfWorkException
     */
    private function scheduleForReference($object, $referencedObject, $fieldName)
    {
        $oid = spl_object_hash($object);

        if (isset($this->referencedObjects[$oid][$fieldName])) {
            throw new UnitOfWorkException(
                sprintf('%s can not be scheduled twice for reference.', get_class($object))
            );
        }

        $this->referencedObjects[$oid][$fieldName] = $referencedObject;
    }

    /**
     * Gets the currently scheduled object references in the UnitOfWork.
     *
     * @return array
     */
    public function getScheduledReferences()
    {
        return $this->referencedObjects;
    }

    /**
     * Gets an specific referenced object that is scheduled on an object by its
     * fieldName.
     *
     * @param $object
     * @param $fieldName
     */
    public function getScheduledReference($object, $fieldName)
    {
        $oid = spl_object_hash($object);
        if (isset($this->referencedObjects[$oid][$fieldName])) {
            return $this->referencedObjects[$oid][$fieldName];
        }
    }

    public function getScheduledReferencesByManager()
    {
        $managedList = array();

        foreach ($this->getScheduledReferences() as $oid => $fields) {
            if (!isset($this->objects[$oid])) {
                throw new UnitOfWorkException('Can not find the object for oid %s', $oid);
            }

            foreach($fields as $fieldName => $referencedObject) {
                $object = $this->objects[$oid];
                $manager = $this->objectAdapterManager->getManager($object, $fieldName);
                $managerClass = get_class($manager);
                if (!isset($managedList[$managerClass])) {
                    $managedList[$managerClass]['manager'] = $manager;
                    $managedList[$managerClass]['referenced-objects'] = array();
                }
                $managedList[$managerClass]['referenced-objects'][] = $referencedObject;
            }
        }

        return $managedList;
    }
}
