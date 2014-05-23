<?php

namespace Doctrine\Tests\ORM\ODMAdapter\Functions;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ODMAdapter\Configuration;
use Doctrine\ORM\ODMAdapter\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\PHPCR\Mapping\Driver\AnnotationDriver as PhpcrAnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as OrmAnnotationDriver;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\Tools\Setup;
use PHPCR\RepositoryFactoryInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;

class BaseFunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface[]
     */
    protected $sessions = array();

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ObjectAdapterManager
     */
    protected $objectAdapterManager;

    protected $base;

    /**
     * Connection parameters.
     *
     * @var array
     */
    private $params;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var DocumentManager
     */
    protected $dm;

    public function setUp()
    {
        $this->fetchDbParameters();
        $this->connection = DriverManager::getConnection($this->params);
        $this->createEntityManager();
        $this->createDocumentManager();
        $this->resetFunctionalNode($this->dm);
        $this->createObjectAdapterManager();
        NodeHelper::createPath($this->dm->getPhpcrSession(), '/functional');
        $this->base = $this->dm->find(null, '/functional');
        $this->createBaseTables();
    }

    public function createDocumentManager(array $paths = null)
    {
        $reader = new AnnotationReader();
        $reader->addGlobalIgnoredName('group');

        if (empty($paths)) {
            $paths = array(__DIR__ . "/../../../Models");
        }

        $metaDriver = new PhpcrAnnotationDriver($reader, $paths);

        $factoryclass = isset($GLOBALS['DOCTRINE_PHPCR_FACTORY'])
            ? $GLOBALS['DOCTRINE_PHPCR_FACTORY'] : '\Jackalope\RepositoryFactoryJackrabbit';

        if ($factoryclass === '\Jackalope\RepositoryFactoryDoctrineDBAL') {
            $GLOBALS['jackalope.doctrine_dbal_connection'] = $this->connection;
        }

        /** @var $factory RepositoryFactoryInterface */
        $factory = new $factoryclass();
        $parameters = array_intersect_key($GLOBALS, $factory->getConfigurationKeys());

        // factory returns null if it gets unknown parameters
        $repository = $factory->getRepository($parameters);
        $this->assertNotNull($repository, 'There is an issue with your parameters: '.var_export(array_keys($parameters), true));

        $workspace = isset($GLOBALS['DOCTRINE_PHPCR_WORKSPACE'])
            ? $GLOBALS['DOCTRINE_PHPCR_WORKSPACE'] : 'tests';

        $user = isset($GLOBALS['DOCTRINE_PHPCR_USER'])
            ? $GLOBALS['DOCTRINE_PHPCR_USER'] : '';
        $pass = isset($GLOBALS['DOCTRINE_PHPCR_PASS'])
            ? $GLOBALS['DOCTRINE_PHPCR_PASS'] : '';

        $credentials = new \PHPCR\SimpleCredentials($user, $pass);
        $session = $repository->login($credentials, $workspace);
        $this->sessions[] = $session;

        $config = new \Doctrine\ODM\PHPCR\Configuration();
        $config->setMetadataDriverImpl($metaDriver);

        $this->dm = DocumentManager::create($session, $config);
    }

    public function resetFunctionalNode(DocumentManager $dm)
    {
        $session = $dm->getPhpcrSession();
        $root = $session->getNode('/');
        if ($root->hasNode('functional')) {
            $root->getNode('functional')->remove();
            $session->save();
        }

        $node = $root->addNode('functional');
        $session->save();
        $dm->clear();
    }

    public function tearDown()
    {
        foreach ($this->sessions as $session) {
            $session->logout();
        }
        $this->sessions = array();
        $this->dropTables();
    }

    public function createEntityManager()
    {
        // retrieve parameters for connection
        $params = array();
        foreach ($GLOBALS as $key => $value) {
            if (0 === strpos($key, 'jackalope.doctrine.dbal.')) {
                $params[substr($key, strlen('jackalope.doctrine.dbal.'))] = $value;
            }
        }

        $config = Setup::createConfiguration(true);
        $reader = new AnnotationReader();
        $reader->addGlobalIgnoredName('group');

        if (empty($paths)) {
            $paths = array(__DIR__ . "/../../../Models");
        }

        $metaDriver = new OrmAnnotationDriver($reader, $paths);
        $config->setMetadataDriverImpl($metaDriver);

        // obtaining the entity manager
        $this->connection = DriverManager::getConnection($params);
        $this->em = EntityManager::create($params, $config);
    }

    private function fetchDbParameters()
    {
        foreach ($GLOBALS as $key => $value) {
            if (0 === strpos($key, 'jackalope.doctrine.dbal.')) {
                $this->params[substr($key, strlen('jackalope.doctrine.dbal.'))] = $value;
            }
        }

        if (isset($this->params['username'])) {
            $this->params['user'] = $this->params['username'];
        }
    }

    private function createObjectAdapterManager()
    {
        $configuration = new Configuration();
        $configuration->setManagers(array(
            'reference-phpcr' => array(
                'default'  => $this->dm,
            ),
            'reference-dbal-orm' => array(
                'default'  => $this->em,
            ),
        ));
        $configuration->setClassMetadataFactoryName('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory');
        $cache = new ArrayCache();
        $reader = new AnnotationReader($cache);
        $annotationDriver = new AnnotationDriver($reader);
        $annotationDriver->addPaths(array(__DIR__ . "/../../../Models"));
        $configuration->setMetadataDriverImpl($annotationDriver);
        $this->objectAdapterManager = new ObjectAdapterManager($configuration);
    }

    private function createBaseTables()
    {
        $this->connection->executeUpdate(
          'CREATE TABLE IF NOT EXISTS `objects` (
              `id` varchar(255),
              `entityName` varchar(255),
              `uuid` varchar(255)
          )'
        );
    }

    protected function dropTables()
    {
        $this->connection->executeUpdate("DROP TABLE objects");
    }
}
