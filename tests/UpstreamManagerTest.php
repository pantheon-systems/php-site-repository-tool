<?php

namespace PhpSiteRepositoryTool;

use PHPUnit\Framework\TestCase;

class UpstreamManagerTest extends TestCase
{

    use SiteRepositoryToolTesterTrait;

    /**
     * Data provider for testExample.
     *
     * Return an array of arrays, each of which contains the parameter
     * values to be used in one invocation of the testExample test function.
     */
    public function allUnmergedFilesInAllowListTestValues()
    {
        return [
            [
                [
                'wp-content/themes/2020/LICENSE.md',
                'wp-content/themes/2021/LICENSE.md',
                'wp-content/themes/2022/LICENSE.md',
                ],
                true
            ],
            [
                [
                'wp-content/themes/2020/LICENSE.md',
                'wp-content/themes/2021/README.md',
                'wp-content/themes/2022/LICENSE.md',
                ],
                false
            ],
            [
                [
                'wp-content/themes/2020/README.md',
                'wp-content/themes/2021/README.md',
                'wp-content/themes/2022/README.md',
                ],
                true
            ],
        ];
    }

    /**
     * Test allUnmergedFilesInAllowListTestValues function.
     *
     * @dataProvider allUnmergedFilesInAllowListTestValues
     */
    public function testAllUnmergedFilesInAllowList($value, $expected)
    {
        $upstreamManager = new UpstreamManager();
        $return = $this->callMethod($upstreamManager, 'allUnmergedFilesInAllowList', [$value]);
        $this->assertEquals($expected, $return);
    }
}
