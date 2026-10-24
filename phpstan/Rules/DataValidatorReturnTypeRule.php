<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Pekral\Arch\PHPStan\Support\DataValidatorClassMatcher;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces that every public method of a Data Validator declares the native return type `bool` (is the input
 * valid?) or `void` (assert the input is valid, failure signalled by an exception). The rule does not restrict
 * thrown exceptions; HTTP response exceptions are handled by NoHttpExceptionsInDataValidatorsRule.
 *
 * The constructor and non-public helpers are not checked. A Data Validator is recognised by
 * DataValidatorClassMatcher, optionally limited by `$dataValidatorNamespaces`.
 *
 * A legitimate exception is silenced with PHPStan's standard `ignoreErrors` on the identifier
 * `arch.dataValidatorReturnType`, scoped to the file.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\ClassMethod>
 */
final readonly class DataValidatorReturnTypeRule implements Rule
{

    public const string IDENTIFIER = 'arch.dataValidatorReturnType';

    private const array ALLOWED_RETURN_TYPES = ['bool', 'void'];

    /**
     * @param list<string> $dataValidatorNamespaces Namespace prefixes (e.g. `App\`) the rule is limited to. Empty means
     *                                              every namespace.
     */
    public function __construct(private array $dataValidatorNamespaces = [])
    {
    }

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\ClassMethod $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null || !DataValidatorClassMatcher::isDataValidator($classReflection, $this->dataValidatorNamespaces)) {
            return [];
        }

        if (!$node->isPublic() || $node->name->toLowerString() === '__construct' || $this->hasAllowedReturnType($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Public method "%s::%s()" of a Data Validator must declare the return type bool or void.',
                    $classReflection->getName(),
                    $node->name->toString(),
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    private function hasAllowedReturnType(ClassMethod $method): bool
    {
        return $method->returnType instanceof Identifier
            && in_array($method->returnType->toLowerString(), self::ALLOWED_RETURN_TYPES, strict: true);
    }

}
