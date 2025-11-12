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

    private const array ALLOWED_RETRIEVAL_METHODS = [
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
    ];

    private const array ALWAYS_FORBIDDEN_METHODS = [
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
        'truncate',
        'delete',
        'chunk',
        'chunkById',
        'each',
        'lazy',
        'lazyById',
        'cursor',
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

        if ($methodName === null) {
            return [];
        }

        $callerType = $this->getCallerType($node, $scope);

        if ($callerType === null || !$this->isEloquentModelOrBuilder($callerType)) {
            return [];
        }

        if ($this->isAlwaysForbiddenMethod($methodName)) {
            return $this->createErrorMessage($methodName);
        }

        if ($this->isAllowedRetrievalMethod($methodName)) {
            return $this->handleRetrievalMethod($node, $scope, $methodName);
        }

        if ($this->isSafeBuilderMethod($methodName)) {
            return [];
        }

        return $this->createScopeErrorMessage($methodName);
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function handleRetrievalMethod(Node $node, Scope $scope, string $methodName): array
    {
        if (!$node instanceof MethodCall) {
            return [];
        }

        $conditionalInfo = $this->findConditionalMethodInChain($node->var, $scope);

        if ($conditionalInfo !== null) {
            $conditionType = $conditionalInfo['type'] === 'scope' ? 'scope' : 'query builder method';
            $errorMessage = sprintf(
                'Calling "%s()" after %s "%s()" is not allowed in Action classes. Data retrieval with conditions must be encapsulated in Repository class.',
                $methodName,
                $conditionType,
                $conditionalInfo['method'],
            );

            return [
                RuleErrorBuilder::message($errorMessage)->build(),
            ];
        }

        return [];
    }

    /**
     * @return array{method: string, type: string}|null
     */
    private function findConditionalMethodInChain(Node $node, Scope $scope): ?array
    {
        $currentNode = $node;

        while ($currentNode instanceof MethodCall) {
            $conditionalInfo = $this->checkMethodCallForCondition($currentNode, $scope);

            if ($conditionalInfo !== null) {
                return $conditionalInfo;
            }

            $currentNode = $currentNode->var;
        }

        return null;
    }

    /**
     * @return array{method: string, type: string}|null
     */
    private function checkMethodCallForCondition(MethodCall $node, Scope $scope): ?array
    {
        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        $methodName = $node->name->toString();

        if ($this->isAlwaysForbiddenMethod($methodName)) {
            return ['method' => $methodName, 'type' => 'conditional'];
        }

        if ($this->isSafeBuilderMethod($methodName)) {
            return null;
        }

        if ($this->isPotentialScope($node, $scope)) {
            return ['method' => $methodName, 'type' => 'scope'];
        }

        return null;
    }

    private function isPotentialScope(MethodCall $node, Scope $scope): bool
    {
        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        $methodName = $node->name->toString();

        if ($this->isAllowedRetrievalMethod($methodName)
            || $this->isAlwaysForbiddenMethod($methodName)
            || $this->isSafeBuilderMethod($methodName)
        ) {
            return false;
        }

        $callerType = $scope->getType($node->var);

        return $this->isEloquentModelOrBuilder($callerType);
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

    private function isAllowedRetrievalMethod(string $methodName): bool
    {
        return in_array($methodName, self::ALLOWED_RETRIEVAL_METHODS, true);
    }

    private function isAlwaysForbiddenMethod(string $methodName): bool
    {
        return in_array($methodName, self::ALWAYS_FORBIDDEN_METHODS, true);
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
    private function createErrorMessage(string $methodName): array
    {
        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Query builder method "%s()" cannot be called in Action classes. Data retrieval with conditions must be in Repository class.',
                    $methodName,
                ),
            )->build(),
        ];
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function createScopeErrorMessage(string $methodName): array
    {
        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Eloquent scope "%s()" cannot be called in Action classes. Data retrieval with conditions must be in Repository class.',
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
