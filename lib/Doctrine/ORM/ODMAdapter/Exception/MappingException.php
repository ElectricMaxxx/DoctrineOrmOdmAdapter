<?php


namespace Doctrine\ORM\ODMAdapter\Exception;


class MappingException extends \Exception
{
    /**
     * @param $className
     * @return MappingException
     */
    public static function classNotFound($className)
    {
        return new self("The class '$className' could not be found");
    }

    /**
     * Asking for the mapping of a field that does not exist.
     */
    public static function fieldNotFound($className, $fieldName)
    {
        return new self("The class '$className' does not have a field mapping for '$fieldName'");
    }

    public static function lifecycleCallbackMethodNotFound($className, $methodName)
    {
        return new self("Document '" . $className . "' has no method '" . $methodName . "' to be registered as lifecycle callback.");
    }

    /**
     * Non-annotation mappings could specify a fieldName that does not exist on the class.
     */
    public static function classHasNoField($className, $fieldName)
    {
        return new self("Invalid mapping: The class '$className' does not have a field named '$fieldName'");
    }

    /**
     * @param string $document The document's name
     * @param string $fieldName The name of the field that was already declared
     * @return \Doctrine\ORM\ODMAdapter\Exception\MappingException
     */
    public static function duplicateFieldMapping($document, $fieldName)
    {
        return new self("Property '$fieldName'. in .'$document'. was already declared, but it must be declared only once");
    }

    /**
     * @param string $document The document's name
     * @param string $fieldName The name of the field that was already declared
     * @return \Doctrine\ORM\ODMAdapter\Mapping\MappingException
     */
    public static function missingTypeDefinition($document, $fieldName)
    {
        return new self("Property '$fieldName' in '$document' must have a type attribute defined");
    }

    public static function classNotMapped($className)
    {
        return new self("Class '$className' is not mapped to a document");
    }

    public static function classIsNotAValidDocument($className)
    {
        return new self("Class '$className' is not a valid object adapter.");
    }
}
