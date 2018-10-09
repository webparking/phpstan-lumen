<?php declare(strict_types = 1);

namespace Weebly\PHPStan\Laravel;

use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class HelpersReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    /**
     * @var string[]
     */
    private $helpers = [
        'app',
        'redirect',
        'response',
        'validator',
        'view',
    ];

    /**
     * @inheritdoc
     */
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return in_array($functionReflection->getName(), $this->helpers);
    }

    /**
     * @inheritdoc
     */
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        switch ($functionReflection->getName()) {
            case 'app':
                if (empty($functionCall->args) || $scope->getType($functionCall->args[0]->value) instanceof NullType) {
                    return new ObjectType(
                        $this->getApplicationClass()
                    );
                }

                $arg1 = $functionCall->args[0];

                if ($arg1->value instanceof ClassConstFetch) {
                    return new ObjectType((string) $functionCall->args[0]->value->class);
                }

                if ($arg1->value instanceof String_ && class_exists($functionCall->args[0]->value->value)) {
                    return new ObjectType($functionCall->args[0]->value->value);
                }

                return new MixedType();
            case 'redirect':
                if (empty($functionCall->args)) {
                    return new ObjectType(
                        $this->getRedirectorClass()
                    );
                }

                return new ObjectType(\Illuminate\Http\RedirectResponse::class);
            case 'response':
                if (empty($functionCall->args)) {
                    return new ObjectType(\Illuminate\Contracts\Routing\ResponseFactory::class);
                }

                return new ObjectType(\Illuminate\Http\Response::class);
            case 'validator':
                if (empty($functionCall->args)) {
                    return new ObjectType(\Illuminate\Contracts\Validation\Factory::class);
                }

                return new ObjectType(\Illuminate\Contracts\Validation\Validator::class);

            case 'view':
                if (empty($functionCall->args)) {
                    return new ObjectType(\Illuminate\Contracts\View\Factory::class);
                }

                return new ObjectType(\Illuminate\View\View::class);
        }

        return new MixedType();
    }

    private function getApplicationClass(): string
    {
        if (class_exists('\Illuminate\Foundation\Application')) {
            return '\Illuminate\Foundation\Application';
        }

        if (class_exists('\Illuminate\Foundation\Application')) {
            return '\Illuminate\Foundation\Application';
        }
    }

    private function getRedirectorClass(): string
    {
        if (class_exists('\Illuminate\Routing\Redirector')) {
            return '\Illuminate\Routing\Redirector';
        }

        if (class_exists('\Laravel\Lumen\Http\Redirector')) {
            return '\Laravel\Lumen\Http\Redirector';
        }
    }
}
