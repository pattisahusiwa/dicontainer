<?php declare(strict_types=1);

use Xynha\Container\ContainerException;
use Xynha\Container\DiContainer;
use Xynha\Container\DiRuleList;
use Xynha\Container\NotFoundException;
use Xynha\Tests\Data\DefaultNullInterface;
use Xynha\Tests\Data\GenericInterfaceDep;
use Xynha\Tests\Data\GenericInterfaceImpl;
use Xynha\Tests\Data\NullableInterface;
use Xynha\Tests\Data\OverriddenGlobalSubsImpl;
use Xynha\Tests\Data\SubsInterface;
use Xynha\Tests\Data\SubsInterfaceDep;
use Xynha\Tests\Data\SubsInterfaceImpl;
use Xynha\Tests\Units\Config\AbstractConfigTest;

final class InterfaceTest extends AbstractConfigTest
{

    public function testMissingInterface()
    {
        $msg = sprintf('Class or rule %s does not exist', ArrayAccess::class);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($msg);

        $this->dic->get(ArrayAccess::class);
    }

    public function testInvalidSubstitution()
    {
        $this->expectException(ContainerException::class);

        $rule['substitutions'] = [SubsInterface::class => GenericInterfaceImpl::class];
        $rlist = new DiRuleList();
        $rlist = $rlist->addRule(SubsInterfaceDep::class, $rule);
        $dic = new DiContainer($rlist);

        $dic->get(SubsInterfaceDep::class);
    }

    public function testPassInvalidInstance()
    {
        $this->expectException(NotFoundException::class);

        $rule['constructParams'] = [new GenericInterfaceImpl];
        $rlist = new DiRuleList();
        $rlist = $rlist->addRule(SubsInterfaceDep::class, $rule);
        $dic = new DiContainer($rlist);

        $dic->get(SubsInterfaceDep::class);
    }

    public function testOverriddenGlobalSubs()
    {
        $obj = $this->dic->get(SubsInterfaceDep::class);
        $this->assertInstanceOf(OverriddenGlobalSubsImpl::class, $obj->obj);
        $this->assertSame('passed value from config', $obj->obj->arg);
    }

    public function testGenericInterfaceRule()
    {
        $obj = $this->dic->get(GenericInterfaceDep::class);
        $this->assertInstanceOf(GenericInterfaceImpl::class, $obj->obj);
    }

    public function testOverrideInstance()
    {
        $obj = $this->dic->get(SubsInterfaceDep::class);
        $this->assertInstanceOf(OverriddenGlobalSubsImpl::class, $obj->obj);
        $this->assertSame('passed value from config', $obj->obj->arg);

        $rule['constructParams'] = [new SubsInterfaceImpl('Overridden value')];

        $rlist = $this->rlist->addRule(SubsInterfaceDep::class, $rule);
        $dic = new DiContainer($rlist);

        $obj = $dic->get(SubsInterfaceDep::class);
        $this->assertInstanceOf(SubsInterfaceImpl::class, $obj->obj);
        $this->assertSame('Overridden value', $obj->obj->arg);
    }

    public function testOverrideClassWithMock()
    {
        if (PHP_MAJOR_VERSION >= 8) {
            $this->markTestSkipped('This test fails on PHP 8');
            return;
        }

        $mock = $this->createMock(SubsInterfaceImpl::class);
        $rule['constructParams'] = [$mock];

        $rlist = $this->rlist->addRule(SubsInterfaceDep::class, $rule);
        $dic = new DiContainer($rlist);

        $obj = $dic->get(SubsInterfaceDep::class);
        $this->assertInstanceOf(SubsInterfaceImpl::class, $obj->obj);
        $this->assertNull($obj->obj->arg);
    }

    public function testOverrideInterfaceWithMock()
    {
        if (PHP_MAJOR_VERSION >= 8) {
            $this->markTestSkipped('This test fails on PHP 8');
            return;
        }

        $mock = $this->createMock(SubsInterface::class);
        $rule['constructParams'] = [$mock];

        $rlist = $this->rlist->addRule(SubsInterfaceDep::class, $rule);
        $dic = new DiContainer($rlist);

        $obj = $dic->get(SubsInterfaceDep::class);
        $this->assertInstanceOf(SubsInterface::class, $obj->obj);
    }

    public function testDefaultValue()
    {
        $dic = new DiContainer(new DiRuleList);
        $obj = $dic->get(DefaultNullInterface::class);

        $this->assertInstanceOf(DefaultNullInterface::class, $obj);
    }

    public function testAllowsNull()
    {
        $dic = new DiContainer(new DiRuleList);
        $obj = $dic->get(NullableInterface::class);

        $this->assertInstanceOf(NullableInterface::class, $obj);
    }
}
