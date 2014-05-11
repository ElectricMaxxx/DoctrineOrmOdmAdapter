<?php


namespace Doctrine\ORM\ODMAdapter\Event;


use Doctrine\Common\EventArgs;
use Doctrine\ORM\ODMAdapter\DocumentAdapterManager;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;

class LoadClassMetadataEventArgs extends EventArgs
{

    /**
     * @var ClassMetadata
     */
    private $classMetadata;
    /**
     * @var DocumentAdapterManager
     */
    private $documentAdapterManager;

    public function __construct(ClassMetadata $classMetadata, DocumentAdapterManager $documentAdapterManager)
    {

        $this->classMetadata = $classMetadata;
        $this->documentAdapterManager = $documentAdapterManager;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @return DocumentAdapterManager
     */
    public function getDocumentAdapterManager()
    {
        return $this->documentAdapterManager;
    }


} 