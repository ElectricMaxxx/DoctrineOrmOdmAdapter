<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Functions\Model;

use Doctrine\ORM\ODMAdapter\Event\LifecycleEventArgs;

class TestEventListener {

    public $preBindReference = false;

    public function preBindReference(LifecycleEventArgs $eventArgs)
    {
        $this->preBindReference = true;
    }
} 