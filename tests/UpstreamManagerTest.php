<?php

namespace PhpSiteRepositoryTool;

use PHPUnit\Framework\TestCase;

class UpstreamManagerTest extends TestCase
{
    /**
     * Data provider for testExample.
     *
     * Return an array of arrays, each of which contains the parameter
     * values to be used in one invocation of the testExample test function.
     */
    public function exampleTestValues()
    {
        return [
            // Result is hardcoded in the example class.
            [8, 2, 2,],
            [8, 3, 3,],
            [8, 7, 8,],
        ];
    }

    /**
     * Test our example class. Each time this function is called, it will
     * be passed data from the data provider function idendified by the
     * dataProvider annotation.
     *
     * @dataProvider exampleTestValues
     */
    public function testExample($expected, $constructor_parameter, $value)
    {
        $example = new UpstreamManager($constructor_parameter);
        $this->assertEquals($expected, $example->multiply($value));
    }
}