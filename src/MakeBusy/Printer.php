<?php
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite\DataProvider;
use \MakeBusy\Common\Log;

class MakeBusy_Printer extends PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener {

    protected $currentTestSuiteName = '';
    protected $currentTestName = '';
    protected $currentTestFileName = '';
    protected $currentTestClassName = '';
    protected $pass = true;
    protected $incomplete = false;
    protected $start_time = 0;
    protected $test_start_time = 0;
    protected $errors = 0;
    protected $incompletes = 0;
    
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
    	$subject = str_ireplace("/", "\/", $this->currentTestFileName);
    	$subject = str_ireplace(".", "\.", $subject);
    	preg_match_all("/" . $subject . "\(?(\d+)\)/", $e->getTraceAsString(), $out);
    	$line = $out[1][0];
    	
    	
    	$this->writeCase('FAILURE', $time, sprintf("line: %s %s", $line , $e->getMessage()), $test);
        $this->pass = false;
        if (isset($_ENV['STACK_TRACE'])) {
            $this->write($e->getTraceAsString());
        }
    }

    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time) {
        $this->writeCase('INCOMPLETE', $time, $e->getMessage(), $test);
        $this->pass = false;
        $this->incomplete = true;
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
    	$this->test_start_time = microtime(true);
    	$re = new ReflectionClass($test);
    	$this->currentTestClassName = $re->getName();
    	$this->currentTestName = $re->getFileName();
    	$this->currentTestFileName = $re->getFileName();
    	$name = $test->getName() == "testMain" ? "" : sprintf("(%s) ", $test->getName());
    	$this->write(sprintf("TEST %s %s... ", $this->currentTestClassName, $name));
    }

    public function endTest(PHPUnit_Framework_Test $test, $time) {
    	if ($this->pass) {
            $this->writeCase('OK', $time, '', $test);
        } else {
        	if($this->incomplete) {
        		$this->incompletes++;
        	} else {
        		$this->errors++;
        	}
        }
        $this->currentTestName = '';
        $this->pass = true;
        $this->incomplete = false;
    }

    private function getEnv($keys) {
        $re = [];
        foreach($keys as $key) {
            if (isset($_ENV[$key])) {
                $re[] = sprintf("%s:%s", $key, $_ENV[$key]);
            }
        }
        return join(" ", $re);
    }

    public function startTestSuite(PHPUnit_Framework_TestSuite $suite) {
    	$this->currentTestSuiteName = $suite->getName();
    	$this->currentTestName = '';
    	if($suite->count() > 1) {
            $this->write(sprintf("START CASE %s\n", $suite->getName()));
        }
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite) {
        $this->currentTestSuiteName = $suite->getName();
        $this->currentTestName = '';
        if($suite->count() > 1) {
        	$status = $this->errors == 0 ? "COMPLETED" : "ERROR";
        	$this->write(sprintf("CASE %s %s\n", $status, $suite->getName()));
        }
    }

    protected function writeCase($status, $time, $message = '', $test = null) {
        $output = '';
        // take care of TestSuite producing error (e.g. by running into exception) as TestSuite doesn't have hasOutput
        if ($test !== null && method_exists($test, 'hasOutput') && $test->hasOutput()) {
            $output = $test->getActualOutput();
        }
        $time = microtime(true) - $this->test_start_time;
        Log::debug("STATUS: %s", $status);
        $tk = explode("\n", $message);
        $this->write(sprintf("%s %.02fs %s\n", $status, $time, $tk[0]));
    }

    public function write($buffer) {
    	if(!strstr($buffer, "PUnit")) {
    		parent::write($buffer);
    	}
    }
}