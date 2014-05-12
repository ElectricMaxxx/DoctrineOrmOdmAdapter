<?php


namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\DocumentAdapterManager;
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
     * This method will get the object's document reference by its field
     * mapping, persist that one and store the document's uuid on the object.
     *
     * @param $object
     * @throws UnitOfWorkException
     */
    public function persistNew($object)
    {

        $classMetadata = $this->documentAdapterManager->getClassMetadata(get_class($object));

        $documents = $this->extractDocuments($object, $classMetadata);

        foreach ($documents as $fieldName => $document) {

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

            $this->insertUuid($object, $document, $fieldName, $classMetadata);
        }
    }

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

    /**
     * Easy helper for setting the uuid to the object.
     *
     * @param object        $object
     * @param object        $document
     * @param string        $fieldName
     * @param ClassMetadata $classMetadata
     */
    private function insertUuid($object, $document, $fieldName, ClassMetadata $classMetadata)
    {
        $referenceMapping = $classMetadata->getReferencedDocument($fieldName);

        if (!$referenceMapping) {
            return;
        }

        // todo implement a target-document check

        $documentReflection = new \ReflectionClass($document);
        $fieldName = $documentReflection->getProperty($referenceMapping['referenced-by']);
        $fieldName->setAccessible(true);
        $referencedValue = $fieldName->getValue($document);

        $objectReflection = new \ReflectionClass($object);
        $fieldName = $objectReflection->getProperty($referenceMapping['inversed-by']);
        $fieldName->setAccessible(true);
        $fieldName->setValue($object, $referencedValue);
    }
    public function updateDocument($object)
    {

    }

    public function removeDocument($object)
    {

    }
}
