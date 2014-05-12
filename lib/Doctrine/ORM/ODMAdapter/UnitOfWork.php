<?php


namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\DocumentAdapterManager;
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
     * @var DocumentAdapterManager
     */
    private $documentAdapterManager;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @param DocumentAdapterManager $documentAdapterManager
     */
    public function __construct(DocumentAdapterManager $documentAdapterManager)
    {

        $this->documentAdapterManager = $documentAdapterManager;
        $this->documentManager = $documentAdapterManager->getDocumentManager();
        $this->objectManager = $documentAdapterManager->getObjectManager();
        $this->eventManager = $documentAdapterManager->getEventManager();
        $this->eventListenersInvoker = new ListenersInvoker($documentAdapterManager);
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
        $classMetadata = $this->documentAdapterManager->getClassMetadata(get_class($object));
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
     * @throws UnitOfWorkException
     */
    private function persistNew($object)
    {

        $classMetadata = $this->documentAdapterManager->getClassMetadata(get_class($object));

        $documents = $this->extractDocuments($object, $classMetadata);

        foreach ($documents as $document) {

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::preBindDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::preBindDocument,
                    $document,
                    new LifecycleEventArgs($this->documentAdapterManager, $document, $object),
                    $invoke
                );
            }

            $this->documentManager->persist($document);

            if ($invoke = $this->eventListenersInvoker->getSubscribedSystems($classMetadata, Event::postBindDocument)) {
                $this->eventListenersInvoker->invoke(
                    $classMetadata,
                    Event::postBindDocument,
                    $document,
                    new LifecycleEventArgs($this->documentAdapterManager, $document, $object),
                    $invoke
                );
            }

            $this->syncCommonFields($object, $document, $classMetadata);
        }
    }

    /**
     * Depending on the reference-document mapping there can be several mapped documents.
     * This method tries to extract them based on the mapping from the object and return
     * it as an array to persist or work on them.
     *
     * @param $object
     * @param ClassMetadata $classMetadata
     * @return array
     * @throws Exception\UnitOfWorkException
     */
    private function extractDocuments($object, ClassMetadata $classMetadata)
    {
        $referenceMappings = $classMetadata->getReferencedDocuments();
        $documents = array();

        foreach ($referenceMappings as $fieldName => $reference) {
            $objectReflection = new \ReflectionClass($object);
            $property = $objectReflection->getProperty($fieldName);
            $property->setAccessible(true);
            $document = $property->getValue($object);

            if (!$document) {
                throw new UnitOfWorkException(
                    sprintf(
                        'No document found on %s with mapped document field %s',
                        get_class($object),
                        $fieldName
                    )
                );
            }

            $documents[$fieldName] = $document;
        }

        return $documents;
    }

    public function removeDocument($object)
    {

    }

    private function updateReference($object, $classMetadata)
    {
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
        $referenceMapping = $classMetadata->getReferencedDocuments();

        $matches = 0;
        foreach ($referenceMapping as $reference) {
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
     * @param $document
     * @param ClassMetadata $classMetadata
     * @throws Exception\MappingException
     */
    private function syncCommonFields($object, $document, ClassMetadata $classMetadata)
    {
        $referencedDocument = array_filter(
            $classMetadata->getReferencedDocuments(),
            function ($refDocument) use ($document) {
                return $refDocument['target-document'] === get_class($document);
            }
        );

        if (!$referencedDocument || !is_array($referencedDocument)) {
            return;
        }

        $objectReflection = new \ReflectionClass($object);
        foreach ($referencedDocument as $fieldName => $reference) {
            // get the current document from object
            $objectFieldProperty = $objectReflection->getProperty($fieldName);
            $objectFieldProperty->setAccessible(true);
            $document = $objectFieldProperty->getValue($object);

            if (!$document) {
                throw new MappingException(
                    sprintf('Error when trying to get mapped %s from object %s.', $fieldName, get_class($object))
                );
            }

            // get the common-field mappings for that document
            $commonFieldMappings = $classMetadata->getCommonFields();
            if (!$commonFieldMappings) {
                continue;
            }

            $commonFieldMappings = array_filter($commonFieldMappings, function ($field) use ($fieldName) {
                 return $field['target-field'] === $fieldName;
            });

            $documentReflection = new \ReflectionClass($document);

            foreach ($commonFieldMappings as $commonField) {
                $documentProperty = $documentReflection->getProperty($commonField['referenced-by']);
                $documentProperty->setAccessible(true);

                $objectProperty = $objectReflection->getProperty($commonField['inversed-by']);
                $objectProperty->setAccessible(true);

                if ($commonField['sync-type'] === 'to-document') {
                    $value = $objectProperty->getValue($object);
                    $documentProperty->setValue($document, $value);
                } elseif ($commonField['sync-type'] === 'to-entity') {
                    $value = $documentProperty->getValue($document);
                    $objectProperty->setValue($object, $value);
                }
            }

        }
    }
}
