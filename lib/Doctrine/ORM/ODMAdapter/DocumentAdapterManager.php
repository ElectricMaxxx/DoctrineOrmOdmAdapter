<?php

namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\PHPCR\DocumentManager;

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
     * Both managers needs to be injected in service definition.
     *
     * @param DocumentManager $dm
     * @param ObjectManager $em
     * @param Configuration $config
     * @param \Doctrine\Common\EventManager $evm
     */
    public function __construct(DocumentManager $dm,ObjectManager $em, Configuration $config = null, EventManager $evm = null)
    {
        $this->config = $config;
        $this->eventManager = $evm ?: new EventManager();
        $this->dm = $dm;
        $this->em = $em;
    }

    public function bindDocument($object)
    {
        // todo implement that
    }

    public function updateBoundDocument($object)
    {
        // todo implement that
    }

    public function removeDocument($object) {

    }

    public function getClassMetadata($className) {

    }
}
