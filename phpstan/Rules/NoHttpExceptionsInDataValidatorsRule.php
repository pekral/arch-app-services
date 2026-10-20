<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\PHPStan\Support\HttpExceptionDetector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that a Data Validator never produces an HTTP response exception. A Data Validator throws a
 * validation or domain exception; the entry point (controller, exception handler) translates it into an
 * HTTP response.
 *
 * The reported constructs are the ones HttpExceptionDetector detects.
 *
 * A Data Validator is a class whose name ends with `DataValidator`. A project that keeps such classes outside
 * its own code (fixtures, vendored copies) limits the rule to its namespaces with `$dataValidatorNamespaces`.
 *
 * A legitimate exception is silenced with PHPStan's standard `ignoreErrors` on the identifier
 * `arch.noHttpExceptionInDataValidator`, scoped to the file.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final readonly class NoHttpExceptionsInDataValidatorsRule implements Rule
{

    public const string IDENTIFIER = 'arch.noHttpExceptionInDataValidator';

    private const string CLASS_SUFFIX = 'DataValidator';

    /**
     * @param list<string> $dataValidatorNamespaces Namespace prefixes (e.g. `App\`) the rule is limited to. Empty means
     *                                              every namespace.
     */
    public function __construct(private ReflectionProvider $reflectionProvider, private array $dataValidatorNamespaces = [])
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

        if ($classReflection === null || !$this->isDataValidator($classReflection)) {
            return [];
        }

        $violation = HttpExceptionDetector::detect($node, $scope, $this->reflectionProvider);

        if ($violation === null) {
            return [];
        }

        return [
            RuleErrorBuilder::message(HttpExceptionDetector::message('A Data Validator', $violation))
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    private function isDataValidator(ClassReflection $classReflection): bool
    {
        $className = $classReflection->getName();

        if (!str_ends_with($className, self::CLASS_SUFFIX)) {
            return false;
        }

        return $this->dataValidatorNamespaces === []
            || array_any(
                $this->dataValidatorNamespaces,
                static fn (string $namespace): bool => str_starts_with($className, $namespace),
            );
    }

}
