<?php


namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\ORM\ODMAdapter\Event\LifecycleEventArgs;
use Doctrine\ORM\ODMAdapter\Event\ListenersInvoker;
use Doctrine\ORM\ODMAdapter\Event;
use Doctrine\ORM\ODMAdapter\Event\ManagerEventArgs;
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
     *
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
     * The list of all objects that are handled by this UnitOfWork.
     *
     * @var array
     */
    private $objects = array();

    /**
     * List of all referenced objects that are handled by this UnitOfWork
     *
     * @var array
     */
    private $referencedObjects = array();

    /**
     * The list of referenced objects contains all of them sorted by the oid
     * of its containing object and fieldName.
     *
     * All referenced objects for inserts will
     * be stored in here.
     *
     * @var array
     */
    private $insertReferences = array();

    /**
     * The list of referenced objects contains all of them sorted by the oid
     * of its containing object and fieldName.
     *
     * All referenced objects for updates will
     * be stored in here.
     *
     * @var array
     */
    private $updateReferences = array();

    /**
     * The list of referenced objects contains all of them sorted by the oid
     * of its containing object and fieldName.
     *
     * All referenced objects for removes will
     * be stored in here.
     *
     * @var array
     */
    private $removeReferences = array();

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
        $this->doPersist($object, $this->objectAdapterManager->getClassMetadata(get_class($object)));
    }

    /**
     * @param $object
     * @param Mapping\ClassMetadata $classMetadata
     */
    private function doPersist($object, ClassMetadata $classMetadata)
    {
        $objectState = $this->getObjectState($object);

        switch ($objectState) {
            case self::OBJECT_STATE_MANAGED:    // this object is still managed and got its reference
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
        foreach ($this->extractReferencedObjects($object, $classMetadata) as $fieldName => $referencedObject) {
            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::preBindReference)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::preBindReference,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            $this->referencedObjectState[spl_object_hash($referencedObject)] = self::STATE_REFERENCED;
            $this->scheduleReferenceForInsert($object, $referencedObject, $fieldName);

            $manager = $this->objectAdapterManager->getManager($object, $fieldName);
            $manager->persist($referencedObject);

            $this->syncCommonFields($object, $referencedObject, $classMetadata);
        }

        $oid = spl_object_hash($object);
        $this->objects[$oid] = $object;
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

    /**
     * @param $object
     */
    public function remove($object)
    {
        $this->doRemove($object, $this->objectAdapterManager->getClassMetadata(get_class($object)));
    }

    /**
     * @param $object
     * @param ClassMetadata $classMetadata
     */
    private function doRemove($object, ClassMetadata $classMetadata)
    {
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

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems(
                $classMetadata,
                Event::preRemoveReference
            )) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::preRemoveReference,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            // call the remove method on the right manager
            $this->objectAdapterManager->getManager($object, $fieldName)->remove($referencedObject);

            $this->referencedObjectState[spl_object_hash($referencedObject)] = self::STATE_REFERENCED;
            $this->scheduleReferenceForRemove($object, $referencedObject, $fieldName);
        }

        // schedule the object itself and mark it as managed
        $oid = spl_object_hash($object);
        $this->objects[$oid] = $object;
        $this->objectState[spl_object_hash($object)] = self::OBJECT_STATE_MANAGED;
    }

    /**
     * This method does the update of a reference.
     *
     * @param $object
     * @param ClassMetadata $classMetadata
     */
    private function updateReference($object, ClassMetadata $classMetadata)
    {
        foreach ($this->extractReferencedObjects($object, $classMetadata) as $fieldName => $referencedObject) {
            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems(
                $classMetadata,
                Event::preUpdateReference
            )) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::preUpdateReference,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            $this->objectAdapterManager->getManager($object, $fieldName)->persist($referencedObject);

            $this->syncCommonFields($object, $referencedObject, $classMetadata);

            $this->referencedObjectState[spl_object_hash($referencedObject)] = self::STATE_REFERENCED;
            $this->scheduleReferenceForUpdate($object, $referencedObject, $fieldName);
        }

        // schedule the objet itself and mark it as managed
        $oid = spl_object_hash($object);
        $this->objects[$oid] = $object;
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

        return isset($this->objectState[$oid]) ? $this->objectState[$oid] : self::OBJECT_STATE_NEW;
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
                $refl = new \ReflectionClass($refObject['target-object']);
                return $refl->isInstance($referencedObject);
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

        // add this object to the object map
        $oid = spl_object_hash($object);
        $this->objects[$oid] = $object;
        $this->objectState[$oid] = self::OBJECT_STATE_MANAGED;

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

            // add this referenced Object to the map of referenced objects
            $roid = spl_object_hash($referencedObject);
            $this->referencedObjects[$roid] = $referencedObject;
            $this->referencedObjectState[$roid] = self::STATE_REFERENCED;

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems(
                $classMetadata,
                Event::postLoadReference
            )) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::postLoadReference,
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
     */
    public function commit()
    {
        $managedList = $this->getScheduledReferencesByManager();

        if ($this->eventManager->hasListeners(Event::preFlushReference)) {
            $this->eventManager->dispatchEvent(
                Event::preFlushReference,
                new Event\FlushEventArguments($this->objectAdapterManager)
            );
        }

        foreach ($managedList as $value) {
            if ($this->eventManager->hasListeners(Event::onFlushReference)) {
                $this->eventManager->dispatchEvent(
                    Event::onFlushReference,
                    new Event\FlushEventArguments($this->objectAdapterManager)
                );
            }


            $value['manager']->flush();
        }

        if ($this->eventManager->hasListeners(Event::postFlushReference)) {
            $this->eventManager->dispatchEvent(
                Event::postFlushReference,
                new Event\FlushEventArguments($this->objectAdapterManager)
            );
        }

        if ($this->insertReferences) {
            $this->dispatchPostPersist();
        }
        if ($this->updateReferences) {
            $this->dispatchPostUpdate();
        }
        if ($this->removeReferences) {
            $this->dispatchPostRemove();
        }
    }

    /**
     * Will remove all scheduled stuff.
     */
    public function clear()
    {
        // trigger clear on all managers of the referenced objects
        $managedObjects = $this->getScheduledReferencesByManager();
        foreach ($managedObjects as $value) {
            $value['manager']->clear();
        }

        // clear all object stores
        $this->objects =
        $this->objectState =
        $this->referencedObjects =
        $this->referencedObjectState =
        $this->removeReferences =
        $this->insertReferences =
        $this->updateReferences = array();

        if ($this->eventManager->hasListeners(Event::onClear)) {
            $this->eventManager->dispatchEvent(Event::onClear, new ManagerEventArgs($this->objectAdapterManager));
        }
    }


    /**
     * Schedule all referenced objects, which needs to be inserted.
     *
     * @param $object
     * @param $referencedObject
     * @param $fieldName
     * @throws Exception\UnitOfWorkException
     */
    private function scheduleReferenceForInsert($object, $referencedObject, $fieldName)
    {
        $oid = spl_object_hash($object);

        if (isset($this->removeReferences[$oid][$fieldName])) {
            throw new UnitOfWorkException(
                sprintf(
                    '%s can not be scheduled for remove and insert on %s.',
                    get_class($referencedObject),
                    get_class($object)
                )
            );
        }

        if (isset($this->insertReferences[$oid][$fieldName])) {
            throw new UnitOfWorkException(
                sprintf(
                    '%s can not be scheduled twice for reference on %s.',
                    get_class($referencedObject),
                    get_class($object)
                )
            );
        }

        $this->insertReferences[$oid][$fieldName] = $referencedObject;
    }

    /**
     * Schedule all referenced objects, which needs to be updated.
     *
     * @param $object
     * @param $referencedObject
     * @param $fieldName
     * @throws Exception\UnitOfWorkException
     */
    private function scheduleReferenceForUpdate($object, $referencedObject, $fieldName)
    {
        $oid = spl_object_hash($object);

        if (isset($this->removeReferences[$oid][$fieldName])) {
            throw new UnitOfWorkException(
                sprintf('%s can not be scheduled for remove and update on %s.', get_class($object), get_class($object))
            );
        }

        if (isset($this->updateReferences[$oid][$fieldName])) {
            throw new UnitOfWorkException(
                sprintf(
                    '%s can not be scheduled twice for reference on %s.',
                    get_class($referencedObject),
                    get_class($object)
                )
            );
        }

        $this->updateReferences[$oid][$fieldName] = $referencedObject;
    }

    /**
     * Schedule all referenced objects, which needs to be removed.
     *
     * @param $object
     * @param $referencedObject
     * @param $fieldName
     * @throws Exception\UnitOfWorkException
     */
    private function scheduleReferenceForRemove($object, $referencedObject, $fieldName)
    {
        $oid = spl_object_hash($object);

        if (isset($this->removeReferences[$oid][$fieldName])) {
            throw new UnitOfWorkException(
                sprintf(
                    '%s can not be scheduled twice for reference on %s.',
                    get_class($referencedObject),
                    get_class($object)
                )
            );
        }

        $this->removeReferences[$oid][$fieldName] = $referencedObject;
    }

    /**
     * Gets the currently scheduled object references for insert in the UnitOfWork.
     *
     * @return array
     */
    public function getScheduledReferencesForInsert()
    {
        return $this->insertReferences;
    }

    /**
     * Gets the currently scheduled object references for update in the UnitOfWork.
     *
     * @return array
     */
    public function getScheduledReferencesForUpdate()
    {
        return $this->updateReferences;
    }

    /**
     * Gets the currently scheduled object references for remove in the UnitOfWork.
     *
     * @return array
     */
    public function getScheduledReferencesForRemove()
    {
        return $this->removeReferences;
    }

    /**
     * Gets an specific referenced object which is scheduled for insert by its objects, that conains
     * the reference and the field name.
     *
     * @param $object
     * @param $fieldName
     */
    public function getScheduledObjectForInsert($object, $fieldName)
    {
        $oid = spl_object_hash($object);
        if (isset($this->insertReferences[$oid][$fieldName])) {
            return $this->insertReferences[$oid][$fieldName];
        }
    }

    /**
     * Gets an specific referenced object which is scheduled for update by its objects, that conains
     * the reference and the field name.
     *
     * @param $object
     * @param $fieldName
     */
    public function getScheduledObjectForUpdate($object, $fieldName)
    {
        $oid = spl_object_hash($object);
        if (isset($this->updateReferences[$oid][$fieldName])) {
            return $this->updateReferences[$oid][$fieldName];
        }
    }

    /**
     * Gets an specific referenced object which is scheduled for remove, that contains
     * the reference and the field name.
     *
     * @param $object
     * @param $fieldName
     */
    public function getScheduledObjectForRemove($object, $fieldName)
    {
        $oid = spl_object_hash($object);
        if (isset($this->removeReferences[$oid][$fieldName])) {
            return $this->removeReferences[$oid][$fieldName];
        }
    }

    /**
     * Will return a merged array of all scheduled references.
     *
     * @return array
     */
    public function getAllScheduledReferences()
    {
        $scheduledReferences = array();
        $scheduledReferences = array_merge($scheduledReferences, $this->getScheduledReferencesForInsert());
        $scheduledReferences = array_merge($scheduledReferences, $this->getScheduledReferencesForRemove());
        $scheduledReferences = array_merge($scheduledReferences, $this->getScheduledReferencesForUpdate());

        return $scheduledReferences;
    }

    /**
     * To commit all referenced Objects by its managers we need to sort them.
     *
     * @return array
     * @throws Exception\UnitOfWorkException
     */
    public function getScheduledReferencesByManager()
    {
        $managedList = array();
        $scheduledLists = array(
            $this->insertReferences,
            $this->updateReferences,
            $this->removeReferences,
        );

        foreach ($scheduledLists as $scheduleList) {
            foreach ($scheduleList as $oid => $fields) {
                if (!isset($this->objects[$oid])) {
                    throw new UnitOfWorkException('Can not find the object for oid %s', $oid);
                }

                foreach ($fields as $fieldName => $referencedObject) {
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
        }

        return $managedList;
    }

    /**
     * One event will be fired for each referenced object in the list of scheduled objects for inserts.
     */
    private function dispatchPostPersist()
    {
        foreach ($this->insertReferences as $oid => $fields) {
            if (!isset($this->objects[$oid])) {
                continue;
            }

            $object = $this->objects[$oid];
            $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));

            foreach ($fields as $referencedObject) {

                if ($invoke = $this->eventListenersInvoker->getSubscribedSystems(
                    $classMetadata,
                    Event::postBindReference
                )) {
                    $this->eventListenersInvoker->invoke(
                        $classMetadata,
                        Event::postBindReference,
                        $object,
                        new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                        $invoke
                    );
                }
            }
        }
    }

    /**
     * One event will be fired for each referenced object in the list of scheduled objects for updates.
     */
    private function dispatchPostUpdate()
    {
        foreach ($this->updateReferences as $oid => $fields) {
            if (!isset($this->objects[$oid])) {
                continue;
            }

            $object = $this->objects[$oid];
            $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));

            foreach ($fields as $referencedObject) {

                if ($invoke = $this->eventListenersInvoker->getSubscribedSystems(
                    $classMetadata,
                    Event::postUpdateReference
                )) {
                    $this->eventListenersInvoker->invoke(
                        $classMetadata,
                        Event::postUpdateReference,
                        $object,
                        new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                        $invoke
                    );
                }
            }
        }
    }

    /**
     * One event will be fired for each referenced object in the list of scheduled objects for removes.
     */
    private function dispatchPostRemove()
    {
        foreach ($this->removeReferences as $oid => $fields) {
            if (!isset($this->objects[$oid])) {
                continue;
            }

            $object = $this->objects[$oid];
            $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));

            foreach ($fields as $referencedObject) {
                if ($invoke = $this->eventListenersInvoker->getSubscribedSystems(
                    $classMetadata,
                    Event::postRemoveReference
                )) {
                    $this->eventListenersInvoker->invoke(
                        $classMetadata,
                        Event::postRemoveReference,
                        $object,
                        new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                        $invoke
                    );
                }
            }
        }
    }
}
