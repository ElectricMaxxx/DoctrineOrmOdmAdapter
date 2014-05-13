What the hell is he doing here?
-------------------------------

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
 * [ ] AnnotationDriver + Test
 * [ ] YmDriver + Test
 * [ ] DocumentAdapter + Test
 * [ ] Kind of UnitOfWork for handling hard work
 * [ ] implement the lifecycle events
 * [ ] Bundle to use that library

 Usage
 -----

 ... would be to create a bundle which is able to hook on entities lifecycle events
 and triggers the right method on the `ObjectAdapterManager`. That bundle would need
 to inject the right managers (for document and entity).
 The application which uses both would just need to do the mapping and hook on this libraries
 events for further usage.

 Example
 -------

 - create an entity which got a reference to a document
 - map uuid/document field - means where to find them on the entity
 - persist the entity
 - OrmOdmAdapterBunle would hook on `postPersist`
 - call `$objectAdapterManager->bindDocument($entity);`
 - UoW would get the documents uuid and store it on the mapped field on the entity
 - UoW would trigger persist on `DocumentManager` (that one that handles the document)
 - a possible hook on `preBindDocument` could manage other work i.e. creating a route
  for that document.
