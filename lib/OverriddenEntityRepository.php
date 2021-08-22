<?php

namespace InterfaceRepository;

use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;

final class OverriddenEntityRepository extends EntityRepository
{
    /**
     * @psalm-var array<string, callable>
     */
    private $macros;

    /**
     * @psalm-param array<string, Closure> $macros
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $class, array $macros)
    {
        parent::__construct($em, $class);

        $this->macros = $macros;
    }

    public function __call($method, $arguments)
    {
        if (in_array($method, array_keys($this->macros), true)) {
            return $this->macros[$method](...$arguments);
        }

        return parent::__call($method, $arguments);
    }
}
