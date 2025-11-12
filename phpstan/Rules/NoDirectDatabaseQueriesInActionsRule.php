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
final class NoDirectDatabaseQueriesInActionsRule implements Rule
{

    private const array QUERY_BUILDER_METHODS = [
        'where',
        'whereIn',
        'whereNotIn',
        'whereBetween',
        'whereNull',
        'whereNotNull',
        'orWhere',
        'find',
        'findOrFail',
        'first',
        'firstOrFail',
        'get',
        'all',
        'pluck',
        'count',
        'sum',
        'avg',
        'min',
        'max',
        'exists',
        'doesntExist',
        'orderBy',
        'limit',
        'offset',
        'join',
        'leftJoin',
        'rightJoin',
        'select',
        'with',
        'withCount',
        'has',
        'whereHas',
        'doesntHave',
        'whereDoesntHave',
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

        if (!$this->isInActionClass($scope)) {
            return [];
        }

        $methodName = $this->getMethodName($node);

        if ($methodName === null || !$this->isQueryBuilderMethod($methodName)) {
            return [];
        }

        $callerType = $this->getCallerType($node, $scope);

        if ($callerType === null || !$this->isEloquentModelOrBuilder($callerType)) {
            return [];
        }

        return $this->createErrorMessage($methodName);
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

    private function isQueryBuilderMethod(string $methodName): bool
    {
        return in_array($methodName, self::QUERY_BUILDER_METHODS, true);
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
    private function createErrorMessage(string $methodName): array
    {
        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Direct database query method "%s()" cannot be called in Action classes. Use Repository pattern instead.',
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

    private function isEloquentModelOrBuilder(Type $type): bool
    {
        $eloquentModelType = new ObjectType('Illuminate\Database\Eloquent\Model');
        $queryBuilderType = new ObjectType('Illuminate\Database\Eloquent\Builder');

        return $eloquentModelType->isSuperTypeOf($type)->yes() || $queryBuilderType->isSuperTypeOf($type)->yes();
    }

}
