<?php

namespace Tests\Resources;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TestInterfaceRepository::class)
 */
class TestEntity
{
    public $id;

    public $param;
}