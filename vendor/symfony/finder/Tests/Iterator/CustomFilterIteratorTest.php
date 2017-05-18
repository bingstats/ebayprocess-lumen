<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Finder\Tests\Iterator;

use Symfony\Component\Finder\Iterator\CustomFilterIterator;

class CustomFilterIteratorTest extends IteratorTestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithInvalidFilter()
    {
        new CustomFilterIterator(new Iterator(), array('foo'));
    }

    /**
     * @dataProvider getAcceptData
     */
    public function testAccept($filters, $expected)
    {
        $inner = new Iterator(array('DataTest.php', 'test.py', 'foo.php'));

        $iterator = new CustomFilterIterator($inner, $filters);

        $this->assertIterator($expected, $iterator);
    }

    public function getAcceptData()
    {
        return array(
            array(array(function (\SplFileInfo $fileinfo) { return false; }), array()),
            array(array(function (\SplFileInfo $fileinfo) { return 0 === strpos($fileinfo, 'test'); }), array('DataTest.php', 'test.py')),
            array(array('is_dir'), array()),
        );
    }
}
