<?php declare(strict_types=1);

/**
 * This file is part of xynha/dicontainer package.
 *
 * @author Asis Pattisahusiwa <asis.pattisahusiwa@gmail.com>
 * @copyright 2020 Asis Pattisahusiwa
 * @license https://github.com/pattisahusiwa/dicontainer/blob/master/LICENSE Apache-2.0 License
 */
class DicDependant
{

    public $dic;

    public function __construct(\Psr\Container\ContainerInterface $dic)
    {
        $this->dic = $dic;
    }
}
