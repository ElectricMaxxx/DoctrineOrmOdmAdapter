<?php

namespace Doctrine\Tests\ORM\ODMAdapter\Functions;


use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\Reference;

class ObjectAdapterManagerTest extends BaseFunctionalTestCase
{
    private $dm;

    /**
     * @var ObjectAdapterManager
     */
    private $objectAdapterManager;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager();
        $this->resetFunctionalNode($this->dm);

        $this->objectAdapterManager = new ObjectAdapterManager(
            array(Reference::PHPCR => $this->dm)
        );
    }

    public function testGetMangerByType()
    {
        $this->assertEquals($this->dm, $this->objectAdapterManager->getManagerByType(Reference::PHPCR));
    }
}
