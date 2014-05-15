<?php
if (isset($metadata) && $metadata instanceof \Doctrine\ODM\PHPCR\Mapping\ClassMetadata) {
    /* @var $metadata \Doctrine\ODM\PHPCR\Mapping\ClassMetadata */
    $metadata->name = 'Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver\Model\DefaultMetaObject';

}
