<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\Action\ArchAction;
use Pekral\Arch\PHPStan\Support\ActionClassMatcher;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that every class declared in an Actions namespace implements ArchAction. Every other
 * Action rule recognises an Action by that interface, so a class that does not implement it
 * silently escapes them.
 *
 * The namespaces are configured by the consuming project; without a configured namespace the rule
 * reports nothing. Anonymous classes are ignored.
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\InClassNode>
 */
final readonly class ActionsMustImplementArchActionRule implements Rule
{

    public const string IDENTIFIER = 'arch.actionMustImplementInterface';

    /**
     * @param list<string> $actionNamespaces Namespace prefixes (e.g. `App\Actions\`) that hold Actions.
     */
    public function __construct(private array $actionNamespaces = [])
    {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param \PHPStan\Node\InClassNode $node
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null
            || !$classReflection->isClass()
            || $classReflection->isAnonymous()
            || $classReflection->implementsInterface(ArchAction::class)
        ) {
            return [];
        }

        $namespace = ActionClassMatcher::findNamespace($classReflection, $this->actionNamespaces);

        if ($namespace === null) {
            return [];
        }

        $message = sprintf(
            'Class "%s" in the "%s" namespace must implement %s.',
            $classReflection->getName(),
            $namespace,
            ArchAction::class,
        );

        return [
            RuleErrorBuilder::message($message)
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

}
