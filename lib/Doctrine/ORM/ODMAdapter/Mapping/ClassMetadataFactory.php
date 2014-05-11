<?php


namespace Doctrine\ORM\ODMAdapter\Mapping;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ORM\ODMAdapter\Configuration;
use Doctrine\ORM\ODMAdapter\DocumentAdapterManager;
use Doctrine\ORM\ODMAdapter\Event;
use Doctrine\ORM\ODMAdapter\Exception\MappingException;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a document database.

 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author      Maximilian Berghoff <maximilian.berghoff@gmx.de>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * {@inheritdoc}
     */
    protected $cacheSalt = '\$PHPCRODMCLASSMETADATA';

    /**
     * @var DocumentAdapterManager
     */
    private $documentAdapterManager;

    /**
     *  The used metadata driver.
     *
     * @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    private $driver;

    /**
     * @var EventManager
     */
    private $evm;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param DocumentAdapterManager $dma
     */
    public function __construct(DocumentAdapterManager $dma)
    {
        $this->documentAdapterManager = $dma;

        $this->configuration = $this->documentAdapterManager->getConfiguration();
        $this->setCacheDriver($this->configuration->getMetadataCacheImpl());
        $this->driver = $this->configuration->getMetadataDriverImpl();
        $this->evm = $this->documentAdapterManager->getEventManager();
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     */
    public function getMetadataFor($className)
    {
        $metadata = parent::getMetadataFor($className);
        if ($metadata) {
            return $metadata;
        }
        throw MappingException::classNotMapped($className);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MappingException
     */
    public function loadMetadata($className)
    {
        if (class_exists($className)) {
            return parent::loadMetadata($className);
        }
        throw MappingException::classNotFound($className);
    }

    /**
     * {@inheritdoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }

    /**
     * Gets the fully qualified class-name from the namespace alias.
     *
     * @param string $namespaceAlias
     * @param string $simpleClassName
     *
     * @return string
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        return $this->configuration->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        if ($this->getDriver()) {
            $this->getDriver()->loadMetadataForClass($class->getName(), $class);
        }

        if ($this->evm->hasListeners(Event::loadClassMetadata)) {
            $eventArgs = new Event\LoadClassMetadataEventArgs($class, $this->documentAdapterManager);
            $this->evm->dispatchEvent(Event::loadClassMetadata, $eventArgs);
        }

        $this->validateRuntimeMetadata($class, $parent);
    }


    /**
     * Validate runtime metadata is correctly defined.
     *
     * @param ClassMetadata $class
     * @throws MappingException
     */
    protected function validateRuntimeMetadata($class)
    {
        if (!$class->getReflectionClass()) {
            // only validate if there is a reflection class instance
            return;
        }

        $class->validateClassMetadata();
        $class->validateLifecycleCallbacks($this->getReflectionService());
    }
    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->initialized = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflectionService)
    {
        /* @var $class ClassMetadata */
        $class->initializeReflection($reflectionService);
    }

    /**
     * {@inheritdoc}
     */
    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflectionService)
    {
        /* @var $class ClassMetadata */
        $class->wakeupReflection($reflectionService);
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(ClassMetadataInterface $class)
    {
        return isset($class->isMappedSuperclass) && $class->isMappedSuperclass === false;
    }
}