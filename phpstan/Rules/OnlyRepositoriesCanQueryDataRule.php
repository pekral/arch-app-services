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
 * Ensures Eloquent query builder methods are not used in Action classes.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final class OnlyRepositoriesCanQueryDataRule implements Rule
{

    private const array QUERY_METHODS = [
        'where',
        'whereIn',
        'whereNotIn',
        'whereBetween',
        'whereNotBetween',
        'whereNull',
        'whereNotNull',
        'orWhere',
        'whereDate',
        'whereMonth',
        'whereDay',
        'whereYear',
        'whereTime',
        'whereColumn',
        'whereHas',
        'whereDoesntHave',
        'orWhereHas',
        'orWhereDoesntHave',
        'withWhereHas',
        'orderBy',
        'orderByDesc',
        'latest',
        'oldest',
        'inRandomOrder',
        'limit',
        'take',
        'offset',
        'skip',
        'join',
        'leftJoin',
        'rightJoin',
        'crossJoin',
        'select',
        'selectRaw',
        'addSelect',
        'with',
        'withCount',
        'withSum',
        'withAvg',
        'withMin',
        'withMax',
        'has',
        'doesntHave',
        'orHas',
        'orDoesntHave',
        'groupBy',
        'having',
        'havingRaw',
        'orHaving',
        'orHavingRaw',
        'chunk',
        'chunkById',
        'each',
        'lazy',
        'lazyById',
        'cursor',
        'get',
        'all',
        'first',
        'firstOrFail',
        'find',
        'findOrFail',
        'count',
        'sum',
        'avg',
        'min',
        'max',
        'exists',
        'doesntExist',
        'pluck',
        'paginate',
        'simplePaginate',
        'cursorPaginate',
    ];

    private const array SAFE_BUILDER_METHODS = [
        'query',
        'newQuery',
        'toBase',
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

        if ($methodName === null || $this->isSafeBuilderMethod($methodName) || !$this->isQueryMethod($methodName)) {
            return [];
        }

        $callerType = $this->getCallerType($node, $scope);

        if ($callerType === null || !$this->isEloquentModelOrBuilder($callerType)) {
            return [];
        }

        if ($this->isDynamoDbModel($callerType)) {
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

    private function isQueryMethod(string $methodName): bool
    {
        return in_array($methodName, self::QUERY_METHODS, true);
    }

    private function isSafeBuilderMethod(string $methodName): bool
    {
        return in_array($methodName, self::SAFE_BUILDER_METHODS, true);
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
                    'Eloquent query method "%s()" cannot be called in Action classes. Found in: %s',
                    $methodName,
                    $currentClass,
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

        return $eloquentModelType->isSuperTypeOf($type)->yes()
            || $queryBuilderType->isSuperTypeOf($type)->yes();
    }

    private function isDynamoDbModel(Type $type): bool
    {
        $dynamoDbModelType = new ObjectType('BaoPham\DynamoDb\DynamoDbModel');

        return $dynamoDbModelType->isSuperTypeOf($type)->yes();
    }

}
