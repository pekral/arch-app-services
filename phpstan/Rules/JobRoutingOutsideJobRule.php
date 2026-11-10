<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\PendingDispatch;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Enforces that a queued job (a class implementing `ShouldQueue`) configures its own queue routing. A call to a
 * routing method (`onConnection`, `onQueue`, `onGroup` by default) is reported when the receiver is
 * a `PendingDispatch` or a job instance used outside the job's own class. Routing calls inside the job class,
 * the `$connection` / `$queue` properties and non-routing methods such as `delay()` stay allowed.
 *
 * A legitimate exception is silenced with PHPStan's standard `ignoreErrors` on the identifier
 * `arch.jobRoutingOutsideJob`, scoped to the file.
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\MethodCall>
 */
final readonly class JobRoutingOutsideJobRule implements Rule
{

    public const string IDENTIFIER = 'arch.jobRoutingOutsideJob';

    private const array DEFAULT_ROUTING_METHODS = ['onConnection', 'onQueue', 'onGroup'];

    /**
     * @var list<string>
     */
    private array $routingMethods;

    /**
     * @param list<string> $routingMethods method names that configure the queue routing of a job
     */
    public function __construct(array $routingMethods = self::DEFAULT_ROUTING_METHODS)
    {
        $this->routingMethods = array_map(strtolower(...), $routingMethods);
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param \PhpParser\Node\Expr\MethodCall $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier || !in_array($node->name->toLowerString(), $this->routingMethods, strict: true)) {
            return [];
        }

        $receiverType = $scope->getType($node->var);

        if (!$this->isPendingDispatch($receiverType) && !$this->isJobOutsideItsClass($receiverType, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Queue routing "%s()" must be configured inside the job class, not at the call site.',
                    $node->name->toString(),
                ),
            )
                ->identifier(self::IDENTIFIER)
                ->build(),
        ];
    }

    private function isPendingDispatch(Type $receiverType): bool
    {
        return new ObjectType(PendingDispatch::class)->isSuperTypeOf($receiverType)->yes();
    }

    private function isJobOutsideItsClass(Type $receiverType, Scope $scope): bool
    {
        if (!new ObjectType(ShouldQueue::class)->isSuperTypeOf($receiverType)->yes()) {
            return false;
        }

        $classReflection = $scope->getClassReflection();

        return !$classReflection instanceof ClassReflection
            || $receiverType->getObjectClassNames() !== [$classReflection->getName()];
    }

}
