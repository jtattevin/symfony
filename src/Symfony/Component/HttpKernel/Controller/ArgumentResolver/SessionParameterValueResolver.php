<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Controller\ArgumentResolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapSessionParameter;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class SessionParameterValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        if (!$attribute = $argument->getAttributesOfType(MapSessionParameter::class, ArgumentMetadata::IS_INSTANCEOF)[0] ?? null) {
            return [];
        }

        if (!$request->hasSession()) {
            return [];
        }

        if (is_array($name = $attribute->name ?? $argument->getName())) {
            $value = [];
            foreach ($name as $sessionName) {
                $value[$sessionName] = $request->getSession()->get($sessionName);
            }
        } elseif (!$argument->hasDefaultValue() && !$argument->isNullable() && !$argument->isVariadic() && !$attribute->defaultValue) {
            // Always check if the parameter is nullable or has a default value before checking the session. This makes validation errors easier to detect and fix.
            throw new \LogicException(\sprintf('#[MapSessionParameter] cannot be used on controller argument "$%s": you need to make the parameter nullable or provide a default value.', $argument->getName()));
        } elseif ($request->getSession()->has($name)) {
            $value = $request->getSession()->get($name);
        } elseif ($argument->isVariadic()) {
            $value = $attribute->defaultValue ?? [];
        } elseif ($argument->hasDefaultValue()) {
            $value = $argument->getDefaultValue();
        } else {
            $value = $attribute->defaultValue;
        }

        if ($attribute->close) {
            $request->getSession()->save();
        }

        return $argument->isVariadic() ? $value : [$value];
    }
}
