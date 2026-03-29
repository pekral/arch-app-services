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
 * Forbids whereRaw() and orWhereRaw() calls on Eloquent models and query builders.
 * Raw where clauses bypass parameter binding and pose a SQL injection risk.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final class NoWhereRawRule implements Rule
{

    private const array FORBIDDEN_METHODS = [
        'whereRaw',
        'orWhereRaw',
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
        if (!$node instanceof MethodCall && !$node instanceof StaticCall) {
            return [];
        }

        $methodName = $this->getMethodName($node);

        if ($methodName === null || !$this->isForbiddenMethod($methodName)) {
            return [];
        }

        $callerType = $this->getCallerType($node, $scope);

        if ($callerType === null || !$this->isEloquentModelOrBuilder($callerType)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Method "%s()" is forbidden. Raw where clauses bypass parameter binding '
                    . 'and pose a SQL injection risk. Use Eloquent query builder methods instead.',
                    $methodName,
                ),
            )->build(),
        ];
    }

    private function getMethodName(MethodCall|StaticCall $node): ?string
    {
        if (!$node->name instanceof Node\Identifier) {
            return null;
        }

        return $node->name->toString();
    }

    private function isForbiddenMethod(string $methodName): bool
    {
        return in_array($methodName, self::FORBIDDEN_METHODS, true);
    }

    private function getCallerType(MethodCall|StaticCall $node, Scope $scope): ?Type
    {
        if ($node instanceof MethodCall) {
            return $scope->getType($node->var);
        }

        if ($node->class instanceof Node\Name) {
            return $scope->resolveTypeByName($node->class);
        }

        return null;
    }

    private function isEloquentModelOrBuilder(Type $type): bool
    {
        $eloquentModelType = new ObjectType('Illuminate\Database\Eloquent\Model');
        $queryBuilderType = new ObjectType('Illuminate\Database\Eloquent\Builder');
        $baseBuilderType = new ObjectType('Illuminate\Database\Query\Builder');

        return $eloquentModelType->isSuperTypeOf($type)->yes()
            || $queryBuilderType->isSuperTypeOf($type)->yes()
            || $baseBuilderType->isSuperTypeOf($type)->yes();
    }

}
