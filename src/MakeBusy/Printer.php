<?php
use PHPUnit\Framework\TestListener;
use \MakeBusy\Common\Log;

class MakeBusy_Printer extends PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener {
    protected $currentTestSuiteName = '';
    protected $currentTestName = '';
    protected $pass = true;
    protected $start_time = 0;

    public function __construct($out = null) {
        $this->start_time = microtime(true);
        return parent::__construct($out);
    }

    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $this->writeCase('ERROR', $time, $e->getMessage(), $test);
        $this->pass = false;
        if (isset($_ENV['STACK_TRACE'])) {
            $this->write($e->getTraceAsString());
        }
    }

    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time) {
        $this->writeCase('FAILURE', $time, $e->getMessage(), $test);
        $this->pass = false;
        if (isset($_ENV['STACK_TRACE'])) {
            $this->write($e->getTraceAsString());
        }
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $this->writeCase('INCOMPLETE', $time, $e->getMessage(), $test);
        $this->pass = false;
    }

    public function addRiskyTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $this->writeCase('RISKY', $time, $e->getMessage(), $test);
        $this->pass = false;
    }

    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $this->writeCase('SKIP', $time, $e->getMessage(), $test);
        $this->pass = false;
    }

    public function startTest(PHPUnit_Framework_Test $test) {
        $this->currentTestName = $test->getName();
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {
        if ($this->pass) {
            $this->writeCase('OK', $time, '', $test);
        }
        $this->currentTestName = '';
    }

    private function getEnv($key) {
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        } else {
            return '';
        }
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
        if (! preg_match('/::/', $suite->getName())) {
            $re = new ReflectionClass($suite->getName());
            $this->currentTestSuiteName = sprintf("test: %s case: %s", $re->getShortName(), $re->getParentClass()->getShortName());
            $this->currentTestName = '';
            $this->write(sprintf("RUN %s CLEAN:%s RESTART_PROFILE:%s SKIP_REGISTER:%s\n", $re->getFileName(),
                                 $this->getEnv('CLEAN'), $this->getEnv('RESTART_PROFILE'), $this->getEnv('SKIP_REGISTER')));
        }
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $this->currentTestSuiteName = $suite->getName();
        $this->currentTestName = '';
    }

    protected function writeCase($status, $time, $message = '', $test = null) {
        $output = '';
        // take care of TestSuite producing error (e.g. by running into exception) as TestSuite doesn't have hasOutput
        if ($test !== null && method_exists($test, 'hasOutput') && $test->hasOutput()) {
            $output = $test->getActualOutput();
        }
        $time = microtime(true) - $this->start_time;
        Log::debug("STATUS: %s", $status);
        $this->write(sprintf("TEST %s %.02fs %s\n%s\n", $status, $time, $this->currentTestSuiteName, $message));
    }

}