<?php


namespace Doctrine\ORM\ODMAdapter\Event;


use Doctrine\Common\EventArgs;
use Doctrine\ORM\ODMAdapter\DocumentAdapterManager;

class LifecycleEventArgs extends EventArgs
{
    /**
     * @var DocumentAdapterManager
     */
    private $documentAdapterManager;
    /**
     * @var null
     */
    private $document;
    /**
     * @var null
     */
    private $object;

    public function __construct(DocumentAdapterManager $documentAdapterManager, $document = null, $object = null)
    {
        $this->documentAdapterManager = $documentAdapterManager;
        $this->document = $document;
        $this->object = $object;
    }

    /**
     * @return null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return DocumentAdapterManager
     */
    public function getDocumentAdapterManager()
    {
        return $this->documentAdapterManager;
    }

    /**
     * @return null
     */
    public function getObject()
    {
        return $this->object;
    }
} 