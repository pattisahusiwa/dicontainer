<?php declare(strict_types=1);

use Xynha\Container\ContainerException;
use Xynha\Container\DiContainer;
use Xynha\Container\DiRuleList;
use Xynha\Tests\Data\FactoryInterface;
use Xynha\Tests\Data\FactoryInterfaceDep;
use Xynha\Tests\Data\FactoryInterfaceImpl;
use Xynha\Tests\Data\GeneratorDep;
use Xynha\Tests\Data\MapFactory;
use Xynha\Tests\Data\MapperDep;
use Xynha\Tests\Units\Config\AbstractConfigTestCase;

final class GetFromTest extends AbstractConfigTestCase
{

    public function testGetFromIsNotCallable()
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('getFrom rule is not a callable');

        $rule['getFrom'] = ['callback'];

        $rlist = new DiRuleList();
        $rlist = $rlist->addRule(FactoryInterface::class, $rule);
        $dic = new DiContainer($rlist);

        $dic->get(FactoryInterfaceDep::class);
    }

    public function testGetInterfaceFromFactory()
    {
        $obj = $this->dic->get(FactoryInterfaceDep::class);

        $this->assertInstanceOf(FactoryInterfaceImpl::class, $obj->obj);
        $this->assertStringStartsWith('factory_', $obj->obj->generated);
        $this->assertSame('passed value from config', $obj->obj->passed);
    }

    public function testGetClassFromFactory()
    {
        $mapper = $this->dic->get(MapperDep::class);

        $this->assertInstanceOf(MapperDep::class, $mapper);
    }

    public function testSharedFactoryClass()
    {
        $mapper = $this->dic->get(MapperDep::class);
        $generator = $this->dic->get(GeneratorDep::class);

        $this->assertSame($mapper->mapper->map, $generator->gen->map);

        $factory = $this->dic->get(MapFactory::class);
        $this->assertSame($mapper->mapper, $factory->getMapper());
        $this->assertSame($generator->gen, $factory->getGenerator());
    }

    public function testSharedFactoryInterface()
    {
        $objA = $this->dic->get(FactoryInterfaceDep::class);
        $objB = $this->dic->get(FactoryInterfaceDep::class);

        $this->assertSame($objA->obj, $objB->obj);
    }

    public function testOverrideGetFrom()
    {
        $closure = function (string $value) : FactoryInterface {
            $factory = new FactoryInterfaceImpl('From closure');
            $factory->setValue($value);
            return $factory;
        };
        $rule['getFrom'] = [$closure, 'Override test'];

        $rlist = $this->rlist->addRule(FactoryInterface::class, $rule);
        $dic = new DiContainer($rlist);

        $obj = $dic->get(FactoryInterfaceDep::class);

        $this->assertSame('From closure', $obj->obj->generated);
        $this->assertSame('Override test', $obj->obj->passed);
    }
}
