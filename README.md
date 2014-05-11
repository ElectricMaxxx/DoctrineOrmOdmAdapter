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
 * [X] XmlDriver + Test
 * [ ] DocumentAdapter + Test
 * [ ] Kind of UnitOfWork for handling hard work
 * [ ] Bundle to use that library