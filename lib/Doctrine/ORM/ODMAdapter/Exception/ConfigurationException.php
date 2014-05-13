<?php


namespace Doctrine\ORM\ODMAdapter\Exception;

/**
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
class ConfigurationException extends \Exception
{
    public static function unknownObjectNamespace($documentNamespaceAlias)
    {
        return new self("Unknown Document namespace alias '$documentNamespaceAlias'.");
    }

    public static function invalidObjectRepository($className)
    {
        return new self("Invalid repository class '".$className."'. It must be a Doctrine\Common\Persistence\ObjectRepository.");
    }
}