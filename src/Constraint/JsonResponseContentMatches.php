<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use Coduo\PHPMatcher\Factory\SimpleFactory;
use Coduo\PHPMatcher\Matcher;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Util\Json;
use SebastianBergmann\Comparator\ComparisonFailure;
use Symfony\Component\HttpFoundation\Response;

final class JsonResponseContentMatches extends Constraint
{
    /** @var Matcher */
    private static $matcher;

    /** @var string */
    private $expectedContent;

    public function __construct(string $expectedContent)
    {
        if (\method_exists(Constraint::class, '__construct')) {
            parent::__construct();
        }

        $this->expectedContent = $expectedContent;
    }

    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return 'content is ' . $this->expectedContent;
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function matches($other) : bool
    {
        return static::getMatcher()->match($other->getContent(), $this->expectedContent);
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        return \sprintf(
            '"%s" matches JSON string "%s"',
            Json::prettify($other->getContent()),
            Json::prettify($this->expectedContent)
        );
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function additionalFailureDescription($other) : string
    {
        return static::getMatcher()->getError();
    }

    /**
     * @param Response $other
     *
     * {@inheritdoc}
     */
    protected function fail($other, $description, ComparisonFailure $comparisonFailure = null) : void
    {
        $actual = $other->getContent();
        if ($comparisonFailure === null) {
            [$error] = Json::canonicalize($actual);

            if ($error) {
                parent::fail($other, $description);
            }

            [$error] = Json::canonicalize($this->expectedContent);

            if ($error) {
                parent::fail($other, $description);
            }

            $comparisonFailure = new ComparisonFailure(
                \json_decode($this->expectedContent, true),
                \json_decode($actual, true),
                Json::prettify($this->expectedContent),
                Json::prettify($actual),
                false,
                'Failed asserting that two json values are equal.'
            );
        }

        parent::fail($other, $description, $comparisonFailure);
    }

    private static function getMatcher() : Matcher
    {
        if (self::$matcher === null) {
            $factory       = new SimpleFactory();
            self::$matcher = $factory->createMatcher();
        }

        return self::$matcher;
    }
}
