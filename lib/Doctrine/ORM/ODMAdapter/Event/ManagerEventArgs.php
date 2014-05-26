<?php


namespace Doctrine\ORM\ODMAdapter\Event;

use Doctrine\Common\Persistence\Event\ManagerEventArgs as CommonManagerEventArgs;
use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;

class ManagerEventArgs extends CommonManagerEventArgs
{
    /**
     * @var ObjectAdapterManager
     */
    private $oam;

    public function __construct(ObjectAdapterManager $oam)
    {
        $this->oam = $oam;
    }

    public function getObjectAdapterManager()
    {
        return $this->oam;
    }
} 