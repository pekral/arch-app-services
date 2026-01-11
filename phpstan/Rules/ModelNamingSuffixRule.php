<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Class_>
 */
final readonly class ModelNamingSuffixRule implements Rule
{

    private const string ELOQUENT_MODEL_CLASS = Model::class;

    private const string REQUIRED_SUFFIX = 'Model';

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return Node\Stmt\Class_::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Stmt\Class_) {
            return [];
        }

        if ($node->extends === null) {
            return [];
        }

        if ($node->name === null) {
            return [];
        }

        $className = $node->name->name;

        if (!$this->extendsEloquentModel($node->extends, $scope)) {
            return [];
        }

        if ($this->hasCorrectSuffix($className)) {
            return [];
        }

        $fullClassName = $scope->getNamespace() !== null
            ? $scope->getNamespace() . '\\' . $className
            : $className;

        return [$this->createErrorMessage($fullClassName)];
    }

    private function createErrorMessage(string $className): RuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Model class "%s" extending Eloquent Model must end with "%s" suffix.',
                $className,
                self::REQUIRED_SUFFIX,
            ),
        )->build();
    }

    private function extendsEloquentModel(Node\Name $extends, Scope $scope): bool
    {
        $resolvedName = $scope->resolveName($extends);

        if ($resolvedName === self::ELOQUENT_MODEL_CLASS) {
            return true;
        }

        if (!$this->reflectionProvider->hasClass($resolvedName)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($resolvedName);

        return in_array(self::ELOQUENT_MODEL_CLASS, $classReflection->getParentClassesNames(), true);
    }

    private function hasCorrectSuffix(string $className): bool
    {
        return str_ends_with($className, self::REQUIRED_SUFFIX);
    }

}
