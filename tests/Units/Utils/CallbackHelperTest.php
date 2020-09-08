<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Xynha\Container\CallbackHelper;
use Xynha\Container\ContainerException;
use Xynha\Container\DiContainer;
use Xynha\Container\DiRuleList;

final class CallbackHelperTest extends TestCase
{

    /** @var CallbackHelper */
    private $callback;

    protected function setUp()
    {
        require_once DATA_DIR . '/utils/callback.php';

        $dic = new DiContainer(new DiRuleList);
        $this->callback = new CallbackHelper($dic);
    }

    public function testNotCallable()
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('getFrom rule is not a callable');
        $this->callback->toCallback('_NotExist_');
    }

    public function testFunctionCallback()
    {
        $callback = 'fnCallback';
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);

        $this->assertSame($expected, $retval);
    }

    public function testStaticClassString()
    {
        $callback = 'StaticClass::callback';
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);
        $this->assertSame(array_merge(['StaticClass'], $expected), $retval);
    }

    public function testStaticClassArray()
    {
        $callback = ['StaticClass', 'callback'];
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);
        $this->assertSame(array_merge(['StaticClass'], $expected), $retval);
    }

    public function testExtendStaticClass()
    {
        $callback = ['ExtendStaticClass', 'callback'];
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);
        $this->assertSame(array_merge(['ExtendStaticClass'], $expected), $retval);
    }

    public function testExtendStaticClassParent()
    {
        $callback = ['ExtendStaticClass', 'parent::callback'];
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);
        $this->assertSame(array_merge(['StaticClass'], $expected), $retval);
    }

    public function testInvokeClass()
    {
        $obj = new InvokeClass();
        $callback = [$obj, 'callback'];
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);
        $this->assertSame(array_merge(['InvokeClass'], $expected), $retval);
    }

    public function testInvokeObject()
    {
        $obj = new InvokeClass();
        $callback = $obj;
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);
        $this->assertSame(array_merge(['InvokeClass::__invoke'], $expected), $retval);
    }

    public function testClosureCallback()
    {
        $closure = function (string $string, array $array) : array {
            return ['Closure', $string, $array];
        };

        $callback = $closure;
        $norm = $this->callback->toCallback($callback);

        $this->assertSame($callback, $norm);

        $expected = ['String value', ['array', 'value']];
        $retval = call_user_func_array($norm, $expected);
        $this->assertSame(array_merge(['Closure'], $expected), $retval);
    }

    public function testClassMethod()
    {
        $callback = ['InvokeClass', 'callback'];
        /** @var array{object,string} $norm */
        $norm = $this->callback->toCallback($callback);

        $this->assertNotSame($callback, $norm);
        $this->assertInstanceOf('InvokeClass', $norm[0]);

        $expected = ['String value', ['array', 'value']];

        /** @var callable */
        $callback = $norm;
        $retval = call_user_func_array($callback, $expected);
        $this->assertSame(array_merge(['InvokeClass'], $expected), $retval);
    }
}
