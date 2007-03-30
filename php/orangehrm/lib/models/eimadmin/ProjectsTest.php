<?php
// Call ProjectsTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "ProjectsTest::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once "testConf.php";

$_SESSION['WPATH'] = WPATH;

require_once ROOT_PATH."/lib/confs/Conf.php";
require_once 'Projects.php';

/**
 * Test class for Projects.
 * Generated by PHPUnit_Util_Skeleton on 2007-03-22 at 15:43:08.
 */
class ProjectsTest extends PHPUnit_Framework_TestCase {
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */

    public $classLeaveType = null;
    public $connection = null;


    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("ProjectsTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    }


    /**
     * @todo Implement testAddProject().
     */
    public function testAddProject() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testEditProject().
     */
    public function testEditProject() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testDeleteProject().
     */
    public function testDeleteProject() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testFetchProject().
     */
    public function testFetchProject() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }

    /**
     * @todo Implement testFetchProjects().
     */
    public function testFetchProjects() {
        // Remove the following line when you implement this test.
        $this->markTestIncomplete(
          "This test has not been implemented yet."
        );
    }
}

// Call ProjectsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "ProjectsTest::main") {
    ProjectsTest::main();
}
?>
