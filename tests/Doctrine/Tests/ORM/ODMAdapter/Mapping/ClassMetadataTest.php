<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping;


use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\ODMAdapter\Mapping\ClassMetadata;
use Doctrine\ORM\ODMAdapter\Mapping\Model\ReferencedOneDocument;

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

    public function testMapField()
    {
        $cm = new ClassMetadata('Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\CommonFieldMappingObject');
        $cm->initializeReflection(new RuntimeReflectionService());

        return $cm;
    }

    /**
     * @depends testMapField
     */
    public function testMapFields(ClassMetadata $cm)
    {
        $cm->mapCommonField(array(
            'fieldName' => 'entityName',
            'document-name' => 'docName',
            'type' => 'common-field'
        ));

        $this->assertTrue(isset($cm->mappings['entityName']));

        $this->assertEquals(
            array(
                'fieldName'      => 'entityName',
                'property'      => 'entityName',
                'document-name' => 'docName',
                'type'          => 'common-field',
            ),
            $cm->mappings['entityName']
        );

        return $cm;
    }

    /**
     * @depends testMapField
     * @param ClassMetadata $cm
     */
    public function testMapReferenceOneDocument(ClassMetadata $cm)
    {
        $cm->mapRefereceOneDocument(array(
            'type'            => 'reference-one-document',
            'inversed-by'     => 'uuid',
            'referenced-by'   => 'uuid',
            'target-document' => 'document',
            'fieldName'       => 'document',
            'inversed-entity' => 'entity',
        ));

        $this->assertTrue(isset($cm->mappings['document']));

        $this->assertEquals(
            array(
                'type'            => 'reference-one-document',
                'fieldName'       => 'document',
                'referenced-by'   => 'uuid',
                'inversed-by'     => 'uuid',
                'target-document' => 'document',
                'inversed-entity'  => 'entity',
                'property'        => 'document',
            ),
            $cm->mappings['document']
        );

        $referencedOneDocument = new ReferencedOneDocument();
        $referencedOneDocument->targetDocument = 'document';
        $referencedOneDocument->referencingEntity = 'entity';
        $referencedOneDocument->inversedBy = 'uuid';
        $referencedOneDocument->referencedBy = 'uuid';
        $referencedOneDocument->fieldName = 'document';

        $this->assertEquals($referencedOneDocument, $cm->getReferencedDocument());
    }
    /**
     * @depends testMapField
     */
    public function testReflectionProperties(ClassMetadata $cm)
    {
        $this->assertInstanceOf('ReflectionProperty', $cm->reflectionFields['entityName']);
    }

    /**
     * @depends testMapField
     */
    public function testNewInstance(ClassMetadata $cm)
    {
        $instance1 = $cm->newInstance();
        $instance2 = $cm->newInstance();

        $this->assertInstanceOf('Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\CommonFieldMappingObject', $instance1);
        $this->assertNotSame($instance1, $instance2);
    }
}
 