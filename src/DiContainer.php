<?php declare(strict_types=1);

namespace Xynha\Container;

use Error;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Throwable;

final class DiContainer implements ContainerInterface
{

    /** @var DiParser */
    private $parser;

    /** @var DiRuleList */
    private $list;

    /** @var object[] */
    private $instances;

    /** @var array<string,string> */
    private $curKeys = [];

    public function __construct(DiRuleList $list)
    {
        $this->list = $list;
        $this->parser = new DiParser([$this, 'get']);
    }

    public function has($id)
    {
        return $this->list->hasRule($id) || class_exists($id);
    }

    public function get($id)
    {
        if ($this->has($id) === false) {
            throw new NotFoundException(sprintf('Class or rule %s does not exist', $id));
        }

        $rule = $this->list->getRule($id);
        if ($rule->classname() === __CLASS__) {
            return clone $this;
        }

        if (empty($rule->getFrom())) {
            return $this->getInstance($rule);
        }

        list($fromClass, $fromMethod, $fromArg) = $rule->getFrom();
        $fromRule = $this->list->getRule($fromClass);
        $object = $this->getInstance($fromRule);
        $callback = [$object, $fromMethod];

        if (is_callable($callback)) {
            return call_user_func_array($callback, $fromArg);
        }

        throw new ContainerException('Rule getFrom is not callable');
    }

    /** @return object */
    private function getInstance(DiRule $rule)
    {
        if (isset($this->instances[$rule->key()])) {
            return $this->instances[$rule->key()];
        }

        if (array_key_exists($rule->key(), $this->curKeys) || in_array($rule->classname(), $this->curKeys)) {
            throw new ContainerException('Cyclic dependencies detected');
        }

        $this->curKeys[$rule->key()] = $rule->classname();

        try {
            $object = $this->createObject($rule);
            unset($this->curKeys[$rule->key()]);
        } catch (Throwable $exc) {
            unset($this->curKeys[$rule->key()]);
            throw $exc;
        }

        if ($rule->isShared()) {
            $this->instances[$rule->key()] = $object;
        }

        return $object;
    }

    /** @return object */
    private function createObject(DiRule $rule)
    {
        $ref = new ReflectionClass($rule->classname());
        if ($ref->isAbstract()) {
            throw new ContainerException('Cannot instantiate abstract class ' . $rule->classname());
        }

        $params = $this->parser->parse($ref->getConstructor(), $rule->params(), $rule->substitutions());
        return $this->getObject($ref, $params);
    }

    /**
     * @param ReflectionClass<Object> $ref
     * @param array<mixed> $params
     *
     * @return object
     */
    private function getObject(ReflectionClass $ref, array $params)
    {
        try {
            return $ref->newInstanceArgs($params);
        } catch (Error $exc) {
            throw new ContainerException($exc->getMessage(), 1, $exc);
        }
    }
}
