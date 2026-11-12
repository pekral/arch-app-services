<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Pekral\Arch\PHPStan\Support\ActionClassMatcher;
use Pekral\Arch\PHPStan\Support\DataValidatorClassMatcher;
use Pekral\Arch\PHPStan\Support\HttpExceptionDetector;
use Pekral\Arch\Transaction\InTransaction;
use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\AssignOp\Coalesce as CoalesceAssign;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalOr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\InClassNode>
 */
final readonly class NoSimpleLogicActionsRule implements Rule
{

    public const string IDENTIFIER = 'arch.simpleLogicAction';

    private const string INVOKE_METHOD = '__invoke';

    private const string CONSTRUCTOR = '__construct';

    private const array BRANCHING_NODES = [
        If_::class,
        Switch_::class,
        Match_::class,
        Ternary::class,
        Coalesce::class,
        CoalesceAssign::class,
        BooleanAnd::class,
        BooleanOr::class,
        LogicalAnd::class,
        LogicalOr::class,
        While_::class,
        Do_::class,
        For_::class,
        TryCatch::class,
        Throw_::class,
    ];

    private const array BRANCHING_FUNCTIONS = [
        ...HttpExceptionDetector::ABORT_FUNCTIONS,
        ...HttpExceptionDetector::THROW_FUNCTIONS,
        'rescue',
    ];

    private const array BRANCHING_METHODS = ['when', 'unless'];

    private const array GUARD_METHODS = [
        'validate',
        'authorize',
        'authorizeforuser',
        'allows',
        'denies',
        'can',
        'cannot',
        'transaction',
        'begintransaction',
        'savepoint',
    ];

    private const array GUARD_CLASSES = [Gate::class, Validator::class];

    /**
     * @param list<string> $actionNamespaces
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
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getOriginalNode();
        $classReflection = $scope->getClassReflection();

        if (!$classReflection instanceof ClassReflection
            || !$class instanceof Class_
            || $class->isAbstract()
            || !$class->getMethod(self::INVOKE_METHOD) instanceof ClassMethod
            || !ActionClassMatcher::isAction($classReflection, $this->actionNamespaces)
            || $this->holdsLogic($class, $classReflection)
        ) {
            return [];
        }

        $message = sprintf(
            'Action "%s" holds only simple logic (no branching, transaction, validation, authorization or second collaborator).'
            . ' Inline it at its call site; when several call sites reuse it, ignore this error by its identifier.',
            $classReflection->getName(),
        );

        return [
            RuleErrorBuilder::message($message)
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    private function holdsLogic(Class_ $class, ClassReflection $classReflection): bool
    {
        $methods = array_values(array_filter(
            $class->getMethods(),
            static fn (ClassMethod $method): bool => $method->name->toLowerString() !== self::CONSTRUCTOR,
        ));
        $nodeFinder = new NodeFinder();

        if ($nodeFinder->findFirst($methods, $this->isLogicNode(...)) instanceof Node) {
            return true;
        }

        $collaborators = [];

        foreach ($nodeFinder->findInstanceOf($methods, Expr::class) as $expression) {
            $collaborator = $this->resolveCollaborator($expression, $class);

            if ($collaborator !== null) {
                $collaborators[$collaborator] = true;
            }
        }

        return count($collaborators) > 1
            || array_any(
                array_keys($collaborators),
                fn (string $collaborator): bool => $this->isDataValidatorProperty($classReflection, $collaborator),
            );
    }

    private function isLogicNode(Node $node): bool
    {
        return array_any(self::BRANCHING_NODES, static fn (string $nodeClass): bool => $node instanceof $nodeClass)
            || ($node instanceof Attribute && $node->name->toString() === InTransaction::class)
            || $this->isBranchingCall($node)
            || $this->isGuardCall($node);
    }

    private function isBranchingCall(Node $node): bool
    {
        if ($node instanceof FuncCall) {
            return $node->name instanceof Name && in_array($node->name->toLowerString(), self::BRANCHING_FUNCTIONS, strict: true);
        }

        return $this->isMethodCall($node)
            && $node->name instanceof Identifier
            && in_array($node->name->toLowerString(), self::BRANCHING_METHODS, strict: true);
    }

    private function isGuardCall(Node $node): bool
    {
        if (!$this->isMethodCall($node) && !$node instanceof StaticCall) {
            return false;
        }

        if ($node instanceof StaticCall && $node->class instanceof Name && in_array($node->class->toString(), self::GUARD_CLASSES, strict: true)) {
            return true;
        }

        return $node->name instanceof Identifier && in_array($node->name->toLowerString(), self::GUARD_METHODS, strict: true);
    }

    private function resolveCollaborator(Expr $expression, Class_ $class): ?string
    {
        return match (true) {
            $expression instanceof MethodCall, $expression instanceof NullsafeMethodCall => $this->resolveMethodCallCollaborator($expression, $class),
            $expression instanceof StaticCall => $this->resolveStaticCallCollaborator($expression),
            $expression instanceof FuncCall => $this->resolveReceiverProperty($expression->name),
            default => null,
        };
    }

    private function resolveMethodCallCollaborator(MethodCall|NullsafeMethodCall $call, Class_ $class): ?string
    {
        if (!$this->isThis($call->var)) {
            return $this->resolveReceiverProperty($call->var);
        }

        if (!$call->name instanceof Identifier || $class->getMethod($call->name->toString()) instanceof ClassMethod) {
            return null;
        }

        return 'method:' . $call->name->toLowerString();
    }

    private function resolveStaticCallCollaborator(StaticCall $call): ?string
    {
        if (!$call->class instanceof Name || $call->class->isSpecialClassName()) {
            return null;
        }

        return 'class:' . $call->class->toString();
    }

    private function resolveReceiverProperty(Node $receiver): ?string
    {
        while ($this->isMethodCall($receiver)
            || $receiver instanceof PropertyFetch
            || $receiver instanceof NullsafePropertyFetch
        ) {
            if (($receiver instanceof PropertyFetch || $receiver instanceof NullsafePropertyFetch)
                && $this->isThis($receiver->var)
                && $receiver->name instanceof Identifier
            ) {
                return 'property:' . $receiver->name->toString();
            }

            $receiver = $receiver->var;
        }

        return null;
    }

    private function isDataValidatorProperty(ClassReflection $classReflection, string $collaborator): bool
    {
        $propertyName = substr($collaborator, strlen('property:'));

        return str_starts_with($collaborator, 'property:')
            && $classReflection->hasNativeProperty($propertyName)
            && array_any(
                $classReflection->getNativeProperty($propertyName)->getNativeType()->getObjectClassReflections(),
                static fn (ClassReflection $propertyClass): bool => DataValidatorClassMatcher::isDataValidator($propertyClass, []),
            );
    }

    /**
     * @phpstan-assert-if-true \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\NullsafeMethodCall $node
     */
    private function isMethodCall(Node $node): bool
    {
        return $node instanceof MethodCall || $node instanceof NullsafeMethodCall;
    }

    private function isThis(Expr $expression): bool
    {
        return $expression instanceof Variable && $expression->name === 'this';
    }

}
