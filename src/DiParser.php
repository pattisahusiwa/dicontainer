<?php declare(strict_types=1);

namespace Xynha\Container;

use ReflectionMethod;
use ReflectionParameter;

final class DiParser
{

    /** @var callable */
    private $creator;

    public function __construct(callable $creator)
    {
        $this->creator = $creator;
    }

    /**
     * @param array<mixed> $passedValues
     * @param array<string,string> $subs
     *
     * @return array<mixed>
     */
    public function parse(?ReflectionMethod $method, array $passedValues, array $subs) : array
    {
        if ($method === null) {
            return [];
        }

        if ($method->isPrivate() || $method->isProtected()) {
            $name = $method->getDeclaringClass()->getName();
            $msg = 'Access to non-public method of class ' . $name;
            if ($method->isConstructor()) {
                $msg = 'Access to non-public constructor of class ' . $name;
            }
            throw new ContainerException($msg);
        }

        $tParams = [];
        $params = $method->getParameters();
        foreach ($params as $arg) {
            $varName = $arg->getName();

            if (is_object($arg->getClass()) && $arg->getClass()->isInterface()) {
                $classname = $arg->getClass()->getName();
                $tParams[$varName] = $this->interfaceValue($arg, $classname, $subs, $passedValues);
                continue;
            }

            if (is_object($arg->getClass())) {
                $classname = $arg->getClass()->getName();
                $tParams[$varName] = $this->classValue($arg, $classname, $passedValues);
                continue;
            }

            $tParams[$varName] = $this->scalarValue($arg, $passedValues);
        }

        return $tParams;
    }

    /**
     * @param array<string,string> $subs
     * @param array<mixed> $values
     *
     * @return void|null|object
     */
    private function interfaceValue(ReflectionParameter $param, string $name, array $subs, array &$values)
    {
        if ($obj = $this->getObjectValue($name, $values)) {
            return $obj;
        }

        if (array_key_exists($name, $subs)) {
            return call_user_func_array($this->creator, [$subs[$name]]);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        if ($param->isOptional()) {
            return;
        }

        return call_user_func_array($this->creator, [$name]);
    }

    /**
     * @param array<mixed> $values
     *
     * @return void|null|object
     */
    private function classValue(ReflectionParameter $param, string $className, array &$values)
    {
        if ($obj = $this->getObjectValue($className, $values)) {
            return $obj;
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        if ($param->isOptional()) {
            return;
        }

        return call_user_func_array($this->creator, [$className]);
    }

    /**
     * @param array<mixed> $values
     *
     * @return mixed
     */
    private function scalarValue(ReflectionParameter $param, array &$values)
    {
        // @todo: Validate scalar type
        if (array_key_exists(0, $values)) {
            return array_shift($values);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->allowsNull()) {
            return null;
        }

        if (!$param->isOptional()) {
            $msg = sprintf(
                'Missing required argument $%s passed to %s::%s()',
                $param->getName(),
                $param->getDeclaringClass()->getName(), // @phpstan-ignore-line
                $param->getDeclaringFunction()->getName()  // @phpstan-ignore-line
            );
            throw new ContainerException($msg);
        }
    }

    /**
     * @param array<mixed> $values
     *
     * @return object|null
     */
    private function getObjectValue(string $className, array &$values)
    {
        if (!isset($values[0])) {
            return null;
        }

        if (is_object($values[0]) && ($values[0] instanceof $className)) {
            return array_shift($values);
        }

        return null;
    }
}
