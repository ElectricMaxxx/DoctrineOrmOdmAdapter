<?php


namespace Doctrine\ORM\ODMAdapter\Event;


use Doctrine\Common\EventArgs;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;

class LoadClassMetadataEventArgs extends EventArgs
{

    /**
     * @var ClassMetadata
     */
    private $classMetadata;
    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    public function __construct(ClassMetadata $classMetadata, ObjectAdapterManager $objectAdapterManager)
    {

        $this->classMetadata = $classMetadata;
        $this->objectAdapterManager = $objectAdapterManager;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->classMetadata;
    }

    /**
     * @return ObjectAdapterManager
     */
    public function getObjectAdapterManager()
    {
        return $this->objectAdapterManager;
    }


} 