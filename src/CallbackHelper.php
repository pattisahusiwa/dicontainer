<?php declare(strict_types=1);

namespace Xynha\Container;

use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

final class CallbackHelper
{

    private $dic;

    public function __construct(ContainerInterface $dic)
    {
        $this->dic = $dic;
    }

    /** @param mixed $callback */
    public function toCallback($callback) : callable
    {
        try {
            $ref = $this->getReflection($callback);

            if ($ref === null) {
                return $callback;
            }

            if ($ref->isStatic() === false) {
                $classname = $ref->getDeclaringClass()->getName();
                $methodName = $ref->getName();
                $obj = $this->dic->get($classname);
                $callback = [$obj, $methodName];
            }

            return $callback;
        } catch (ReflectionException $exc) {
        }

        if (is_callable($callback)) {
            return $callback;
        }

        throw new ContainerException('getFrom rule is not a callable');
    }

    /** @param mixed $callback */
    private function getReflection($callback) : ?ReflectionMethod
    {
        switch (true) {
            case is_string($callback):
                return new ReflectionMethod($callback);
            case is_array($callback):
                return $this->fromArray($callback);
        }

        // $callback is an object
        return null;
    }

    // @phpstan-ignore-next-line
    private function fromArray(array $callback) : ?ReflectionMethod
    {
        $class = array_shift($callback);
        $method = array_shift($callback);

        if (is_object($class)) {
            return null;
        }

        return new ReflectionMethod($class, $method);
    }
}
