<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final class OnlyModelManagersCanPersistDataRule implements Rule
{

    private const array PERSISTENCE_METHODS = [
        'save',
        'create',
        'update',
        'delete',
        'forceDelete',
        'restore',
        'insert',
        'insertOrIgnore',
        'upsert',
        'updateOrCreate',
        'updateOrInsert',
        'firstOrCreate',
    ];

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isValidMethodOrStaticCall($node)) {
            return [];
        }

        if ($this->isInModelManagerClass($scope)) {
            return [];
        }

        $methodName = $this->getMethodName($node);

        if ($methodName === null || !$this->isPersistenceMethod($methodName)) {
            return [];
        }

        $callerType = $this->getCallerType($node, $scope);

        if ($callerType === null || !$this->isEloquentModel($callerType)) {
            return [];
        }

        return $this->createErrorMessage($methodName, $scope);
    }

    private function isValidMethodOrStaticCall(Node $node): bool
    {
        return $node instanceof MethodCall || $node instanceof StaticCall;
    }

    private function getMethodName(Node $node): ?string
    {
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return null;
        }

        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        return $node->name->toString();
    }

    private function isPersistenceMethod(string $methodName): bool
    {
        return in_array($methodName, self::PERSISTENCE_METHODS, true);
    }

    private function getCallerType(Node $node, Scope $scope): ?Type
    {
        if ($node instanceof MethodCall) {
            return $scope->getType($node->var);
        }

        if ($node instanceof StaticCall && $node->class instanceof Node\Name) {
            return $scope->resolveTypeByName($node->class);
        }

        return null;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function createErrorMessage(string $methodName, Scope $scope): array
    {
        $currentClass = $scope->getClassReflection()?->getName() ?? 'unknown';

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Eloquent persistence method "%s()" can only be called in classes extending BaseModelManager or BaseModelService. Found in: %s',
                    $methodName,
                    $currentClass,
                ),
            )->build(),
        ];
    }

    private function isInModelManagerClass(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        $allowedClasses = [
            'Pekral\Arch\ModelManager\Mysql\BaseModelManager',
            'Pekral\Arch\Service\BaseModelService',
        ];

        $currentClassName = $classReflection->getName();

        if (in_array($currentClassName, $allowedClasses, true)) {
            return true;
        }

        foreach ($classReflection->getAncestors() as $ancestor) {
            if (in_array($ancestor->getName(), $allowedClasses, true)) {
                return true;
            }
        }

        return false;
    }

    private function isEloquentModel(Type $type): bool
    {
        $eloquentModelType = new ObjectType('Illuminate\Database\Eloquent\Model');

        return $eloquentModelType->isSuperTypeOf($type)->yes();
    }

}
