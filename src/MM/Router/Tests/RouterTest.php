<?php declare(strict_types=1);

namespace MM\Router\Tests;

use ArrayObject;
use MM\Router\Route;
use MM\Router\Router;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-router
 */
final class RouterTest extends TestCase
{
	public function testSanityCheck()
	{
		$log = new ArrayObject(); // we need reference
		$router = new Router([
			'/' => function () use ($log) {
				$log[] = 'index';
				// just to simplify testing, technically no need to return anything
				return true;
			},
		]);

		//
		$this->assertTrue($router->current()['route'] === null);
		$this->assertTrue($router->current()['params'] === null);

		// match returns truthy (depends on the callback)
		$this->assertTrue($router->exec('/'));

		//
		$this->assertTrue($router->current()['route'] === '/');
		$this->assertTrue($router->current()['params'] !== null);

		// returns falsey on no match (but only if no fallback or catch all provided)
		$this->assertFalse($router->exec('/wrong/path'));

		//
		$this->assertTrue($router->current()['route'] === null);
		$this->assertTrue($router->current()['params'] === null);

		//
		$this->assertEquals('index', join(',', $log->getArrayCopy()));
	}

	public function testFirstMatchWins()
	{
		$log = new ArrayObject();
		$router = new Router();

		// must win
		$router->on(['/', '/index.html'], function () use ($log) {
			$log[] = 'index';
		});
		$router->on('/', function () use ($log) {
			$log[] = 'foo';
		});

		//
		$router->exec('/');
		$this->assertEquals('index', join(',', $log->getArrayCopy()));
	}

	public function testExecFallback()
	{
		$log = new ArrayObject();
		$router = new Router([
			'/' => function () use ($log) {
				$log[] = 'index';
			},
		]);

		$router->exec('/foo', function () use ($log) {
			$log[] = 'fallback';
		});

		//
		$this->assertEquals('fallback', join(',', $log->getArrayCopy()));
	}

    public function testCatchAllFallback()
    {
        $log = new ArrayObject();
		$router = new Router([
			'/' => function () use ($log) {
				$log[] = 'index';
			},
            '*' => function () use ($log) {
                $log[] = '404';
                return true;
            }
		]);

        // truthy even on no match (because catch all returns truthy)
	    $this->assertTrue($router->exec('/foo'));

        $this->assertEquals('*', $router->current()['route']);

        $router->exec('/');

        $this->assertEquals('404,index', join(',', $log->getArrayCopy()));
    }

    public function testExecReturnsArbitraryValue()
    {
        $index = new ArrayObject(['page' => 'index']);
        $notFound = new ArrayObject(['page' => 'not-found']);

        $router = new Router([
			'/' => function () use ($index) {
				return $index;
			},
            '*' => function () use ($notFound) {
                return $notFound;
            }
		]);

        $this->assertEquals($index, $router->exec('/'));
        $this->assertEquals($notFound, $router->exec('/foo'));
    }

    public function testIntegration()
    {
        $log = new ArrayObject();
        $log2 = new ArrayObject();

        $router = new Router([
			'/' => function () use ($log) {
				$log[] = 'index';
			},
            '*' => function () use ($log) {
                $log[] = '404';
                return true;
            }
		]);

        $router->subscribe(function ($v) use ($log2) {
            $log2[] = $v['route'];
        });

        // or via "on" api
        $route = '/[bar]/[id([\\d]+)]/baz';
        $router->on($route, function ($params) use ($log) {
            // log.push(`${bar}:${id}`));
            $log[] = $params['bar'] . ':' . $params['id'];
        });

        // custom fallback
        $router->exec('hey', function () use ($log) {$log[] = 'ho';});
	    $this->assertEquals(null, $router->current()['route']);

        // 404
        $router->exec('id/non-digits/baz');
        $this->assertEquals('*', $router->current()['route']);
        $this->assertEquals(null, $router->current()['params']);

        // id:123
        $router->exec('id/123/baz');
        $this->assertEquals($route, $router->current()['route']);
        $this->assertEquals('id', $router->current()['params']['bar']);
        $this->assertEquals('123', $router->current()['params']['id']);

        // index
        $router->exec('/');
        $this->assertEquals('/', $router->current()['route']);

        $this->assertEquals('ho,404,id:123,index', join(',', $log->getArrayCopy()));

        $this->assertEquals(
            join(',', [null, null, '*', $route, '/']),
            join(',', $log2->getArrayCopy())
        );
    }

    public function testUnsubscribeWorks()
    {
        $log = new ArrayObject(); // we need reference
		$router = new Router([
			'/' => function () { return true; },
		]);

        $unsubscribe = $router->subscribe(function ($v) use ($log) {
            $log[] = $v['route'];
        });

        $router->exec('/');
        $logged = join(',', $log->getArrayCopy());
        $this->assertEquals(',/', $logged);
        
        $unsubscribe();
        
        // log must not be changed
        $router->exec('/');
        $this->assertEquals(join(',', $log->getArrayCopy()), $logged);
    }

    public function testLabel()
    {
        $router = new Router();

        $router->on('/foo', function () {}, ['label' => 'foo']);
        $router->on('/bar', function () {}, ['label' => 'bar']);

        $router->exec('/foo');
        $this->assertEquals('foo', $router->current()['label']);

        $router->exec('/bar');
        $this->assertEquals('bar', $router->current()['label']);
    }
}
