<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\MethodCall>
 */
final class NoEloquentStorageMethodsInActionsRule implements Rule
{

    private const array ELOQUENT_STORAGE_METHODS = [
        'save',
        'create',
        'update',
        'delete',
        'forceDelete',
        'insert',
        'insertOrIgnore',
        'upsert',
        'updateOrCreate',
        'updateOrInsert',
        'firstOrCreate',
        'firstOrNew',
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param \PhpParser\Node\Expr\MethodCall $node
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->isInActionClass($scope)) {
            return [];
        }

        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();

        if (!in_array($methodName, self::ELOQUENT_STORAGE_METHODS, true)) {
            return [];
        }

        $callerType = $scope->getType($node->var);

        if (!$this->isEloquentModel($callerType)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Eloquent storage method "%s()" cannot be called directly in Action classes. Use ModelManager instead.',
                    $methodName,
                ),
            )->build(),
        ];
    }

    private function isInActionClass(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        return $classReflection->implementsInterface('Pekral\Arch\Action\ArchAction');
    }

    private function isEloquentModel(Type $type): bool
    {
        $eloquentModelType = new ObjectType('Illuminate\Database\Eloquent\Model');

        return $eloquentModelType->isSuperTypeOf($type)->yes();
    }

}
