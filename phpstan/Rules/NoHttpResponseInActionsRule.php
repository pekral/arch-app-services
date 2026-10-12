<?php

declare(strict_types = 1);

namespace Pekral\Arch\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Enforces that no method of an ArchAction returns an HTTP response. An Action returns data
 * (a DTO, a model, a scalar); the controller builds the HTTP response from it.
 *
 * Both the declared return type and the type of every returned expression are inspected. A broad
 * declaration (`mixed`, `object`, `\Stringable`) therefore cannot hide a returned response.
 *
 * Reported types: Symfony\Component\HttpFoundation\Response (and every subclass, including the
 * Illuminate JsonResponse and RedirectResponse), Illuminate\Contracts\Support\Responsable,
 * Illuminate\Contracts\View\View, Illuminate\Contracts\Routing\ResponseFactory and Inertia\Response.
 *
 * A spatie/laravel-data DTO is never reported, although it implements Responsable: returning a DTO
 * is the sanctioned Action result (see ActionReturnDtoRule).
 *
 * A legitimate exception is silenced with PHPStan's standard `ignoreErrors` on the identifier
 * `arch.noHttpResponseInAction`, scoped to the Action file.
 *
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\MethodReturnStatementsNode>
 */
final readonly class NoHttpResponseInActionsRule implements Rule
{

    public const string IDENTIFIER = 'arch.noHttpResponseInAction';

    private const string ARCH_ACTION_INTERFACE = 'Pekral\Arch\Action\ArchAction';

    private const string SPATIE_RESPONSABLE_DATA = 'Spatie\LaravelData\Contracts\ResponsableData';

    private const array HTTP_RESPONSE_TYPES = [
        'Symfony\Component\HttpFoundation\Response',
        'Illuminate\Contracts\Support\Responsable',
        'Illuminate\Contracts\View\View',
        'Illuminate\Contracts\Routing\ResponseFactory',
        'Inertia\Response',
    ];

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    /**
     * @param \PHPStan\Node\MethodReturnStatementsNode $node
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null || !$classReflection->implementsInterface(self::ARCH_ACTION_INTERFACE)) {
            return [];
        }

        foreach ($this->getReturnedClassNames($node) as $className) {
            if (!$this->isHttpResponse($className)) {
                continue;
            }

            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Action class "%s" method %s() must not return an HTTP response (%s).'
                        . ' Return the data and build the HTTP response in the controller.',
                        $classReflection->getName(),
                        $node->getMethodReflection()->getName(),
                        $className,
                    ),
                )->identifier(self::IDENTIFIER)->build(),
            ];
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function getReturnedClassNames(MethodReturnStatementsNode $node): array
    {
        $returnTypes = [$node->getMethodReflection()->getOnlyVariant()->getReturnType()];

        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnedExpression = $returnStatement->getReturnNode()->expr;

            if ($returnedExpression === null) {
                continue;
            }

            $returnTypes[] = $returnStatement->getScope()->getType($returnedExpression);
        }

        return array_values(array_unique(array_merge(...array_map(
            static fn (Type $type): array => TypeCombinator::removeNull($type)->getObjectClassNames(),
            $returnTypes,
        ))));
    }

    private function isHttpResponse(string $className): bool
    {
        $type = new ObjectType($className);

        if (new ObjectType(self::SPATIE_RESPONSABLE_DATA)->isSuperTypeOf($type)->yes()) {
            return false;
        }

        return array_any(
            self::HTTP_RESPONSE_TYPES,
            static fn (string $httpResponseType): bool => new ObjectType($httpResponseType)->isSuperTypeOf($type)->yes(),
        );
    }

}
