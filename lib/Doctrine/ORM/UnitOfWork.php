<?php


namespace Doctrine\ORM;

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
     * @var ODMAdapter\DocumentAdapterManager
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

        $document = $this->extractDocument($object, $classMetadata);

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

        $this->insertUuid($object, $document, $classMetadata);
    }

    private function extractDocument($object, ClassMetadata $classMetadata)
    {
        $objectReflection = new \ReflectionClass($object);
        $property = $objectReflection->getProperty($classMetadata->getReferencedDocuments()->fieldName);
        $property->setAccessible(true);
        $document = $property->getValue();

        if (!$document) {
            throw new UnitOfWorkException(
                sprintf(
                    'No document found on %s with mapped document field %s',
                    get_class($object),
                    $classMetadata->getReferencedDocuments()->fieldName
                )
            );
        }

        return $document;
    }

    /**
     * Easy helper for setting the uuid to the object.
     *
     * @param object        $object
     * @param object        $document
     * @param ClassMetadata $classMetadata
     */
    private function insertUuid($object, $document, ClassMetadata $classMetadata)
    {
        $documentReflection = new \ReflectionClass($document);
        $property = $documentReflection->getProperty($classMetadata->getReferencedDocuments()->referencedBy);
        $property->setAccessible(true);
        $referencedValue = $property->getValue($document);

        $uuid = $document->getUuid();
        $objectReflection = new \ReflectionClass($object);
        $property = $objectReflection->getProperty($classMetadata->getReferencedDocuments()->inversedBy);
        $property->setAccessible(true);
        $property->setValue($object, $referencedValue);
    }
    public function updateDocument($object)
    {

    }

    public function removeDocument($object)
    {

    }
} 