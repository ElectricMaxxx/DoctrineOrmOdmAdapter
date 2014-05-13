<?php


namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\Event\LifecycleEventArgs;
use Doctrine\ORM\ODMAdapter\Event\ListenersInvoker;
use Doctrine\ORM\ODMAdapter\Event;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;
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
    const STATE_NEW = 2;

    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    /**
     * @var EventManager
     */
    private $eventManager;

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
            case self::STATE_NEW:           // complete new reference
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
        $referencedObjects = $this->extractReferencedObjects($object, $classMetadata);

        foreach ($referencedObjects as $fieldName => $referencedObject) {
            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::preBindDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::preBindDocument,
                    $object,
                    new LifecycleEventArgs($this->objectAdapterManager, $referencedObject, $object),
                    $invoke
                );
            }

            $this->objectAdapterManager->getManager($object, $fieldName)->persist($referencedObject);

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
        }
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
        $classMetadata = $this->objectAdapterManager->getClassMetadata(get_class($object));
        $references = $classMetadata->getReferencedObjects();

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
        }
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
        $referencedObjects = $this->extractReferencedObjects($object, $classMetadata);

        foreach ($referencedObjects as $fieldName => $referencedObject) {
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
        }
    }

    /**
     * Wil set return a state of the object depending on the value of the inversed-by field.
     *
     * @todo think about that decision.
     *
     * @param  object        $object
     * @param  ClassMetadata $classMetadata
     * @return int
     */
    private function getObjectState($object, ClassMetadata $classMetadata)
    {
        $referencedObjectMapping = $classMetadata->getReferencedObjects();

        $matches = 0;
        foreach ($referencedObjectMapping as $reference) {
            $objectReflection = new \ReflectionClass($object);
            $property = $objectReflection->getProperty($reference['inversed-by']);
            $property->setAccessible(true);
            $inversedField = $property->getValue($object);

            if (null !== $inversedField) {
                $matches++;
            }
        }

        return $matches === 0 ? self::STATE_NEW : self::STATE_REFERENCED;
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
}
