<?php

namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\ODMAdapter\Configuration;

/**
 * The DocumentAdapterManager will combine persistence operation
 * on orm and odm and provide a doctrine common interface.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class DocumentAdapterManager
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var
     */
    protected $classMetdataFactory;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * Both managers needs to be injected in service definition.
     *
     * @param DocumentManager $dm
     * @param ObjectManager $em
     * @param Configuration $config
     * @param EventManager $evm
     */
    public function __construct(DocumentManager $dm, ObjectManager $em, Configuration $config = null, EventManager $evm = null)
    {
        $this->configuration = $config?: new Configuration();
        $this->eventManager = $evm ?: new EventManager();
        $this->dm = $dm;
        $this->em = $em;

        $classMetadataFactoryClass = $this->configuration->getClassMetadataFactoryName();
        $this->classMetdataFactory = new $classMetadataFactoryClass($this);
    }

    /**
     * Factory method for a Document Manager.
     *
     * @param \Doctrine\ODM\PHPCR\DocumentManager $dm
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @param Configuration $configuration
     * @param EventManager $evm
     *
     * @return DocumentAdapterManager
     */
    public static function create(DocumentManager $dm, ObjectManager $em, Configuration $configuration = null, EventManager $evm = null)
    {
        return new self($dm, $em, $configuration, $evm);
    }

    public function bindDocument($object)
    {
        // todo implement that
    }

    public function updateBoundDocument($object)
    {
        // todo implement that
    }

    public function removeDocument($object)
    {

    }

    public function getClassMetadata($className)
    {

    }

    /**
     * @param Configuration $config
     */
    public function setConfiguration($config)
    {
        $this->configuration = $config;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param EventManager $eventManager
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @return EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }


}
