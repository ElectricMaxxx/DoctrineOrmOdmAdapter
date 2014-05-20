<?php

namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;
use Doctrine\ORM\ODMAdapter\Mapping\Model\ReferencedOneDocument;
use Doctrine\ORM\ODMAdapter\Reference;

abstract class AbstractMappingDriverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    abstract protected function loadDriver();
    /**
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    abstract protected function loadDriverForTestMappingDocuments();

    protected function ensureIsLoaded($entityClassName)
    {
        new $entityClassName;
    }

    /**
     * Returns a ClassMetadata object for the given class,
     * loaded using the driver associated with a concrete child of this class.
     *
     * @param string $className
     * @return \Doctrine\ODM\PHPCR\Mapping\ClassMetadata
     */
    protected function loadMetadataForClassname($className)
    {
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata($className);
        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass($className, $class);

        return $class;
    }

    public function testLoadMetadataForNonDocumentThrowsException()
    {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new RuntimeReflectionService());

        $driver = $this->loadDriver();

        $this->setExpectedException('Doctrine\ORM\ODMAdapter\Exception\MappingException');
        $driver->loadMetadataForClass('stdClass', $cm);
    }

    public function testGetAllClassNamesIsIdempotent()
    {
        $driver = $this->loadDriverForTestMappingDocuments();
        $original = $driver->getAllClassNames();

        $driver = $this->loadDriverForTestMappingDocuments();
        $afterTestReset = $driver->getAllClassNames();

        $this->assertEquals($original, $afterTestReset);
    }

    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate()
    {
        $rightClassName = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject';
        $this->ensureIsLoaded($rightClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertContains($rightClassName, $classes);
    }

    public function testGetAllClassNamesReturnsOnlyTheAppropriateClasses()
    {
        $extraneousClassName = 'Doctrine\Tests\Models\ECommerce\ECommerceCart';
        $this->ensureIsLoaded($extraneousClassName);

        $driver = $this->loadDriverForTestMappingDocuments();
        $classes = $driver->getAllClassNames();

        $this->assertNotContains($extraneousClassName, $classes);
    }

    public function testLoadReferenceMapping()
    {
        $className = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject';

        return $this->loadMetadataForClassName($className);
    }

    public function testLoadInvertReferenceMapping()
    {
        $className = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\InvertedReferenceMappingObject';

        return $this->loadMetadataForClassName($className);
    }

    /**
     * @depends testLoadReferenceMapping
     * @param ClassMetadata $class
     * @return \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata
     */
    public function testReferenceMappings($class)
    {
        $this->assertCount(3, $class->mappings);
        $this->assertCount(2, $class->commonFieldMappings);
        $this->assertCount(1, $class->getReferencedObjects());
        $this->assertTrue(isset($class->mappings['entityName']));
        $this->assertEquals('common-field', $class->mappings['entityName']['type']);
        $this->assertEquals('common-field', $class->mappings['uuid']['type']);
        $this->assertEquals(Reference::PHPCR, $class->mappings['referencedField']['type']);
        return $class;
    }


    /**
     * @depends testLoadInvertReferenceMapping
     * @param ClassMetadata $class
     * @return \Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata
     */
    public function testInvertedReferenceMappings($class)
    {
        $this->assertCount(3, $class->mappings);
        $this->assertCount(2, $class->commonFieldMappings);
        $this->assertCount(1, $class->getReferencedObjects());
        $this->assertTrue(isset($class->mappings['docName']));
        $this->assertTrue(isset($class->mappings['objectId']));
        $this->assertTrue(isset($class->mappings['referencedField']));
        $this->assertEquals('common-field', $class->mappings['docName']['type']);
        $this->assertEquals('common-field', $class->mappings['objectId']['type']);
        $this->assertEquals(Reference::DBAL_ORM, $class->mappings['referencedField']['type']);
        return $class;
    }

    /**
     * @depends testReferenceMappings
     * @param $class
     */
    public function testReferencedDocumentMapping($class)
    {
        $expectedMapping = array();
        $expectedMapping['target-object'] = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\Document';
        $expectedMapping['referenced-by'] = 'uuid';
        $expectedMapping['inversed-by'] = 'uuid';
        $expectedMapping['inversed-entity'] = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject';
        $expectedMapping['fieldName'] = 'referencedField';
        $expectedMapping['type'] = Reference::PHPCR;
        $expectedMapping['property'] = 'referencedField';
        $expectedMapping['name'] = 'referencedField';

        $this->assertEquals($expectedMapping, $class->mappings['referencedField']);
    }

    /**
     * @depends testInvertedReferenceMappings
     * @param $class
     */
    public function testReferencedObjectMapping($class)
    {
        $expectedMapping = array();
        $expectedMapping['target-object'] = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\Object';
        $expectedMapping['referenced-by'] = 'id';
        $expectedMapping['inversed-by'] = 'objectId';
        $expectedMapping['inversed-entity'] = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\InvertedReferenceMappingObject';
        $expectedMapping['fieldName'] = 'referencedField';
        $expectedMapping['type'] = Reference::DBAL_ORM;
        $expectedMapping['property'] = 'referencedField';
        $expectedMapping['name'] = 'referencedField';

        $this->assertEquals($expectedMapping, $class->mappings['referencedField']);
    }

    /**
     * @depends testLoadReferenceMapping
     * @param   ClassMetadata $class
     */
    public function testcommonFieldMapping($class)
    {
        $this->assertEquals('common-field', $class->mappings['entityName']['type']);
        $this->assertEquals('entityName', $class->mappings['entityName']['property']);
        $this->assertEquals('entityName', $class->mappings['entityName']['inversed-by']);
        $this->assertEquals('docName', $class->mappings['entityName']['referenced-by']);
        $this->assertEquals('from-reference', $class->mappings['entityName']['sync-type']);
        $this->assertEquals('referencedField', $class->mappings['entityName']['target-field']);
    }


    /**
     * @depends testInvertedReferenceMappings
     * @param   ClassMetadata $class
     */
    public function testInvertedCommonFieldMapping($class)
    {
        $this->assertEquals('common-field', $class->mappings['docName']['type']);
        $this->assertEquals('docName', $class->mappings['docName']['property']);
        $this->assertEquals('docName', $class->mappings['docName']['inversed-by']);
        $this->assertEquals('entityName', $class->mappings['docName']['referenced-by']);
        $this->assertEquals('referencedField', $class->mappings['docName']['target-field']);
    }
}
