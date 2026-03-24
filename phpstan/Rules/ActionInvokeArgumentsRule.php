<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Enforces that ArchAction instances are always invoked without arguments.
 *
 * All inputs to an action must be provided via constructor injection.
 * Calling an action with arguments (e.g. `$action($a, $b)`) breaks the
 * readonly DI pattern and is forbidden — use `$action()` instead.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\FuncCall>
 */
final readonly class ActionInvokeArgumentsRule implements Rule
{

    private const string ARCH_ACTION_INTERFACE = 'Pekral\Arch\Action\ArchAction';

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param \PhpParser\Node\Expr\FuncCall $node
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof FuncCall) {
            return [];
        }

        // Named function calls like foo() are not action invocations
        if ($node->name instanceof Node\Name) {
            return [];
        }

        if (!$node->name instanceof Node\Expr) {
            return [];
        }

        // No arguments — valid pattern: $action()
        if ($node->args === []) {
            return [];
        }

        $calleeType = $scope->getType($node->name);

        if (!$this->isArchAction($calleeType)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'ArchAction must be invoked without arguments. Provide all inputs via constructor injection and call the action as $action().',
            )->build(),
        ];
    }

    private function isArchAction(Type $type): bool
    {
        return new ObjectType(self::ARCH_ACTION_INTERFACE)
            ->isSuperTypeOf($type)
            ->yes();
    }

}
