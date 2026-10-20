<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\PHPStan\Support\ArchitectureLayerMatcher;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids constructing an architecture-managed class (Action, Service, Repository, Model Manager) with
 * `new`. These classes are resolved by the container through constructor injection, so their own
 * dependencies stay explicit and container-managed. Complements NoLaravelHelpersForActionsRule, which
 * forbids resolving Actions through service-locator helpers.
 *
 * The instantiated class is recognised by type (`new X`, `new static`, `new $className`), never by a
 * class-name suffix. DTOs, value objects, Eloquent models and any other class stay constructible, and
 * so do anonymous classes. The package itself constructs none of these classes.
 *
 * Code that legitimately builds them by hand (tests, a factory) is excluded with PHPStan's standard
 * `ignoreErrors` on the identifier `arch.manualArchitectureInstantiation`, or by leaving it out of the
 * analysed paths.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\New_>
 */
final readonly class NoManualArchitectureServiceInstantiationRule implements Rule
{

    public const string IDENTIFIER = 'arch.manualArchitectureInstantiation';

    public function getNodeType(): string
    {
        return New_::class;
    }

    /**
     * @param \PhpParser\Node\Expr\New_ $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($scope->getType($node)->getObjectClassReflections() as $classReflection) {
            if (!$this->isManagedClass($classReflection)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Architecture class %s must not be instantiated with "new". Inject it through the constructor'
                    . ' so the container resolves it and its dependencies.',
                    $classReflection->getName(),
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build();
        }

        return $errors;
    }

    private function isManagedClass(ClassReflection $classReflection): bool
    {
        return !$classReflection->isAnonymous() && ArchitectureLayerMatcher::isArchitectureClass($classReflection);
    }

}
