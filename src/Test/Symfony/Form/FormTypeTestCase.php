<?php

declare(strict_types=1);

namespace Speicher210\FunctionalTestBundle\Test\Symfony\Form;

use Generator;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\MockObject\MockObject;
use Psl\Type;
use Psl\Vec;
use Speicher210\FunctionalTestBundle\SnapshotUpdater;
use Speicher210\FunctionalTestBundle\SnapshotUpdater\DriverConfigurator;
use Speicher210\FunctionalTestBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\Type\FormTypeValidatorExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

use function array_replace_recursive;

use const PHP_EOL;

abstract class FormTypeTestCase extends KernelTestCase
{
    protected FormFactoryInterface $factory;

    /** @var array<string, mixed> */
    protected static array $formTypeOptions = [];

    /** @var MockObject&Request */
    protected MockObject|Request $currentRequestMock;

    /** @var MockObject&RequestStack */
    protected MockObject|RequestStack $requestStackMock;

    /**
     * @return class-string<FormTypeInterface>
     */
    abstract protected static function formTypeUnderTest(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currentRequestMock = $this->createMock(Request::class);

        $this->requestStackMock = $this->createMock(RequestStack::class);

        $this->factory = Forms::createFormFactoryBuilder()
            ->addTypes(Vec\values($this->getTypes()))
            ->addExtensions(Vec\values($this->getExtensions()))
            ->addTypeExtensions(Vec\values($this->getTypeExtensions()))
            ->getFormFactory();

        self::$formTypeOptions = [];
    }

    /**
     * @return Generator<FormTypeExtensionInterface>
     */
    protected function getTypeExtensions(): Generator
    {
        yield new FormTypeValidatorExtension(Validation::createValidator());
    }

    /**
     * @return Generator<FormExtensionInterface>
     */
    protected function getExtensions(): Generator
    {
        $validatorFactory = Type\instance_of(ContainerConstraintValidatorFactory::class)->coerce($this->getContainerService('validator.validator_factory'));

        yield new ValidatorExtension(
            Validation::createValidatorBuilder()
                ->setConstraintValidatorFactory($validatorFactory)
                ->getValidator(),
        );

        yield new HttpFoundationExtension();
    }

    /**
     * @return Generator<FormTypeInterface>
     */
    protected function getTypes(): Generator
    {
        yield from [];
    }

    protected function createFormType(mixed $initialData = null): FormInterface
    {
        return $this->factory->create(static::formTypeUnderTest(), $initialData, self::$formTypeOptions);
    }

    /**
     * @param array<mixed> $submittedData
     */
    protected function assertFormSubmitOnGet(array $submittedData, mixed $expected): void
    {
        $form = $this->createAndSubmitTestedForm($submittedData, Request::METHOD_GET);
        $this->assertSubmittedFormMatchesData($form, $expected);
    }

    /**
     * @param array<mixed>        $submittedData
     * @param array<UploadedFile> $submittedFilesData
     */
    protected function assertFormSubmitOnPost(array $submittedData, mixed $expected, array $submittedFilesData = []): void
    {
        $form = $this->createAndSubmitTestedForm($submittedData, Request::METHOD_POST, null, $submittedFilesData);
        $this->assertSubmittedFormMatchesData($form, $expected);
    }

    /**
     * @param array<UploadedFile> $submittedFilesData
     * @param array<mixed>        $submittedData
     */
    protected function assertFormSubmitOnPatch(array $submittedData, mixed $initialData, mixed $expected, array $submittedFilesData = []): void
    {
        $form = $this->createAndSubmitTestedForm($submittedData, Request::METHOD_PATCH, $initialData, $submittedFilesData);
        $this->assertSubmittedFormMatchesData($form, $expected);
    }

    /**
     * @param array<mixed> $submittedData
     */
    protected function assertFormSubmitOnPostWithInvalidData(array $submittedData, mixed $initialData = null): void
    {
        $this->assertFormSubmitWithInvalidData($submittedData, Request::METHOD_POST, $initialData);
    }

    /**
     * @param array<mixed> $submittedData
     */
    protected function assertFormSubmitOnPatchWithInvalidData(array $submittedData, mixed $initialData): void
    {
        $this->assertFormSubmitWithInvalidData($submittedData, Request::METHOD_PATCH, $initialData);
    }

    /**
     * @param array<mixed> $submittedData
     */
    protected function assertFormSubmitOnGetWithInvalidData(array $submittedData): void
    {
        $this->assertFormSubmitWithInvalidData($submittedData, Request::METHOD_GET, null);
    }

    /**
     * @param array<mixed>      $submittedData
     * @param Request::METHOD_* $method
     */
    private function assertFormSubmitWithInvalidData(array $submittedData, string $method, mixed $initialData): void
    {
        $form = $this->createAndSubmitTestedForm($submittedData, $method, $initialData);

        self::assertTrue($form->isSubmitted(), 'Form has to be submitted');
        self::assertTrue($form->isSynchronized(), 'Form data is not synchronized.');
        self::assertFalse($form->isValid(), 'Form is valid.');

        $actualErrors = (string) $form->getErrors(true, false);
        $expectedFile = $this->getExpectedContentFile('txt');

        try {
            self::assertStringEqualsFile($expectedFile, $actualErrors);
        } catch (ExpectationFailedException $e) {
            $comparisonFailure = $e->getComparisonFailure();
            if ($comparisonFailure !== null && DriverConfigurator::isOutputUpdaterEnabled()) {
                SnapshotUpdater::updateText(
                    $comparisonFailure,
                    $expectedFile,
                );
            }

            throw $e;
        }
    }

    /**
     * @param array<mixed>        $submittedRequestData
     * @param Request::METHOD_*   $method
     * @param array<UploadedFile> $submittedFilesData
     */
    protected function createAndSubmitTestedForm(
        array $submittedRequestData,
        string $method,
        mixed $initialData = null,
        array $submittedFilesData = [],
    ): FormInterface {
        $this->currentRequestMock->method('getMethod')->willReturn($method);
        $this->currentRequestMock->request = new InputBag($submittedRequestData);
        $this->currentRequestMock->files   = new FileBag($submittedFilesData);

        $this->requestStackMock->method('getCurrentRequest')->willReturn($this->currentRequestMock);

        $form = $this->createFormType($initialData);
        $form->submit(array_replace_recursive($submittedRequestData, $submittedFilesData), $method !== Request::METHOD_PATCH);

        return $form;
    }

    protected function assertSubmittedFormMatchesData(FormInterface $form, mixed $expected): void
    {
        self::assertSubmittedFormIsValid($form);
        if (! $expected instanceof Constraint) {
            $expected = new IsEqual($expected);
        }

        self::assertThat($form->getData(), $expected);
    }

    protected static function assertSubmittedFormIsValid(FormInterface $form): void
    {
        self::assertTrue($form->isSubmitted(), 'Form has to be submitted');
        self::assertTrue($form->isValid(), 'Form is not valid. Form errors: ' . PHP_EOL . $form->getErrors(true, false));
        self::assertTrue($form->isSynchronized(), 'Form data is not synchronized.');
    }
}
