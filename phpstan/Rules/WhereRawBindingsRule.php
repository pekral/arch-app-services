<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Ensures whereRaw() calls with dynamic SQL expressions always provide bindings to prevent SQL injection.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final class WhereRawBindingsRule implements Rule
{

    private const array RAW_METHODS = [
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

        if ($methodName === null || !$this->isRawMethod($methodName)) {
            return [];
        }

        $callerType = $this->getCallerType($node, $scope);

        if ($callerType === null || !$this->isQueryBuilderOrModel($callerType)) {
            return [];
        }

        if ($this->hasBindingsArgument($node)) {
            return [];
        }

        if ($this->isStaticStringLiteral($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Call to %s() with a dynamic expression requires bindings (second argument) to prevent SQL injection.',
                    $methodName,
                ),
            )->build(),
        ];
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

    private function isRawMethod(string $methodName): bool
    {
        return in_array($methodName, self::RAW_METHODS, true);
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

    private function isQueryBuilderOrModel(Type $type): bool
    {
        $eloquentModelType = new ObjectType('Illuminate\Database\Eloquent\Model');
        $eloquentBuilderType = new ObjectType('Illuminate\Database\Eloquent\Builder');
        $queryBuilderType = new ObjectType('Illuminate\Database\Query\Builder');

        return $eloquentModelType->isSuperTypeOf($type)->yes()
            || $eloquentBuilderType->isSuperTypeOf($type)->yes()
            || $queryBuilderType->isSuperTypeOf($type)->yes();
    }

    /**
     * Checks whether the call has a second argument (bindings array).
     */
    private function hasBindingsArgument(MethodCall|StaticCall $node): bool
    {
        return count($node->getArgs()) >= 2;
    }

    /**
     * Returns true when the first argument is a plain string literal without interpolation or concatenation.
     */
    private function isStaticStringLiteral(MethodCall|StaticCall $node): bool
    {
        $args = $node->getArgs();

        if ($args === []) {
            return false;
        }

        return $args[0]->value instanceof String_;
    }

}
