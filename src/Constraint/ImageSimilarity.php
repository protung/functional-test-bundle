<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

final class ImageSimilarity extends Constraint
{
    /** @var string */
    private $expectedImageContent;

    /** @var float */
    private $similarityThreshold;

    public function __construct(string $expectedImageContent, float $similarityThreshold)
    {
        if (\method_exists(Constraint::class, '__construct')) {
            parent::__construct();
        }

        $this->expectedImageContent = $expectedImageContent;
        $this->similarityThreshold  = $similarityThreshold;
    }

    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return 'image is similar to ' . $this->expectedImageContent;
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other) : bool
    {
        $expectedImagick = new \Imagick();
        $expectedImagick->readImageBlob($this->expectedImageContent);
        $actualImagick = new \Imagick();
        $actualImagick->readImageBlob($other);

        $result = $expectedImagick->compareImages($actualImagick, \Imagick::METRIC_MEANSQUAREERROR);

        return $result[1] <= $this->similarityThreshold;
    }

    /**
     * {@inheritdoc}
     */
    protected function failureDescription($other) : string
    {
        return \sprintf('images are similar');
    }
}
