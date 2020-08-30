<?php declare(strict_types=1);

namespace Xynha\Tests\Units;

use Xynha\Container\ContainerException;
use Xynha\Container\DiContainer;
use Xynha\Container\DiRuleList;
use Xynha\Tests\Data\NoConstructor;
use Xynha\Tests\Data\ObjectAllowsNull;
use Xynha\Tests\Data\ObjectDefaultValue;
use Xynha\Tests\Data\ObjectDependencies;
use Xynha\Tests\Data\ScalarAllowsNull;
use Xynha\Tests\Data\ScalarRequired;
use Xynha\Tests\Units\Config\AbstractConfigTest;

final class ConstructParamsTest extends AbstractConfigTest
{

    public function testScalarRequired()
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Missing required argument $%s passed to %s::__construct()',
                'bool',
                ScalarRequired::class
            )
        );

        $dic = new DiContainer(new DiRuleList);
        $dic->get(ScalarRequired::class);
    }

    public function testScalarType()
    {
        $obj = $this->dic->get(ScalarRequired::class);

        $this->assertInstanceOf(ScalarRequired::class, $obj);
        $this->assertSame(true, $obj->bool);
        $this->assertSame('String value', $obj->string);
        $this->assertSame(2020, $obj->int);
        $this->assertSame(0.1, $obj->float);
        $this->assertSame([], $obj->emptyArray);
        $this->assertSame([false, true], $obj->boolArray);
        $this->assertSame(['string', 'value'], $obj->stringArray);
        $this->assertSame([2019, 2020], $obj->intArray);
        $this->assertSame([0.1, 0.2], $obj->floatArray);
    }

    public function testPassInvalidScalarType()
    {
        $type = 'bool or null';
        if (PHP_VERSION_ID >= 70100 && PHP_VERSION_ID < 70300) {
            $type = 'boolean or null';
        }

        if (PHP_MAJOR_VERSION >= 8) {
            $type = '?bool';
        }

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Argument 1 passed to %s::__construct() must be of the type %s, array given',
                ScalarAllowsNull::class,
                $type
            )
        );

        $rules['constructParams'] = [[]];

        $rlist = new DiRuleList();
        $rlist = $rlist->addRule(ScalarAllowsNull::class, $rules);
        $dic = new DiContainer($rlist);
        $dic->get(ScalarAllowsNull::class);
    }

    public function testInvalidObjectValueArray()
    {
        $rules['constructParams'] = [['invalid type']];

        $rlist = new DiRuleList();
        $rlist = $rlist->addRule(ObjectDefaultValue::class, $rules);
        $dic = new DiContainer($rlist);
        $obj = $dic->get(ObjectDefaultValue::class);

        $this->assertNull($obj->obj);
    }

    public function testInvalidObjectValueScalar()
    {
        $rules['constructParams'] = ['invalid type'];

        $rlist = new DiRuleList();
        $rlist = $rlist->addRule(ObjectDefaultValue::class, $rules);
        $dic = new DiContainer($rlist);
        $obj = $dic->get(ObjectDefaultValue::class);

        $this->assertNull($obj->obj);
    }

    public function testPassOptionalObject()
    {
        $rules['constructParams'] = [new ObjectAllowsNull(null)];

        $rlist = new DiRuleList();
        $rlist = $rlist->addRule(ObjectDefaultValue::class, $rules);
        $dic = new DiContainer($rlist);
        $obj = $dic->get(ObjectDefaultValue::class);

        $this->assertInstanceOf(ObjectAllowsNull::class, $obj->obj);
    }

    public function testPassObject()
    {
        $obj = $this->dic->get(ObjectDependencies::class);
        $this->assertInstanceOf(ObjectDependencies::class, $obj);
        $this->assertInstanceOf(ScalarAllowsNull::class, $obj->scalar);
        $this->assertInstanceOf(ObjectAllowsNull::class, $obj->obj);
        $this->assertNull($obj->obj->std);

        $null = new ObjectAllowsNull(new NoConstructor);
        $rule['constructParams'] = [$null];

        $rlist = $this->rlist->addRule(ObjectDependencies::class, $rule);
        $dic = new DiContainer($rlist);

        $obj = $dic->get(ObjectDependencies::class);
        $this->assertSame($null, $obj->obj);
    }
}
