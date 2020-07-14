<?php declare(strict_types=1);

namespace Xynha\Tests;

use PHPUnit\Framework\TestCase;
use Xynha\Container\DiContainer;
use Xynha\Container\DiRuleList;

abstract class AbstractTestCase extends TestCase
{

    /** @var DiRuleList */
    protected $rule;

    /** @var DiContainer */
    protected $dic;

    protected function setUp()
    {
        $this->rule = new DiRuleList();
        $this->dic = new DiContainer($this->rule);
    }

    public function __construct()
    {
        parent::__construct();

        // Load basic test data
        require_once __DIR__ . '/data/Basic.php';

        //Load data for this test
        $name = str_replace('Test', '', get_class($this));
        $path = __DIR__ . '/data/' . $name . '.php';

        if (file_exists($path)) {
            require_once $path;
        }
    }
}