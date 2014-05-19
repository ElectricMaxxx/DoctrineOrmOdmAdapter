<?php

namespace Doctrine\ORM\ODMAdapter\Proxy;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Proxy\ProxyDefinition;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\UnitOfWork;

/**
 * This factory is used to create proxies for referenced objects at runtime.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@onit-gmbh.de>
 */
class ProxyFactory extends AbstractProxyFactory
{
    /**
     * @var ObjectAdapterManager
     */
    public $objectAdapterManager;

    /**
     * @var UnitOfWork The UnitOfWork this factory uses.
     */
    private $unitOfWork;

    /**
     * @var string
     */
    private $proxyNs;

    /**
     * @param ObjectAdapterManager $oam
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadataFactory $proxyDir
     * @param bool|int $proxyNs
     * @param bool $autoGenerate
     */
    public function __construct(ObjectAdapterManager $oam, $proxyDir, $proxyNs, $autoGenerate = false)
    {
        $proxyGenerator = new ProxyGenerator($proxyDir, $proxyNs);

        $proxyGenerator->setPlaceholder('baseProxyInterface', 'Doctrine\ORM\Proxy\ODMAdapter');
        parent::__construct($proxyGenerator, $oam->getMetadataFactory(), $autoGenerate);

        $this->objectAdapterManager = $oam;
        $this->unitOfWork = $this->objectAdapterManager->getUnitOfWork();
        $this->proxyNs = $proxyNs;
    }

    /**
     * {@inheritDoc}
     */
    protected function skipClass(ClassMetadata $metadata)
    {
        /* @var $metadata \Doctrine\ORM\Mapping\ClassMetadataInfo */
        return $metadata->isMappedSuperclass || $metadata->getReflectionClass()->isAbstract();
    }

    /**
     * @return ProxyDefinition
     */
    protected function createProxyDefinition($className)
    {
        $classMetadata = $this->objectAdapterManager->getClassMetadata($className);
    }
}
