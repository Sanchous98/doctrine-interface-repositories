<?php

namespace Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Setup;
use InterfaceRepository\InterfaceRepositoryFactory;
use PHPUnit\Framework\TestCase;
use Tests\Resources\TestEntity;

class TestInterfaceRepository extends TestCase
{
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        AnnotationRegistry::registerLoader("class_exists");
        $config = Setup::createAnnotationMetadataConfiguration([], true, null, null, false);

        $mockRepositoryFactory = new class($config->getMetadataDriverImpl()->getReader()) extends InterfaceRepositoryFactory {
            protected function getQueryResult(int $resultType, Query $query)
            {
                return $query;
            }
        };
        $config->setRepositoryFactory($mockRepositoryFactory);

        $this->entityManager = EntityManager::create([
            'driver' => 'pdo_sqlite',
            'path' => ':memory:',
        ], $config);
    }

    public function testCorrectParamOrder()
    {
        $id = random_int(0, 100);
        $param = random_bytes(10);
        $repository = $this->entityManager->getRepository(TestEntity::class);
        /** @var Query $result */
        $result = $repository->testCorrectParamOrder($id, $param);
        $this->assertEquals($id, $result->getParameter("id")->getValue());
        $this->assertEquals($param, $result->getParameter("param")->getValue());
        $result = $repository->testCustomParamOrder($param, $id);
        $this->assertEquals($id, $result->getParameter("id")->getValue());
        $this->assertEquals($param, $result->getParameter("param")->getValue());
    }
}