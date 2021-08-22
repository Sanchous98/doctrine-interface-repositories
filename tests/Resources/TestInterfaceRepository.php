<?php

namespace Tests\Resources;

use Doctrine\Persistence\ObjectRepository;
use InterfaceRepository\Annotations\Query;

interface TestInterfaceRepository extends ObjectRepository
{
    /**
     * @Query(dql="SELECT e FROM Tests\Resources\TestEntity e WHERE e.id = :id and e.param = :param")
     */
    #[Query(dql: "SELECT e FROM Tests\Resources\TestEntity e WHERE e.id = :id and e.param = :param")]
    public function testCorrectParamOrder($id, $param);

    /**
     * @Query(dql="SELECT e FROM Tests\Resources\TestEntity e WHERE e.id = :id and e.param = :param")
     */
    #[Query(dql: "SELECT e FROM Tests\Resources\TestEntity e WHERE e.id = :id and e.param = :param")]
    public function testCustomParamOrder($param, $id);
}