<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Pekral\Arch\PHPStan\Support\ActionClassMatcher;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Enforces that an Action and a Data Validator never produce an HTTP response exception. Both layers
 * throw a domain exception; the entry point (controller, exception handler) translates it into an
 * HTTP response.
 *
 * Reported: `throw` of, and `throw_if()` / `throw_unless()` with, a Symfony HttpExceptionInterface
 * or an Illuminate HttpResponseException (as a class-string or as an instance), and every call of
 * `abort()`, `abort_if()` and `abort_unless()`.
 *
 * - An Action is a class implementing ArchAction. A project whose Actions do not all implement
 *   ArchAction yet lists the namespaces that hold them in `$actionNamespaces`.
 * - A Data Validator is a class whose name ends with `DataValidator`.
 *
 * A legitimate exception is silenced with PHPStan's standard `ignoreErrors` on the identifier
 * (`arch.noHttpExceptionInAction`, `arch.noHttpExceptionInDataValidator`), scoped to the file.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr>
 */
final readonly class NoHttpExceptionsInActionsAndDataValidatorsRule implements Rule
{

    public const string ACTION_IDENTIFIER = 'arch.noHttpExceptionInAction';

    public const string DATA_VALIDATOR_IDENTIFIER = 'arch.noHttpExceptionInDataValidator';

    private const string DATA_VALIDATOR_CLASS_SUFFIX = 'DataValidator';

    private const array ABORT_FUNCTIONS = ['abort', 'abort_if', 'abort_unless'];

    private const array THROW_FUNCTIONS = ['throw_if', 'throw_unless'];

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

        if ($classReflection === null) {
            return [];
        }

        $identifier = $this->resolveLayerIdentifier($classReflection);

        if ($identifier === null) {
            return [];
        }

        $violation = $this->resolveViolation($node, $scope);

        if ($violation === null) {
            return [];
        }

        $message = sprintf(
            '%s must not produce an HTTP response exception (%s). Throw a domain exception such as %s instead'
            . ' and let the entry point translate it into an HTTP response.',
            $identifier === self::ACTION_IDENTIFIER ? 'An Action' : 'A Data Validator',
            $violation,
            ModelNotFoundException::class,
        );

        return [
            RuleErrorBuilder::message($message)
                ->identifier($identifier)
                ->build(),
        ];
    }

    private function resolveLayerIdentifier(ClassReflection $classReflection): ?string
    {
        if (ActionClassMatcher::isAction($classReflection, $this->actionNamespaces)) {
            return self::ACTION_IDENTIFIER;
        }

        if (str_ends_with($classReflection->getName(), self::DATA_VALIDATOR_CLASS_SUFFIX)) {
            return self::DATA_VALIDATOR_IDENTIFIER;
        }

        return null;
    }

    private function resolveViolation(Expr $node, Scope $scope): ?string
    {
        if ($node instanceof Throw_) {
            return $this->resolveHttpException($node->expr, $scope);
        }

        if (!$node instanceof FuncCall || !$node->name instanceof Name) {
            return null;
        }

        $functionName = $node->name->toLowerString();

        if (in_array($functionName, self::ABORT_FUNCTIONS, strict: true)) {
            return $functionName . '()';
        }

        if (!in_array($functionName, self::THROW_FUNCTIONS, strict: true)) {
            return null;
        }

        $arguments = $node->getArgs();

        if (!isset($arguments[1])) {
            return null;
        }

        return $this->resolveHttpException($arguments[1]->value, $scope);
    }

    private function resolveHttpException(Expr $expression, Scope $scope): ?string
    {
        $type = $scope->getType($expression);

        foreach ($type->getConstantStrings() as $constantString) {
            $matched = $this->matchHttpException($constantString->getValue());

            if ($matched !== null) {
                return $matched;
            }
        }

        foreach ($type->getObjectClassNames() as $objectClassName) {
            $matched = $this->matchHttpException($objectClassName);

            if ($matched !== null) {
                return $matched;
            }
        }

        return null;
    }

    private function matchHttpException(string $className): ?string
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        if ($classReflection->is(HttpExceptionInterface::class) || $classReflection->is(HttpResponseException::class)) {
            return $classReflection->getName();
        }

        return null;
    }

}
