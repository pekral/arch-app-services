<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
final class NoLaravelHelpersForActionsRule implements Rule
{

    private const array FORBIDDEN_HELPERS = [
        'app',
        'resolve',
        'make',
    ];

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof FuncCall) {
            return [];
        }

        if (!$this->isInActionClass($scope)) {
            return [];
        }

        if (!$node->name instanceof Name) {
            return [];
        }

        $functionName = $node->name->toString();

        if (!in_array($functionName, self::FORBIDDEN_HELPERS, true)) {
            return [];
        }

        return $this->checkFirstArgument($node, $scope, $functionName);
    }

    /**
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    private function checkFirstArgument(FuncCall $node, Scope $scope, string $functionName): array
    {
        if (count($node->args) === 0) {
            return [];
        }

        $firstArg = $node->args[0] ?? null;

        if (!$firstArg instanceof Arg) {
            return [];
        }

        if ($this->isActionClassConst($firstArg->value, $scope)) {
            return [$this->createError($functionName)];
        }

        $argType = $scope->getType($firstArg->value);

        if ($this->isActionType($argType)) {
            return [$this->createError($functionName)];
        }

        return [];
    }

    private function createError(string $functionName): RuleError
    {
        return RuleErrorBuilder::message(
            sprintf(
                'Laravel helper "%s()" cannot be used to resolve Action classes. Actions must be injected via constructor.',
                $functionName,
            ),
        )->build();
    }

    private function isInActionClass(Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        return $classReflection->implementsInterface('Pekral\Arch\Action\ArchAction');
    }

    private function isActionClassConst(Node $node, Scope $scope): bool
    {
        if (!$node instanceof ClassConstFetch) {
            return false;
        }

        if (!$node->class instanceof Name) {
            return false;
        }

        $resolvedType = $scope->resolveTypeByName($node->class);

        $actionType = new ObjectType('Pekral\Arch\Action\ArchAction');

        return $actionType->isSuperTypeOf($resolvedType)->yes();
    }

    private function isActionType(Type $type): bool
    {
        $actionType = new ObjectType('Pekral\Arch\Action\ArchAction');

        if ($actionType->isSuperTypeOf($type)->yes()) {
            return true;
        }

        $classStringType = $type->getClassStringObjectType();

        return $actionType->isSuperTypeOf($classStringType)->yes();
    }

}
