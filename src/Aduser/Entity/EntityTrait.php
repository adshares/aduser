<?php
namespace Aduser\Entity;

use Doctrine\ORM\EntityManager;

trait EntityTrait
{
    public static function getRepository(EntityManager $em)
    {
        return $em->getRepository(get_called_class());
    }
}