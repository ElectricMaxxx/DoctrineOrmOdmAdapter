<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Functions\Model;

use Doctrine\Common\Persistence\Event\ManagerEventArgs;
use Doctrine\ORM\ODMAdapter\Event\FlushEventArguments;
use Doctrine\ORM\ODMAdapter\Event\LifecycleEventArgs;

class TestEventListener {
    public $preBindReference = false;
    public $postBindReference = false;
    public $postLoadReference = false;
    public $preUpdateReference = false;
    public $postUpdateReference = false;
    public $preRemoveReference = false;
    public $postRemoveReference = false;
    public $preFlushReference = false;
    public $onFlushReference = false;
    public $postFlushReference = false;
    public $onClear = false;

    public function preBindReference(LifecycleEventArgs $eventArgs)
    {
        $this->preBindReference = true;
    }

    public function postBindReference(LifecycleEventArgs $eventArgs)
    {
        $this->postBindReference = true;
    }

    public function postLoadReference(LifecycleEventArgs $eventArgs)
    {
        $this->postLoadReference = true;
    }

    public function preUpdateReference(LifecycleEventArgs $eventArgs)
    {
        $this->preUpdateReference = true;
    }

    public function postUpdateReference(LifecycleEventArgs $eventArgs)
    {
        $this->postUpdateReference = true;
    }

    public function preRemoveReference(LifecycleEventArgs $eventArgs)
    {
        $this->preRemoveReference = true;
    }

    public function postRemoveReference(LifecycleEventArgs $eventArgs)
    {
        $this->postRemoveReference = true;
    }

    public function preFlushReference(FlushEventArguments $arguments)
    {
        $this->preFlushReference = true;
    }

    public function onFlushReference(FlushEventArguments $arguments)
    {
        $this->onFlushReference = true;
    }

    public function postFlushReference(FlushEventArguments $arguments)
    {
        $this->postFlushReference = true;
    }

    public function onClear(ManagerEventArgs $eventArgs)
    {
        $this->onClear = true;
    }

    public function reset()
    {
        $this->preBindReference = false;
        $this->postBindReference = false;
        $this->postLoadReference = false;
        $this->preUpdateReference = false;
        $this->postUpdateReference = false;
        $this->preRemoveReference = false;
        $this->postRemoveReference = false;
        $this->preFlushReference = false;
        $this->postFlushReference = false;
        $this->onFlushReference = false;
        $this->onClear = false;
    }
} 