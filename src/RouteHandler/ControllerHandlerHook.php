<?php
/**
 * @since  11.08.2015
 * @author Ed Posinitskiy <eddiespb@gmail.com>
 */

namespace RouteHandler;

use InvalidArgumentException;
use Slim\Slim;
use Symfony\Component\Yaml\Parser;

/**
 * Class Handler
 *
 * @author Ed Posinitskiy <eddiespb@gmail.com>
 */
class ControllerHandlerHook
{

    /**
     * @var Slim
     */
    protected $app;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $methods
        = [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS'
        ];

    /**
     * @param Slim         $app
     * @param string|array $config
     */
    public function __construct(Slim $app, $config)
    {
        $this->app = $app;

        if (is_string($config)) {
            if (!is_readable($config)) {
                throw new InvalidArgumentException(
                    sprintf('Filename [%s] is not readable', $config)
                );
            }

            $config = $this->getParser()->parse(file_get_contents($config));

        }

        if (null === $config) {
            $config = [];
        }

        if (!is_array($config)) {
            throw new InvalidArgumentException('Config must be an array of routes or path to yaml routes config file');
        }

        $this->config = $config;
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        if (!$this->parser) {
            $this->setParser(new Parser());
        }

        return $this->parser;
    }

    /**
     * @param Parser $parser
     *
     * @return self
     */
    public function setParser(Parser $parser)
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * Invokes on slim.before.router or slim.before hook
     */
    public function __invoke()
    {
        foreach ($this->config as $routeName => $params) {
            if (!isset($params['route']) || !is_string($params['route'])) {
                throw new InvalidArgumentException(
                    sprintf('`route` param must be defined under [%s] route', $routeName)
                );
            }

            $route      = $params['route'];
            $controller = $this->defineController($params, $routeName);
            $action     = $this->defineAction($params, $controller, $routeName);
            $methods    = $this->defineMethods($params);

            array_walk(
                $methods,
                function (&$el) use ($routeName) {
                    $el = $this->guardForMethod($el, $routeName);
                }
            );

            $slimRoute = $this->app->map(
                $route,
                function () use ($controller, $action) {
                    call_user_func_array([$controller, $action], func_get_args());
                }
            );

            call_user_func_array([$slimRoute, 'via'], $methods);

            if (is_string($routeName)) {
                $slimRoute->name($routeName);
            }
        }
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function defineMethods(array $params)
    {
        $method = isset($params['method']) ? $params['method'] : 'get';
        if (false !== strpos($method, ',')) {
            $method = explode(',', $method);
        }

        return (array)$method;
    }

    /**
     * @param array      $params
     * @param string|int $routeName
     *
     * @return mixed
     */
    protected function defineController(array $params, $routeName)
    {
        if (!isset($params['controller'])) {
            throw new InvalidArgumentException(
                sprintf('`controller` param is not defined under [%s] route', $routeName)
            );
        }

        $controllerClass = $params['controller'];

        if (!class_exists($controllerClass)) {
            throw new InvalidArgumentException(
                sprintf('Class [%s] does not exist', $controllerClass)
            );
        }

        return new $controllerClass($this->app);
    }

    /**
     * @param array      $params
     * @param object     $controller
     * @param string|int $routeName
     *
     * @return mixed
     */
    protected function defineAction(array $params, $controller, $routeName)
    {
        if (!isset($params['action'])) {
            throw new InvalidArgumentException(
                sprintf('`action` param is not defined under [%s] route', $routeName)
            );
        }

        $action = $params['action'];

        if (!method_exists($controller, $action)) {
            throw new InvalidArgumentException(
                sprintf('method [%s] does not exist in class [%s]', $action, get_class($controller))
            );
        }

        return $action;
    }

    /**
     * @param string     $method
     * @param string|int $route
     *
     * @return string
     */
    protected function guardForMethod($method, $route)
    {
        $method = strtoupper($method);

        if (!in_array($method, $this->methods)) {
            throw new InvalidArgumentException(
                sprintf('method [%s] is not allowed, defined in [%s]', $method, $route)
            );
        }

        return $method;
    }

}
