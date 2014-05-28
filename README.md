What the hell is he doing here?
-------------------------------

[![Build Status](https://secure.travis-ci.org/ElectricMaxxx/DoctrineOrmOdmAdapter.png)](http://travis-ci.org/ElectricMaxxx/DoctrineOrmOdmAdapter)

I realized in a client project that there are use cases to reference documents
(i.e. from phpcr-odm) on an entity (ORM). The reference is done by the document's
uuid. So i started with implementing an listener, that handles the loading, persisting
and removing the document for the entity depending on the persisted uuid.

As this handling can be more and more complex i saw the need of an mapping. So
this library was born. Atm i try to map the following fields/properties

- uuid - the field for the uuid on the entity
- document - the field for the document on the entity
- common-field - fields that should be synced on both document and entity
   i.e. the title, to not load the complete document when just displaying

Current state:
 * [x] ClassMetadata + Test
 * [x] ClassMetadataFactory + Test
 * [x] XmlDriver + Test
 * [x] AnnotationDriver + Test
 * [ ] YmlDriver + Test
 * [x] DocumentAdapter + Test
 * [x] Kind of UnitOfWork for handling hard work
 * [x] implement the lifecycle events
 * [x] Bundle to use that library

Usage
-----

Configuration
~~~~~~~~~~~~~

 To create a a new instance of an `ObjectAdapterManager` you will need to setup some simple
 configurations. The class will work without that, but you won't have any managers to
 persist your referenced objects:

```php

     use Doctrine\Common\Annotations\AnnotationReader;
     use Doctrine\Common\Cache\ArrayCache;
     use Doctrine\Common\EventManager;
     use Doctrine\ODM\PHPCR\DocumentManager;
     use Doctrine\ORM\EntityManager;
     use Doctrine\ORM\ODMAdapter\Mapping\Driver\AnnotationDriver;
     use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
     use Doctrine\Common\EventManager;

     // caching and annotation read
     $cache = new ArrayCache();
     $reader = new AnnotationReader($cache);

     // AnnotationDriver as example, Yaml and Xml available too
     $annotationDriver = new AnnotationDriver($reader);
     $annotationDriver->addPaths(array(__DIR__ . "/Models"));

     // configuration for the manager
     $configuration = new Configuration();
     $configuration->setManagers(
        array(
            'reference-phpcr' => array(
                'default'  => $documentManager,
            ),
            'reference-dbal-orm' => array(
                'default'  => $entityManager,
            ),
        )
     );
     $configuration->setClassMetadataFactoryName('Doctrine\ORM\ODMAdapter\Mapping\ClassMetadataFactory');
     $configuration->setMetadataDriverImpl($annotationDriver);

     // create the ObjectAdapterManager with the configuration and optional event manager
     $this->objectAdapterManager = new ObjectAdapterManager($configuration, new EventManager());

```

That configuration looks very similar to the common doctrine one and
that is the purpose. There are two more mapping driver available.
One for Yaml and one for Xml. There is one difference:
You have to setup the managers you wanna use as for the reference
of an object and for the referencing object. The managers are sorted
by the type of mapping (reference-phpcr and reference-dbal-orm for
phpcr-odm and orm) and the manager name.

The Main purpose of this library is that such example would work
without doing some work on the referenced object:

```php

     $entity = new ReferencingEntity();

     $document = new ReferencedDocument();
     $document->name = 'document-name';
     $document->parentDocument =  // create some parent document
     $entity->document = $this->referencedObject;

     $entityManager->persist($entity);
     $entityManager->flush();
     $entityManager->clear();

     $document = $documentManager->find(null, '/some-path/document-name');

     // or even better
     $entity = $entityManager->find('Entity', $entity->id);
     $document = $entity->document;

```

In general this documentation speaks about an object that references
the referenced object, but the examples tries to explains that with
an entity that references documents (or inverted).
But how should that example work? We can do some custom event hooking
for every use case or just persist the referenced objects manually.

Mapping
~~~~~~~

But the mapping of this library should help in such a situation. You can
do it in Xml:

```xml

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-mapping
            xmlns="http://doctrine-project.org/schemas/orm-odm-adapter/adapter-mapping"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://doctrine-project.org/schemas/orm-odm-adapter/adapter-mapping
            https://github.com/ElectricMaxxx/DoctrineOrmOdmAdapter/blob/master/doctrine-orm-odm-adapter-mapping.xsd ">
        <object-adapter name="Entity">
            <reference-phpcr
                    target-object="Document"
                    referenced-by="uuid"
                    inversed-by="uuid"
                    name="document"
                    manager="default">
            </reference-phpcr>
        </object-adapter>
    </doctrine-mapping>

```

Yaml

```yml

todo implement the yaml driver


```

or Annotations

```php

    use Doctrine\ORM\ODMAdapter\Mapping\Annotations as ODMAdapter;

     /**
      * @ODMAdapter\ObjectAdapter
      */
     class Entity
     {
         public $uuid;

         /**
          * @ODMAdapter\ReferencePhpcr(
          *  referencedBy="uuid",
          *  inversedBy="uuid",
          *  targetObject="Document",
          *  name="document",
          *  manager="default"
          * )
          */
         public $document;
     }

```

The reference mapping is wrapped by an item (xml-node, annotation,...) which
describes the type of the reference, means the doctrine the referenced object
lives in. At the moment there are two different types available:

 - reference-phpcr -> to create a reference on a document persisted
 with phpcr-odm
 - reference-dbal-orm -> to create a reference on an entity persisted
 with doctrines orm

The body of those types got same attributes for all:
 - referenced-by -> the identifier to reference the object
 (`$manager->find(null, $id))` should work)
 - inversed-by -> the field to store the value of the referenced objects
 field (referenced-by)
 target-object -> the FQCN of the referenced object
 - name -> field where to find the referenced objct
 - manager -> name of the manger (set in the configuration), default set
 to default
 - common-field -> fields to keep in sync between both objects
 (see own description)

If the managers you have set to the configuration provide own event
managers, that will be enough.
The library is able to hook own EventSubscriber on the lifecycle events
fired by the UnitOfWork of the object that holds the reference. Otherwise
you will need to build your own EventSystem and call corresponding methods
on the `ObjectAdapterManager`. Just take the library's own as example:

```php

     <?php

     namespace Doctrine\ORM\ODMAdapter\Event;

     use Doctrine\Common\Persistence\Event\ManagerEventArgs;
     use Doctrine\ORM\Event\PreFlushEventArgs;
     use Doctrine\ORM\Event\LifecycleEventArgs;
     use Doctrine\ORM\Event\OnClearEventArgs;

     class OrmLifecycleListener extends AbstractListener
     {
         public function prePersist(LifecycleEventArgs $event)
         {
             $object = $event->getEntity();
             if ($this->isReferenceable($object)) {
                 $this->objectAdapterManager->persistReference($object);
             }
         }

         public function postLoad(LifecycleEventArgs $event)
         {
             $object = $event->getObject();
             if ($this->isReferenceable($object)) {
                 $this->objectAdapterManager->findReference($object);
             }
         }

         public function preRemove(LifecycleEventArgs $event)
         {
             $object = $event->getObject();
             if ($this->isReferenceable($object)) {
                 $this->objectAdapterManager->removeReference($object);
             }
         }

         public function onClear(OnClearEventArgs $event)
         {
             $this->objectAdapterManager->clear();
         }

         public function preFlush(PreFlushEventArgs $event)
         {
             $this->objectAdapterManager->flushReference();
         }
     }

```


Common fields
~~~~~~~~~~~~~

That field are fields, that should have same content on both objects.
The referencing and the referenced one.

Just a little example why you would need that:
Imagine you have got selection of products in your backend for example.
That selection just shows the title of the product. As you want to persist
all text information as a `ProductDocument`, you decided to create a `ProductEntity`
for all relational stuff persisted with the ORM and let it reference the
document by the type `reference-phpcr`. For sure, your product's title
is a property in your document. When displaying that selection the title is
needed and need's one query/request per product in your list. This would be
when your shop just sells 3 products, but would be an incredible performance
impact for lots of products.
For sure this bundle does lazy loading for the referenced object. That means
when loading the product entity just a proxy of the document is created.
But when fetching the title the proxy will awake and the query/request will
be done.

That is the reason for the common fields. You are able to just map properties to
keep in sync between object and referenced object. That means to provide redundant
data, but you will got some performance boosts.
You can do that mapping inside your reference, cause one reference mapping is done
per property (Btw: you can do several references to different doctrines in one class)

```xml

    <reference-phpcr
            target-object="Document"
            referenced-by="uuid"
            inversed-by="uuid"
            name="document">
        <common-field referenced-by="title" inversed-by="title" sync-type="from-reference"/>
    </reference-phpcr>

```

or in Yaml

```yml

```

or in xml

```php

    /**
     * @ODMAdapter\ReferencePhpcr(
     *  referencedBy="uuid",
     *  inversedBy="uuid",
     *  targetObject="Document",
     *  name="document",
     *  commonField={
     *      @ODMAdapter\CommonField(referencedBy="docName", inversedBy="entityName", syncType="from-reference")
     *  }
     * )
     */
    public $referencedField;

```

You can do more than one common field mapping per reference. Just set the
fields to keep in sync as values of the following attributes

 - referenced-by -> the field on the referenced object
 - inversed-by -> the field on the referencing object
 - sync-type -> way to sync the values, possible `from-reference` (default)
 and `to-reference`
