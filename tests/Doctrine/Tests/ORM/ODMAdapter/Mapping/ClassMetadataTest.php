<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping;


use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;

class ClassMetadataTest extends \PHPUnit_Framework_TestCase
{

    public function testGetTypeOfField()
    {
        $cmi = new ClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $cmi->initializeReflection(new RuntimeReflectionService());
        $this->assertEquals(null, $cmi->getTypeOfField('some_field'));
        $cmi->mappings['some_field'] = array('type' => 'some_type');
        $this->assertEquals('some_type', $cmi->getTypeOfField('some_field'));
    }

    public function testClassName()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\ECommerce\ECommerceCart');
        $cm->initializeReflection(new RuntimeReflectionService());
        $this->assertEquals('Doctrine\Tests\Models\ECommerce\ECommerceCart', $cm->className);
        $this->assertInstanceOf('ReflectionClass', $cm->getReflectionClass());

        return $cm;
    }

    /**
     * @depends testClassName
     */
    public function testHasFieldNull(ClassMetadata $cm)
    {
        $this->assertFalse($cm->hasField(null));
    }

    public function testReferenceMapping()
    {
        $cm = new ClassMetadata('Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject');
        $cm->initializeReflection(new RuntimeReflectionService());

        return $cm;
    }

    /**
     * @depends testReferenceMapping
     */
    public function testReferenceMappings(ClassMetadata $cm)
    {
        $cm->mapCommonField(array(
            'inversed-by'   => 'entityName',
            'referenced-by' => 'docName',
            'target-field'  => 'referencedField',
            'type'          => 'common-field',
        ));

        $this->assertTrue(isset($cm->mappings['entityName']));

        $this->assertEquals(
            array(
                'fieldName'      => 'entityName',
                'inversed-by' => 'entityName',
                'property'      => 'entityName',
                'referenced-by' => 'docName',
                'type'          => 'common-field',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
            $cm->mappings['entityName']
        );

        $this->assertEquals(
            array(
                'fieldName'     => 'entityName',
                'inversed-by' => 'entityName',
                'property'      => 'entityName',
                'referenced-by' => 'docName',
                'type'          => 'common-field',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
            $cm->commonFieldMappings['entityName']
        );

        return $cm;
    }

    /**
     * @depends testReferenceMapping
     * @param ClassMetadata $cm
     */
    public function testMapReferenceOneDocument(ClassMetadata $cm)
    {
        $cm->mapReferencedObject(array(
            'type'            => 'reference-document',
            'inversed-by'     => 'uuid',
            'referenced-by'   => 'uuid',
            'target-object' => 'document',
            'fieldName'       => 'referencedField',
            'inversed-entity' => 'entity',
        ));

        $this->assertTrue(isset($cm->mappings['referencedField']));

        $this->assertEquals(
            array(
                'type'            => 'reference-document',
                'fieldName'       => 'referencedField',
                'referenced-by'   => 'uuid',
                'inversed-by'     => 'uuid',
                'target-object' => 'document',
                'inversed-entity' => 'entity',
                'property'        => 'referencedField',
            ),
            $cm->mappings['referencedField']
        );

        $this->assertEquals(
            array(
                'property'      => 'uuid',
                'fieldName'     => 'uuid',
                'type'          => 'common-field',
                'inversed-by'   => 'uuid',
                'referenced-by' => 'uuid',
                'target-field'  => 'referencedField',
                'sync-type'     => 'from-reference',
            ),
            $cm->mappings['uuid']
        );

        $this->assertEquals(
            array(
                'type'            => 'reference-document',
                'fieldName'       => 'referencedField',
                'referenced-by'   => 'uuid',
                'inversed-by'     => 'uuid',
                'target-object' => 'document',
                'inversed-entity' => 'entity',
                'property'        => 'referencedField',
            ),
            $cm->getReferencedObject('referencedField')
        );
    }

    /**
     * @depends             testReferenceMapping
     * @expectedException   Doctrine\ORM\ODMAdapter\Exception\MappingException
     * @param               ClassMetadata $cm
     */
    public function testMapReferenceThrowsExceptionOnEmtpyType(ClassMetadata $cm)
    {
        $cm->mapCommonField(array());
    }

    /**
     * @depends            testReferenceMapping
     * @expectedException  Doctrine\ORM\ODMAdapter\Exception\MappingException
     * @param              ClassMetadata $cm
     */
    public function testReferenceMappingThrowsExceptionOnWrongType(ClassMetadata $cm)
    {
        $cm->mapCommonField(array('type' => 'some_type'));
    }

    /**
     * @depends             testReferenceMapping
     * @expectedException   Doctrine\ORM\ODMAdapter\Exception\MappingException
     * @param               ClassMetadata $cm
     */
    public function testReferenceMappingThrowsExceptionOnEmtpyType(ClassMetadata $cm)
    {
        $cm->mapReferencedObject(array());
    }

    /**
     * @depends            testReferenceMapping
     * @expectedException  Doctrine\ORM\ODMAdapter\Exception\MappingException
     * @param              ClassMetadata $cm
     */
    public function testMapReferenceThrowsExceptionOnWrongType(ClassMetadata $cm)
    {
        $cm->mapReferencedObject(array('type' => 'some_type'));
    }

    /**
     * @depends testReferenceMapping
     */
    public function testReflectionProperties(ClassMetadata $cm)
    {
        $this->assertInstanceOf('ReflectionProperty', $cm->reflectionFields['entityName']);
    }

    /**
     * @depends testReferenceMapping
     */
    public function testNewInstance(ClassMetadata $cm)
    {
        $instance1 = $cm->newInstance();
        $instance2 = $cm->newInstance();

        $this->assertInstanceOf('Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\ReferenceMappingObject', $instance1);
        $this->assertNotSame($instance1, $instance2);
    }
}
 