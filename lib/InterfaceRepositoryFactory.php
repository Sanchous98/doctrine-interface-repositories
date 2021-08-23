<?php

namespace InterfaceRepository;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\ORM\Repository\RepositoryFactory;
use InterfaceRepository\Annotations\Query;
use ReflectionClass;
use ReflectionParameter;

class InterfaceRepositoryFactory implements RepositoryFactory
{
    /**
     * @var DefaultRepositoryFactory
     */
    private $factory;
    /**
     * @var Reader
     */
    private $annotationReader;

    public function __construct(Reader $annotationReader)
    {
        $this->factory = new DefaultRepositoryFactory();
        $this->annotationReader = $annotationReader;
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     * @psalm-param class-string $entityName
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $repository = $this->annotationReader->getClassAnnotation(new ReflectionClass($entityName), Entity::class);

        if (isset($repository)) {
            /** @var class-string $repositoryClass */
            $repositoryClass = $repository->repositoryClass;

            if ((new ReflectionClass($repositoryClass))->isInterface()) {
                return $this->createRepositoryFromInterface($entityManager, $entityName, $repositoryClass);
            }
        }

        return $this->factory->getRepository($entityManager, $entityName);
    }

    /**
     * @psalm-param class-string $entityName
     * @psalm-param class-string $repository
     */
    protected function createRepositoryFromInterface(EntityManagerInterface $entityManager, string $entityName, string $repository): OverriddenEntityRepository
    {
        $reflect = new ReflectionClass($repository);
        $methods = [];

        foreach ($reflect->getMethods() as $method) {
            if (PHP_VERSION_ID < 80000 && count($method->getAttributes(Query::class))) {
                $annotation = $method->getAttributes(Query::class)[0]->newInstance();
            }

            if (!isset($annotation)) {
                $annotation = $this->annotationReader->getMethodAnnotation($method, Query::class);
            }

            if (!isset($annotation)) {
                continue;
            }

            $repositoryMethod = /** @psalm-return mixed */function () use ($entityManager, $annotation, $method) {
                $query = $entityManager->createQuery($annotation->dql)
                    ->setParameters(
                        array_combine(
                            array_map(function (ReflectionParameter $item) {
                                return $item->name;
                            }, $method->getParameters()),
                            func_get_args()
                        )
                    );

                return $this->getQueryResult($annotation->resultType, $query);
            };
            $methods[$method->getName()] = $repositoryMethod;
        }

        return new OverriddenEntityRepository($entityManager, new ClassMetadata($entityName), $methods);
    }

    /**
     * @psalm-param Query::RESULT_* $resultType
     * @return mixed
     * @throws NonUniqueResultException|NoResultException
     */
    protected function getQueryResult(int $resultType, \Doctrine\ORM\Query $query)
    {
        switch ($resultType) {
            case Query::RESULT_SINGLE:
                return $query->getSingleResult();
            case Query::RESULT_SCALAR:
                return $query->getScalarResult();
            case Query::RESULT_SINGLE_SCALAR:
                return $query->getSingleScalarResult();
            default:
                return $query->getResult();
        }
    }
}
