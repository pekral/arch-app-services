<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\PHPStan\Support\ActionClassMatcher;
use Pekral\Arch\PHPStan\Support\HttpExceptionDetector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that an Action never produces an HTTP response exception. An Action throws a domain exception;
 * the entry point (controller, exception handler) translates it into an HTTP response.
 *
 * The reported constructs are the ones HttpExceptionDetector detects.
 *
 * An Action is a class implementing ArchAction. A project whose Actions do not all implement ArchAction yet
 * lists the namespaces that hold them in `$actionNamespaces`.
 *
 * A legitimate exception is silenced with PHPStan's standard `ignoreErrors` on the identifier
 * `arch.noHttpExceptionInAction`, scoped to the file.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final readonly class NoHttpExceptionsInActionsRule implements Rule
{

    public const string IDENTIFIER = 'arch.noHttpExceptionInAction';

    /**
     * @param list<string> $actionNamespaces Namespace prefixes (e.g. `App\Actions\`) whose classes count as Actions
     *                                       even when they do not implement ArchAction.
     */
    public function __construct(private ReflectionProvider $reflectionProvider, private array $actionNamespaces = [])
    {
    }

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param \PhpParser\Node\Expr $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null || !ActionClassMatcher::isAction($classReflection, $this->actionNamespaces)) {
            return [];
        }

        $violation = HttpExceptionDetector::detect($node, $scope, $this->reflectionProvider);

        if ($violation === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(HttpExceptionDetector::message('An Action', $violation))
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

}
