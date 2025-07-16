<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Tests\Controller\ArgumentResolver;

use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Attribute\MapSessionParameter;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\SessionParameterValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class SessionParameterValueResolverTest extends TestCase
{
    private ValueResolverInterface $resolver;

    private Request $request;

    protected function setUp(): void
    {
        $this->resolver = new SessionParameterValueResolver();

        $session = new Session(new MockArraySessionStorage());
        $session->set("locale", "en");
        $session->set("last_username", "jtattevin");
        $session->set("preferred_languages", ["en", "fr"]);
        $this->request = Request::create('/');
        $this->request->setSession($session);
    }

    public function testSkipWhenNoSession()
    {
        $metadata = new ArgumentMetadata('locale', 'string', false, true, false);

        $this->assertSame([], $this->resolver->resolve(Request::create('/'), $metadata));
    }

    /**
     * @dataProvider validDataProvider
     */

    public function testResolvingSuccessfully(ArgumentMetadata $metadata, array $expectedValues)
    {
        $this->assertSame($expectedValues, $this->resolver->resolve($this->request, $metadata));
    }

    public static function validDataProvider(): iterable
    {
        yield "Skip when not attribute" => [
            "metadata"       => new ArgumentMetadata('locale', 'string', false, true, false),
            "expectedValues" => [],
        ];

        yield "Basic usage" => [
            "metadata"       => new ArgumentMetadata('locale', 'string', false, true, "", attributes: [
                new MapSessionParameter(),
            ]),
            "expectedValues" => ["en"],
        ];

        yield "Basic usage, not set, with default value" => [
            "metadata"       => new ArgumentMetadata('not_defined', 'string', false, true, "", attributes: [
                new MapSessionParameter(),
            ]),
            "expectedValues" => [""],
        ];

        yield "Basic usage, not set, nullable" => [
            "metadata"       => new ArgumentMetadata('not_defined', 'string', false, false, "", isNullable: true, attributes: [
                new MapSessionParameter(),
            ]),
            "expectedValues" => [null],
        ];

        yield "Basic usage, not set, not null but default value provided" => [
            "metadata"       => new ArgumentMetadata('not_defined', 'string', false, false, null, attributes: [
                new MapSessionParameter(defaultValue: "default_value"),
            ]),
            "expectedValues" => ["default_value"],
        ];

        yield "Union type (reading type 1)" => [
            "metadata"       => new ArgumentMetadata('locale', 'string|array', false, true, "", attributes: [
                new MapSessionParameter("locale"),
            ]),
            "expectedValues" => ["en"],
        ];

        yield "Union type (reading type 2)" => [
            "metadata"       => new ArgumentMetadata('locale', 'string|array', false, true, "", attributes: [
                new MapSessionParameter("preferred_languages"),
            ]),
            "expectedValues" => [["en", "fr"]],
        ];

        yield "Using another name not defined" => [
            "metadata"       => new ArgumentMetadata('locale', 'string', false, true, "", attributes: [
                new MapSessionParameter("not_defined"),
            ]),
            "expectedValues" => [""],
        ];

        yield "Using another name defined" => [
            "metadata"       => new ArgumentMetadata('not_defined', 'string', false, true, "", attributes: [
                new MapSessionParameter("locale"),
            ]),
            "expectedValues" => ["en"],
        ];

        yield "Variadic usage" => [
            "metadata"       => new ArgumentMetadata('preferred_languages', 'string', true, false, null, attributes: [
                new MapSessionParameter(),
            ]),
            "expectedValues" => ["en", "fr"],
        ];

        yield "Variadic usage not defined" => [
            "metadata"       => new ArgumentMetadata('preferredLanguages', 'string', true, false, null, attributes: [
                new MapSessionParameter(),
            ]),
            "expectedValues" => [],
        ];

        yield "Variadic usage not defined but default value provided" => [
            "metadata"       => new ArgumentMetadata('preferredLanguages', 'string', true, false, null, attributes: [
                new MapSessionParameter(defaultValue: ["fr", "en"]),
            ]),
            "expectedValues" => ["fr", "en"],
        ];

        yield "Reading one array in session" => [
            "metadata"       => new ArgumentMetadata('preferredLanguages', 'array', false, true, [], attributes: [
                new MapSessionParameter("preferred_languages"),
            ]),
            "expectedValues" => [["en", "fr"]],
        ];

        yield "Reading multiple key in session" => [
            "metadata"       => new ArgumentMetadata('sessionData', 'array', false, true, [], attributes: [
                new MapSessionParameter(["preferred_languages", "last_username", "locale", "not_defined"]),
            ]),
            "expectedValues" => [["preferred_languages" => ["en", "fr"], "last_username" => "jtattevin", "locale" => "en", "not_defined" => null]],
        ];
    }

    public function testRequireDefaultValue()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('#[MapSessionParameter] cannot be used on controller argument "$locale": you need to make the parameter nullable or provide a default value.');
        $metadata = new ArgumentMetadata('locale', 'string', false, false, false, attributes: [new MapSessionParameter()]);
        $this->resolver->resolve($this->request, $metadata);
    }

    public function testNotClosingByDefault()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->never())->method("save");
        $this->request->setSession($session);

        $metadata = new ArgumentMetadata('locale', 'string', false, true, "", attributes: [new MapSessionParameter()]);
        $this->resolver->resolve($this->request, $metadata);

        $metadata = new ArgumentMetadata('locale', 'string', false, true, "", attributes: [new MapSessionParameter(close: false)]);
        $this->resolver->resolve($this->request, $metadata);
    }

    public function testClosingIfRequested()
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method("save");
        $this->request->setSession($session);

        $metadata = new ArgumentMetadata('locale', 'string', false, true, "", attributes: [new MapSessionParameter(close: true)]);
        $this->resolver->resolve($this->request, $metadata);
    }
}
