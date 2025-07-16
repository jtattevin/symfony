<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Attribute;

use Symfony\Component\HttpKernel\Controller\ArgumentResolver\SessionParameterValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;

/**
 * Can be used to pass a session parameter to a controller argument.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class MapSessionParameter extends ValueResolver
{
    /**
     * @param string|array|null                           $name     The name of the session parameter; if null, the name of the argument in the controller will be used; Provide an array to read multiple value
     * @param bool                                        $close    Specify if we should try to save and close the session after reading
     * @param class-string<ValueResolverInterface>|string $resolver The name of the resolver to use
     */
    public function __construct(
        public string|array|null $name = null,
        public bool $close = false,
        public mixed $defaultValue = null,
        string $resolver = SessionParameterValueResolver::class,
    ) {
        parent::__construct($resolver);
    }
}
