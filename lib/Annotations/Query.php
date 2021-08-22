<?php

namespace InterfaceRepository\Annotations;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[Attribute(flags: Attribute::TARGET_METHOD)]
final class Query
{
    public const RESULT_ALL = 0;
    public const RESULT_SINGLE = 1;
    public const RESULT_SCALAR = 2;
    public const RESULT_SINGLE_SCALAR = self::RESULT_SINGLE | self::RESULT_SCALAR;

    /**
     * @var string DQL
     * @Required
     */
    public $dql;

    /**
     * @var int
     * @Required
     * @psalm-var self::RESULT_*
     */
    public $resultType = self::RESULT_ALL;
    // TODO: Support native queries
    // public bool $native = false;

    /** @psalm-param  self::RESULT_* $resultType */
    public function __construct(string $dql, int $resultType = self::RESULT_ALL)
    {
        $this->dql = $dql;
        $this->resultType = $resultType;
    }
}
