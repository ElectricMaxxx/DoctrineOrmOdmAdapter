<?php


namespace Doctrine\ORM\ODMAdapter;

/**
 * This class contains the values of possible mapping types.
 *
 * @author Maximilian Berghoff <Maximilian.Berghoff@gmx.de>
 */
final class Reference {
    const PHPCR = 'reference-phpcr';
    const DBAL_ORM = 'reference-dbal-orm';
} 