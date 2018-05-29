<?php

namespace Tests\Platform\Domains\Routing;

use SuperV\Platform\Domains\Routing\RouteRegistrar;
use Tests\Platform\TestCase;

class RouteLoaderTest extends TestCase
{
    /** @test */
    function loads_routes_from_array()
    {
        app(RouteRegistrar::class)
            ->register([
                'web/foo'       => 'WebController@foo',
                'web/bar'       => [
                    'uses' => 'WebController@bar',
                    'as'   => 'web.bar',
                ],
                'post@web/foo'  => 'WebController@postFoo',
                'patch@web/bar' => function () { },
            ]);

        $getRoutes = $this->router()->getRoutes()->get('GET');

        $this->assertEquals('WebController@foo', $getRoutes['web/foo']->getAction('controller'));
        $this->assertEquals('WebController@bar', $getRoutes['web/bar']->getAction('controller'));
        $this->assertEquals('web.bar', $getRoutes['web/bar']->getName());

        $postRoutes = $this->router()->getRoutes()->get('POST');
        $this->assertEquals('WebController@postFoo', $postRoutes['web/foo']->getAction('controller'));

        $patchRoutes = $this->router()->getRoutes()->get('PATCH');
        $this->assertInstanceOf(\Closure::class, $patchRoutes['web/bar']->getAction('uses'));
    }

    /** @test */
    function loads_routes_for_a_port()
    {
        $this->setUpPorts();

        $loader = $this->app->make(RouteRegistrar::class);
        $loader->setPort('web')->register(['web/foo' => 'WebController@foo']);
        $loader->setPort('acp')->register(['acp/foo' => 'AcpController@foo']);
        $loader->setPort('api')->register(['api/foo' => 'ApiController@foo']);

        $getRoutes = $this->router()->getRoutes()->get('GET');

        $webRoute = $getRoutes['superv.ioweb/foo'];
        $this->assertEquals('web', $webRoute->getAction('port'));
        $this->assertEquals('superv.io', $webRoute->getDomain());
        $this->assertNull($webRoute->getPrefix());

        $acpRoute = $getRoutes['superv.ioacp/acp/foo'];
        $this->assertEquals('acp', $acpRoute->getAction('port'));
        $this->assertEquals('superv.io', $acpRoute->getDomain());
        $this->assertEquals('acp', $acpRoute->getPrefix());

        $apiRoute = $getRoutes['api.superv.ioapi/foo'];
        $this->assertEquals('api', $apiRoute->getAction('port'));
        $this->assertEquals('api.superv.io', $apiRoute->getDomain());
        $this->assertNull($apiRoute->getPrefix());
    }

    /** @test */
    function registers_ports_middlewares()
    {
        config([
            'superv.ports' => [
                'web' => [
                    'hostname'    => 'localhost',
                    'middlewares' => ['a', 'b', 'c'],
                ],
            ],
        ]);

        $loader = $this->app->make(RouteRegistrar::class)->setPort('web');
        $route = $loader->registerRoute('foo', 'WebController@foo');

        $this->assertEquals(['a', 'b', 'c'], $route->getAction('middleware'));
    }

    /**
     * @return \Illuminate\Routing\Router
     */
    protected function router()
    {
        return $this->app['router'];
    }
}