<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\PHPStan\Support\ArchitectureLayerMatcher;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids static properties in architecture-layer classes (Action, Service, Repository, Model Manager).
 * A static property is state shared by every instance and every request a long-running worker (queue
 * worker, Octane) serves, so it leaks data between jobs and requests.
 *
 * PHP cannot declare a readonly static property, so every static property is mutable and is reported.
 * Class constants and static methods are allowed.
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\ClassPropertyNode>
 */
final readonly class NoMutableStaticStateRule implements Rule
{

    public const string IDENTIFIER = 'arch.mutableStaticState';

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @param \PHPStan\Node\ClassPropertyNode $node
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (!$node->isStatic() || !ArchitectureLayerMatcher::isArchitectureClass($classReflection)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Static property %s::$%s holds mutable state shared across instances, requests and queue jobs'
                    . ' in long-running workers. Use a class constant, an injected dependency or a cache instead.',
                    $classReflection->getName(),
                    $node->getName(),
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

}
