<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }

    /**
     * Demo unit test case
     *
     * @return void
     */
    public function testDemoUnitTest()
    {
        $expected = ["assessment-center"];
        $actual = ["assessment-center"];

        $this->assertEquals(
            $expected,
            $actual,
            "actual value is not equals to expected"
        );
    }
}
