<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that the __invoke() method of any ArchAction never returns a raw array.
 * All structured data returned from an action must be wrapped in a DTO
 * (a class extending Spatie\LaravelData\Data).
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final readonly class ActionReturnDtoRule implements Rule
{

    private const string ARCH_ACTION_INTERFACE = 'Pekral\Arch\Action\ArchAction';

    private const string INVOKE_METHOD = '__invoke';

    private const string SPATIE_DATA_CLASS = 'Spatie\LaravelData\Data';

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof ClassMethod) {
            return [];
        }

        if ($node->name->toString() !== self::INVOKE_METHOD) {
            return [];
        }

        $classReflection = $this->resolveActionClassReflection($scope);

        if ($classReflection === null) {
            return [];
        }

        return $this->validateReturnType($classReflection, $scope);
    }

    /**
     * Returns the class reflection only when the current scope is inside an ArchAction class.
     */
    private function resolveActionClassReflection(Scope $scope): ?ClassReflection
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        if (!$classReflection->implementsInterface(self::ARCH_ACTION_INTERFACE)) {
            return null;
        }

        return $classReflection;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function validateReturnType(ClassReflection $classReflection, Scope $scope): array
    {
        $methodReflection = $classReflection->getMethod(self::INVOKE_METHOD, $scope);
        $variant = ParametersAcceptorSelector::combineAcceptors($methodReflection->getVariants());

        if (!$variant->getReturnType()->isArray()->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Action class "%s" __invoke() must not return array. Use a DTO extending "%s" instead.',
                    $classReflection->getName(),
                    self::SPATIE_DATA_CLASS,
                ),
            )->build(),
        ];
    }

}
