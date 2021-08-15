<?php

namespace InterfaceRepository;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Entity;
use InterfaceRepository\Annotations\Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory;
use ReflectionClass;
use ReflectionParameter;

final class InterfaceRepositoryFactory implements RepositoryFactory
{
    private $factory;
    private $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->factory = new DefaultRepositoryFactory();
        $this->annotationReader = $annotationReader;
    }

    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $repository = $this->annotationReader->getClassAnnotation(new ReflectionClass($entityName), Entity::class)->repositoryClass;

        if ((new ReflectionClass($repository))->isInterface()) {
            return $this->createRepositoryFromInterface($entityManager, $entityName, $repository);
        }

        return $this->factory->getRepository($entityManager, $entityName);
    }

    public function createRepositoryFromInterface(EntityManagerInterface $entityManager, string $entityName, string $repository)
    {
        $reflect = new ReflectionClass($repository);
        $methods = [];

        foreach ($reflect->getMethods() as $method) {
            if (PHP_VERSION_ID < 80000 && count($method->getAttributes(Query::class)) !== 0) {
                $annotation = $method->getAttributes(Query::class)[0]->newInstance();
            } else {
                $annotation = $this->annotationReader->getMethodAnnotation($method, Query::class);
            }


            if (!isset($annotation)) {
                continue;
            }

            $methods[$method->getName()] = function () use($entityManager, $annotation, $method) {
                $entityManager->beginTransaction();
                $query = $entityManager->createQuery($annotation->dql)
                    ->setParameters(
                        array_combine(
                            array_map(fn(ReflectionParameter $item) => $item->name, $method->getParameters()),
                            func_get_args()
                        )
                    );

                switch ($annotation->resultType) {
                    case Query::RESULT_SINGLE:
                        return $query->getSingleResult();
                    case Query::RESULT_SCALAR:
                        return $query->getScalarResult();
                    case Query::RESULT_SINGLE_SCALAR:
                        return $query->getSingleScalarResult();
                    case Query::RESULT_ALL:
                    default:
                        return $query->getResult();
                }
            };
        }

        return new OverriddenEntityRepository($entityManager, new ClassMetadata($entityName), $methods);
    }
}
