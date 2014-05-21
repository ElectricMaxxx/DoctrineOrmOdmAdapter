<?php


namespace Doctrine\ORM\ODMAdapter;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\ODMAdapter\Exception\ConfigurationException;
use Doctrine\ORM\ODMAdapter\Mapping\Driver\BuiltinObjectAdaptersDriver;
use PHPCR\Util\UUIDHelper;

/**
 * Configuration class
 *
 * @license     http://www.opensource.org/licenses/MIT-license.php MIT license
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 * @author      Pascal Helfenstein <nicam@nicam.ch>
 * @author      Maximilian Berghoff <maximilian.berghoff@gmx.de>
 */
class Configuration
{
    /**
     * Array of attributes for this configuration instance.
     *
     * @var array $attributes
     */
    private $attributes = array(
        'writeDoctrineMetadata'    => true,
        'validateDoctrineMetadata' => true,
        'metadataDriverImpl'       => null,
        'metadataCacheImpl'        => null,
        'objectClassMapper'        => null,
        'proxyNamespace'           => 'DoctrineORMODMAdapter',
        'autoGenerateProxyClasses' => true,
        'objectNamespaces' => array(),
    );

    /**
     * List of all available managers.
     *
     * @var array
     */
    private $managers;

    /**
     * Sets if all object adapter metadata should be validated on read
     *
     * @param boolean $validateDoctrineMetadata
     */
    public function setValidateDoctrineMetadata($validateDoctrineMetadata)
    {
        $this->attributes['validateDoctrineMetadata'] = $validateDoctrineMetadata;
    }

    /**
     * Gets if all object adapter metadata should be validated on read
     *
     * @return boolean
     */
    public function getValidateDoctrineMetadata()
    {
        return $this->attributes['validateDoctrineMetadata'];
    }

    /**
     * Sets if all object adapters should automatically get doctrine metadata added on write
     *
     * @param boolean $writeDoctrineMetadata
     */
    public function setWriteDoctrineMetadata($writeDoctrineMetadata)
    {
        $this->attributes['writeDoctrineMetadata'] = $writeDoctrineMetadata;
    }

    /**
     * Gets if all object adapters should automatically get doctrine metadata added on write
     *
     * @return boolean
     */
    public function getWriteDoctrineMetadata()
    {
        return $this->attributes['writeDoctrineMetadata'];
    }

    /**
     * Adds a namespace under a certain alias.
     *
     * @param string $alias
     * @param string $namespace
     */
    public function addObjectNamespace($alias, $namespace)
    {
        $this->attributes['objectNamespaces'][$alias] = $namespace;
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @param string $objectNamespaceAlias
     *
     * @throws Exception\ConfigurationException
     * @return string the namespace URI
     */
    public function getObjectNamespace($objectNamespaceAlias)
    {
        if (!isset($this->attributes['objectNamespaces'][$objectNamespaceAlias])) {
            throw ConfigurationException::unknownObjectNamespace($objectNamespaceAlias);
        }

        return trim($this->attributes['objectNamespaces'][$objectNamespaceAlias], '\\');
    }

    /**
     * Set the object alias map.
     *
     * @param array $objectNamespaces
     */
    public function setObjectNamespaces(array $objectNamespaces)
    {
        $this->attributes['objectNamespaces'] = $objectNamespaces;
    }

    /**
     * Return the object alias map.
     * @return array
     */
    public function getObjectNamespaces()
    {
        return $this->attributes['objectNamespaces'];
    }

    /**
     * Sets the driver implementation that is used to retrieve mapping metadata.
     *
     * @param MappingDriver $driverImpl
     * @param bool $useBuildInObjectsDriver
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $driverImpl, $useBuildInObjectsDriver = true)
    {
        if ($useBuildInObjectsDriver) {
            $driverImpl = new BuiltinObjectAdaptersDriver($driverImpl);
        }
        $this->attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Gets the driver implementation that is used to retrieve mapping metadata.
     *
     * @return MappingDriver
     */
    public function getMetadataDriverImpl()
    {
        return $this->attributes['metadataDriverImpl'];
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param Cache $metadataCacheImpl
     */
    public function setMetadataCacheImpl(Cache $metadataCacheImpl)
    {
        $this->attributes['metadataCacheImpl'] = $metadataCacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     *
     * @return Cache|null
     */
    public function getMetadataCacheImpl()
    {
        return $this->attributes['metadataCacheImpl'];
    }

    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     *
     * @param string $dir
     */
    public function setProxyDir($dir)
    {
        $this->attributes['proxyDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     *
     * @return string
     */
    public function getProxyDir()
    {
        if (!isset($this->attributes['proxyDir'])) {
            $this->attributes['proxyDir'] = sys_get_temp_dir();
        }

        return $this->attributes['proxyDir'];
    }

    /**
     * Sets the namespace for Doctrine proxy class files.
     *
     * @param string $namespace
     */
    public function setProxyNamespace($namespace)
    {
        $this->attributes['proxyNamespace'] = $namespace;
    }

    /**
     * Gets the namespace for Doctrine proxy class files.
     *
     * @return string
     */
    public function getProxyNamespace()
    {
        return $this->attributes['proxyNamespace'];
    }

    /**
     * Sets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @param boolean $bool
     */
    public function setAutoGenerateProxyClasses($bool)
    {
        $this->attributes['autoGenerateProxyClasses'] = $bool;
    }

    /**
     * Gets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @return boolean
     */
    public function getAutoGenerateProxyClasses()
    {
        return $this->attributes['autoGenerateProxyClasses'];
    }

    /**
     * Sets a class metadata factory.
     *
     * @since 1.1
     *
     * @param string $cmfName
     *
     * @return void
     */
    public function setClassMetadataFactoryName($cmfName)
    {
        $this->attributes['classMetadataFactoryName'] = $cmfName;
    }

    /**
     * @since 1.1
     *
     * @return string
     */
    public function getClassMetadataFactoryName()
    {
        if (!isset($this->attributes['classMetadataFactoryName'])) {
            $this->attributes['classMetadataFactoryName'] = 'Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory';
        }

        return $this->attributes['classMetadataFactoryName'];
    }

    /**
     * Sets default repository class.
     *
     * @since 1.1
     *
     * @param string $className
     *
     * @throws Exception\ConfigurationException
     * @return void
     */
    public function setDefaultRepositoryClassName($className)
    {
        $reflectionClass = new \ReflectionClass($className);

        if (!$reflectionClass->implementsInterface('Doctrine\Common\Persistence\ObjectRepository')) {
            throw ConfigurationException::invalidObjectRepository($className);
        }

        $this->attributes['defaultRepositoryClassName'] = $className;
    }

    /**
     * Get default repository class.
     *
     * @since 1.1
     *
     * @return string
     */
    public function getDefaultRepositoryClassName()
    {
        return isset($this->attributes['defaultRepositoryClassName'])
            ? $this->attributes['defaultRepositoryClassName']
            : 'Doctrine\ORM\ODMAdapter\ObjectAdapterRepository';
    }

    /**
     * Set the closure for the UUID generation.
     *
     * @since 1.1
     * @param callable $generator
     */
    public function setUuidGenerator(\Closure $generator)
    {
        $this->attributes['uuidGenerator'] = $generator;
    }

    /**
     * Get the closure for the UUID generation.
     *
     * @since 1.1
     * @return callable a UUID generator
     */
    public function getUuidGenerator()
    {
        return (isset($this->attributes['uuidGenerator']))
            ? $this->attributes['uuidGenerator']
            : function () {
                return UUIDHelper::generateUUID();
            }
            ;
    }

    public function getManagers()
    {
        return $this->managers;
    }

    public function setManagers($managers)
    {
        $this->managers = array();
        $possibleReferenceTypes = array(Reference::PHPCR, Reference::DBAL_ORM);

        foreach ($managers as $referenceType => $managersByName) {
            if (!in_array($referenceType, $possibleReferenceTypes)) {
                throw new ConfigurationException(
                    sprintf(
                        'Not allowed to set a registry %s with reference type %s. Allowed reference types are: %s',
                        get_class($managersByName),
                        $referenceType,
                        implode(', ', $possibleReferenceTypes)
                    )
                );
            }

            foreach ($managersByName as $name => $manager) {
                $this->managers[$referenceType][$name] = $manager;
            }
        }
    }

    /**
     * Return the manager for a specific reference type and its name.
     *
     * @param $type
     * @param string $managerName
     * @throws Exception\ConfigurationException
     * @return ManagerRegistry
     */
    public function getManagerByReferenceType($type, $managerName = 'default')
    {
        if (isset($this->managers[$type][$managerName])) {
                return $this->managers[$type][$managerName];
        }

        throw new ConfigurationException(sprintf('No manager found for type %s and manager name %s.', $type, $managerName));
    }
}
