<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 02.01.17
 * Time: 13:32
 */

namespace Cundd\Rest\Router;


use Cundd\Rest\Exception\InvalidArgumentException;
use Cundd\Rest\Http\RestRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Route implements RouteInterface
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $method;

    /**
     * @var callable
     */
    private $callback;

    /**
     * Route constructor
     *
     * @param string   $pattern
     * @param string   $method
     * @param callable $callback
     */
    public function __construct($pattern, $method, callable $callback)
    {
        $this->assertString($pattern, 'pattern');
        $this->assertString($method, 'method');

        $this->pattern = $this->normalizePattern($pattern);
        $this->method = strtoupper($method);
        $this->callback = $callback;
        $this->parameters = ParameterType::extractParameterTypesFromPattern($this->pattern);
    }

    /**
     * Creates a new Route with the given pattern and callback for the method GET
     *
     * @param string   $pattern
     * @param callable $callback
     * @return static
     */
    public static function get($pattern, callable $callback)
    {
        return new static($pattern, 'GET', $callback);
    }

    /**
     * Creates a new Route with the given pattern and callback for the method POST
     *
     * @param string   $pattern
     * @param callable $callback
     * @return static
     */
    public static function post($pattern, callable $callback)
    {
        return new static($pattern, 'POST', $callback);
    }

    /**
     * Creates a new Route with the given pattern and callback for the method PUT
     *
     * @param string   $pattern
     * @param callable $callback
     * @return static
     */
    public static function put($pattern, callable $callback)
    {
        return new static($pattern, 'PUT', $callback);
    }

    /**
     * Creates a new Route with the given pattern and callback for the method DELETE
     *
     * @param string   $pattern
     * @param callable $callback
     * @return static
     */
    public static function delete($pattern, callable $callback)
    {
        return new static($pattern, 'DELETE', $callback);
    }

    /**
     * Creates a new Route with the given pattern and callback for the method GET
     *
     * @param string   $pattern
     * @param callable $callback
     * @return static
     */
    public static function routeWithPattern($pattern, callable $callback)
    {
        return new static($pattern, 'GET', $callback);
    }

    /**
     * Creates a new Route with the given pattern, method and callback
     *
     * @param string   $pattern
     * @param string   $method
     * @param callable $callback
     * @return static
     */
    public static function routeWithPatternAndMethod($pattern, $method, callable $callback)
    {
        return new static($pattern, $method, $callback);
    }

    /**
     * Returns the normalized path pattern
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Returns the request method for this route
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns the requested parameters
     *
     * @return string[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Process the route
     *
     * @param RestRequestInterface $request
     * @param array                $parameters
     * @return ResponseInterface
     */
    public function process(RestRequestInterface $request, ...$parameters)
    {
        $callback = $this->callback;

        return $callback($request, ...$parameters);
    }

    /**
     * The __invoke method is called when a script tries to call an object as a function.
     *
     * @param RestRequestInterface $request
     * @param array                $arguments
     * @return mixed
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.invoke
     */
    public function __invoke(RestRequestInterface $request, ...$arguments)
    {
        return $this->process($request, ...$arguments);
    }

    /**
     * Returns the priority of this route
     *
     * Deeper nested paths have a higher priority. Fixed paths have precedence over paths with parameter expressions.
     *
     * @return int
     */
    public function getPriority()
    {
        if (!$this->priority) {
            if ('' === $this->pattern) {
                $this->priority = 0;
            } else {
                $this->priority = 1 + 10 * substr_count($this->pattern, '/') - substr_count($this->pattern, '{');
            }
        }

        return $this->priority;
    }

    /**
     * @param mixed  $input
     * @param string $argumentName
     */
    private function assertString($input, $argumentName)
    {
        if (!is_string($input)) {
            throw InvalidArgumentException::buildException($input, 'string', $argumentName);
        }
    }

    /**
     * @param string $inputPattern
     * @return string
     */
    private function normalizePattern($inputPattern)
    {
        $pattern = trim($inputPattern, '/');
        $patternParts = explode('/', $pattern);
        $parameterTypes = ParameterType::extractParameterTypesFromPattern($pattern);

        foreach ($parameterTypes as $index => $type) {
            $patternParts[$index] = '{' . $type . '}';
        }

        return implode('/', $patternParts);
    }
}
