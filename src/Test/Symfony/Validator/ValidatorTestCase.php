<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Symfony\Validator;

use stdClass;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @template TConstraintValidator of ConstraintValidatorInterface
 * @template-extends ConstraintValidatorTestCase<TConstraintValidator>
 */
abstract class ValidatorTestCase extends ConstraintValidatorTestCase
{
    abstract protected function createValidValue(): mixed;

    abstract protected function createConstraint(): Constraint;

    protected function invalidConstraint(): Constraint
    {
        return new Stub\DummyConstraint();
    }

    protected function invalidValue(): mixed
    {
        return new stdClass();
    }

    public function testThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Expected argument of type');

        $this->validator->validate($this->invalidValue(), $this->createConstraint());
    }

    public function testThrowsExceptionOnInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type');

        $this->validator->validate($this->createValidValue(), $this->invalidConstraint());
    }

    public function testDoesNotValidateNull(): void
    {
        $this->validator->validate(null, $this->createConstraint());
        $this->assertNoViolation();
    }
}
