<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

// phpcs:disable
// Needed for compatibility with phpunit < 8.0
if ((new \ReflectionMethod(Constraint::class, 'evaluate'))->getParameters()[1]->hasType()) {
    trait ConstraintEvaluate
    {
        /**
         * @param mixed $other
         *
         * @return mixed|void
         */
        public function evaluate($other, string $description = '', bool $returnResult = false)
        {
            return $this->doEvaluate($other, $description, $returnResult);
        }

        /**
         * @param mixed $other
         *
         * @return mixed|void
         */
        abstract protected function doEvaluate($other, string $description = '', bool $returnResult = false);
    }
} else {
    trait ConstraintEvaluate
    {
        /**
         * @param mixed $other
         *
         * @return mixed|void
         */
        public function evaluate($other, $description = '', $returnResult = false)
        {
            return $this->doEvaluate($other, $description, $returnResult);
        }

        /**
         * @param mixed $other
         *
         * @return mixed|void
         */
        abstract protected function doEvaluate($other, string $description = '', bool $returnResult = false);
    }
}
// phpcs:enable