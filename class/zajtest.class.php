<?php

    namespace Enhance;

    ini_set('error_reporting', (string)E_ALL);
    ini_set('display_errors', '1');

// Public API
    class Core {
        /** @var EnhanceTestFramework $Instance */
        private static $Instance;
        private static $Language = Language::English;

        /** @return Language */
        public static function getLanguage() {
            return self::$Language;
        }

        public static function setLanguage($language) {
            self::$Language = $language;
        }

        public static function discoverTests($path, $isRecursive = true, $excludeRules = []) {
            self::setInstance();
            self::$Instance->discoverTests($path, $isRecursive, $excludeRules);
        }

        public static function runTests($output = TemplateType::Html) {
            self::setInstance();

            /** ADDED BY MOZAJIK (the return) **/
            return self::$Instance->runTests($output);
        }

        public static function getCodeCoverageWrapper($className, $args = null) {
            self::setInstance();
            self::$Instance->registerForCodeCoverage($className);

            return new CodeCoverageWrapper($className, $args);
        }

        public static function log($className, $methodName) {
            self::setInstance();
            self::$Instance->log($className, $methodName);
        }

        public static function getScenario($className, $args = null) {
            return new Scenario($className, $args, self::$Language);
        }

        public static function setInstance() {
            if (self::$Instance === null) {
                self::$Instance = new EnhanceTestFramework(self::$Language);
            }
        }
    }

// Public API
    class TestFixture {

    }

// Public API
    class MockFactory {
        public static function createMock($typeName) {
            return new Mock($typeName, true, Core::getLanguage());
        }
    }

// Public API
    class StubFactory {
        public static function createStub($typeName) {
            return new Mock($typeName, false, Core::getLanguage());
        }
    }

// Public API
    class Expect {
        const AnyValue = 'ENHANCE_ANY_VALUE_WILL_DO';

        public static function method($methodName) {
            $expectation = new Expectation(Core::getLanguage());

            return $expectation->method($methodName);
        }

        public static function getProperty($propertyName) {
            $expectation = new Expectation(Core::getLanguage());

            return $expectation->getProperty($propertyName);
        }

        public static function setProperty($propertyName) {
            $expectation = new Expectation(Core::getLanguage());

            return $expectation->setProperty($propertyName);
        }
    }

// Public API
    class Assert {
        /** @var Assertions $EnhanceAssertions */
        private static $EnhanceAssertions;

        private static function GetEnhanceAssertionsInstance() {
            if (self::$EnhanceAssertions === null) {
                self::$EnhanceAssertions = new Assertions(Core::getLanguage());
            }

            return self::$EnhanceAssertions;
        }

        public static function areIdentical($expected, $actual) {
            self::GetEnhanceAssertionsInstance()->areIdentical($expected, $actual);
        }

        public static function areNotIdentical($expected, $actual) {
            self::GetEnhanceAssertionsInstance()->areNotIdentical($expected, $actual);
        }

        public static function isTrue($actual) {
            self::GetEnhanceAssertionsInstance()->isTrue($actual);
        }

        public static function isFalse($actual) {
            self::GetEnhanceAssertionsInstance()->isFalse($actual);
        }

        public static function isNull($actual) {
            self::GetEnhanceAssertionsInstance()->isNull($actual);
        }

        public static function isNotNull($actual) {
            self::GetEnhanceAssertionsInstance()->isNotNull($actual);
        }

        public static function isArray($actual) {
            self::GetEnhanceAssertionsInstance()->isArray($actual);
        }

        public static function isNotArray($actual) {
            self::GetEnhanceAssertionsInstance()->isNotArray($actual);
        }

        public static function isBool($actual) {
            self::GetEnhanceAssertionsInstance()->isBool($actual);
        }

        public static function isNotBool($actual) {
            self::GetEnhanceAssertionsInstance()->isNotBool($actual);
        }

        public static function isFloat($actual) {
            self::GetEnhanceAssertionsInstance()->isFloat($actual);
        }

        public static function isNotFloat($actual) {
            self::GetEnhanceAssertionsInstance()->isNotFloat($actual);
        }

        public static function isInt($actual) {
            self::GetEnhanceAssertionsInstance()->isInt($actual);
        }

        public static function isNotInt($actual) {
            self::GetEnhanceAssertionsInstance()->isNotInt($actual);
        }

        public static function isNumeric($actual) {
            self::GetEnhanceAssertionsInstance()->isNumeric($actual);
        }

        public static function isNotNumeric($actual) {
            self::GetEnhanceAssertionsInstance()->isNotNumeric($actual);
        }

        public static function isObject($actual) {
            self::GetEnhanceAssertionsInstance()->isObject($actual);
        }

        public static function isNotObject($actual) {
            self::GetEnhanceAssertionsInstance()->isNotObject($actual);
        }

        public static function isResource($actual) {
            self::GetEnhanceAssertionsInstance()->isResource($actual);
        }

        public static function isNotResource($actual) {
            self::GetEnhanceAssertionsInstance()->isNotResource($actual);
        }

        public static function isScalar($actual) {
            self::GetEnhanceAssertionsInstance()->isScalar($actual);
        }

        public static function isNotScalar($actual) {
            self::GetEnhanceAssertionsInstance()->isNotScalar($actual);
        }

        public static function isString($actual) {
            self::GetEnhanceAssertionsInstance()->isString($actual);
        }

        public static function isNotString($actual) {
            self::GetEnhanceAssertionsInstance()->isNotString($actual);
        }

        public static function contains($expected, $actual) {
            self::GetEnhanceAssertionsInstance()->contains($expected, $actual);
        }

        public static function notContains($expected, $actual) {
            self::GetEnhanceAssertionsInstance()->notContains($expected, $actual);
        }

        public static function fail() {
            self::GetEnhanceAssertionsInstance()->fail();
        }

        public static function inconclusive() {
            self::GetEnhanceAssertionsInstance()->inconclusive();
        }

        public static function isInstanceOfType($expected, $actual) {
            self::GetEnhanceAssertionsInstance()->isInstanceOfType($expected, $actual);
        }

        public static function isNotInstanceOfType($expected, $actual) {
            self::GetEnhanceAssertionsInstance()->isNotInstanceOfType($expected, $actual);
        }

        public static function throws($class, $methodName, $args = null) {
            self::GetEnhanceAssertionsInstance()->throws($class, $methodName, $args);
        }
    }

// Internal Workings
// You don't need to call any of these bits directly - use the public API above, which will
// use the stuff below to carry out your tests!

    class TextFactory {
        public static $Text;

        public static function getLanguageText($language) {
            if (self::$Text === null) {
                $languageClass = 'Enhance\Text'.$language;
                self::$Text = new $languageClass();
            }

            return self::$Text;
        }
    }

    class TextEn {
        public $FormatForTestRunTook = 'Test run took {0} seconds';
        public $FormatForExpectedButWas = 'Expected {0} but was {1}';
        public $FormatForExpectedNotButWas = 'Expected NOT {0} but was {1}';
        public $FormatForExpectedContainsButWas = 'Expected to contain {0} but was {1}';
        public $FormatForExpectedNotContainsButWas = 'Expected NOT to contain {0} but was {1}';
        public $EnhanceTestFramework = 'Enhance Test Framework';
        public $EnhanceTestFrameworkFull = 'Enhance PHP Unit Testing Framework';
        public $TestResults = 'Test Results';
        public $Test = 'Test';
        public $TestPassed = 'Test Passed';
        public $TestFailed = 'Test Failed';
        public $Passed = 'Passed';
        public $Skipped = 'Skipped';
        public $Failed = 'Failed';
        public $ExpectationFailed = 'Expectation failed';
        public $Expected = 'Expected';
        public $Called = 'Called';
        public $InconclusiveOrNotImplemented = 'Inconclusive or not implemented';
        public $Times = 'Times';
        public $MethodCoverage = 'Method Coverage';
        public $Copyright = 'Copyright';
        public $ExpectedExceptionNotThrown = 'Expected exception was not thrown';
        public $CannotCallVerifyOnStub = 'Cannot call VerifyExpectations on a stub';
        public $ReturnsOrThrowsNotBoth = 'You must only set a single return value (1 returns() or 1 throws())';
        public $ScenarioWithExpectMismatch = 'Scenario must be initialised with the same number of "with" and "expect" calls';
        public $LineFile = 'Line {0} in file {1}';
    }


    class EnhanceTestFramework {
        private $FileSystem;
        private $Text;
        private $Tests = [];
        private $Results = [];
        private $Warnings = [];
        private $Errors = [];
        private $Duration;
        private $MethodCalls = [];
        private $Language;

        public function __construct($language) {
            $this->Text = TextFactory::getLanguageText($language);
            $this->FileSystem = new FileSystem();
            $this->Language = $language;
        }

        public function discoverTests($path, $isRecursive, $excludeRules) {
            $directory = rtrim($path, '/');
            if (is_dir($directory)) {
                $phpFiles = $this->FileSystem->getFilesFromDirectory($directory, $isRecursive, $excludeRules);
                foreach ($phpFiles as $file) {
                    /** @noinspection PhpIncludeInspection */
                    include_once($file);
                }
            }
        }

        public function runTests($output) {
            $this->getTestFixturesByParent();
            $this->run();

            /** ADDED BY MOZAJIK **/
            if ($output == "MOZAJIK") {
                return $this;
            }

            if (PHP_SAPI === 'cli' && $output != TemplateType::Tap) {
                $output = TemplateType::Cli;
            }

            $OutputTemplate = TemplateFactory::createOutputTemplate($output, $this->Language);
            echo $OutputTemplate->get(
                $this->Errors,
                $this->Results,
                $this->Text,
                $this->Duration,
                $this->MethodCalls
            );

            if (count($this->Errors) > 0) {
                exit(1);
            } else {
                exit(0);
            }
        }

        public function log($className, $methodName) {
            $index = $this->getMethodIndex($className, $methodName);
            if (array_key_exists($index, $this->MethodCalls)) {
                $this->MethodCalls[$index] = $this->MethodCalls[$index] + 1;
            }
        }

        public function registerForCodeCoverage($className) {
            $classMethods = get_class_methods($className);
            foreach ($classMethods as $methodName) {
                $index = $this->getMethodIndex($className, $methodName);
                if (!array_key_exists($index, $this->MethodCalls)) {
                    $this->MethodCalls[$index] = 0;
                }
            }
        }

        private function getMethodIndex($className, $methodName) {
            return $className.'#'.$methodName;
        }

        private function getTestFixturesByParent() {
            $classes = get_declared_classes();
            foreach ($classes as $className) {
                $this->AddClassIfTest($className);
            }
        }

        private function AddClassIfTest($className) {
            $parentClassName = get_parent_class($className);
            if ($parentClassName === 'Enhance\TestFixture') {
                $instance = new $className();
                $this->addFixture($instance);
            } elseif ($parentClassName) {
                $ancestorClassName = get_parent_class($parentClassName);
                if ($ancestorClassName === 'Enhance\TestFixture') {
                    $instance = new $className();
                    $this->addFixture($instance);
                }
            }
        }

        private function addFixture($class) {
            $classMethods = get_class_methods($class);
            foreach ($classMethods as $method) {
                if (strtolower($method) !== 'setup' && strtolower($method) !== 'teardown') {
                    $reflection = new \ReflectionMethod($class, $method);
                    if ($reflection->isPublic()) {
                        $this->addTest($class, $method);
                    }
                }
            }
        }

        private function addTest($class, $method) {
            $testMethod = new Test($class, $method);
            $this->Tests[] = $testMethod;
        }

        private function run() {
            $start = microtime(true);
            foreach ($this->Tests as /** @var Test $test */
                $test) {
                $result = $test->run();
                switch ($result) {
                    case Test::RESULT_SUCCESS:
                        $message = $test->getTestName().' - '.$this->Text->Passed;
                        $this->Duration = microtime(true) - $start;
                        $message .= "<span class='label label-success pull-right'>".number_format($this->Duration,
                                2)." seconds</span>";
                        $this->Results[] = new TestMessage($message, $test, Test::RESULT_SUCCESS);
                        break;
                    case Test::RESULT_SKIPPED:
                        $message = $test->getTestName().' - '.$this->Text->Skipped;
                        $this->Duration = microtime(true) - $start;
                        $message .= "<span class='label label-warning pull-right'>".number_format($this->Duration,
                                2)." seconds</span>";
                        $this->Warnings[] = new TestMessage($message, $test, Test::RESULT_SKIPPED);
                        break;
                    case Test::RESULT_FAILED:
                    default:
                        $message = '['.str_replace('{0}', $test->getLine(),
                                str_replace('{1}', $test->getFile(), $this->Text->LineFile)).'] '.
                            $test->getTestName().' - '.
                            $this->Text->Failed.' - '.$test->getMessage();
                        $this->Duration = microtime(true) - $start;
                        $message .= "<span class='label label-important pull-right'>".number_format($this->Duration,
                                2)." seconds</span>";
                        $this->Errors[] = new TestMessage($message, $test, Test::RESULT_FAILED);
                        break;
                }
            }
        }

        /**
         * Read only access to all privates - ADDED BY MOZAJIK.
         **/
        public function __get($name) {
            return $this->$name;
        }
    }

    class FileSystem {
        public function getFilesFromDirectory($directory, $isRecursive, $excludeRules) {
            $files = [];
            if ($handle = opendir($directory)) {
                while (false !== ($file = readdir($handle))) {
                    if ($file != '.' && $file != '..' && strpos($file, '.') !== 0) {
                        if ($this->isFolderExcluded($file, $excludeRules)) {
                            continue;
                        }

                        if (is_dir($directory.'/'.$file)) {
                            if ($isRecursive) {
                                $dir2 = $directory.'/'.$file;
                                $files[] = $this->getFilesFromDirectory($dir2, $isRecursive, $excludeRules);
                            }
                        } else {
                            $files[] = $directory.'/'.$file;
                        }
                    }
                }
                closedir($handle);
            }

            return $this->flattenArray($files);
        }

        private function isFolderExcluded($folder, $excludeRules) {
            $folder = substr($folder, strrpos($folder, '/'));

            foreach ($excludeRules as $excluded) {
                if ($folder === $excluded) {
                    return true;
                }
            }

            return false;
        }

        public function flattenArray($array) {
            $merged = [];
            foreach ($array as $a) {
                if (is_array($a)) {
                    $merged = array_merge($merged, $this->flattenArray($a));
                } else {
                    $merged[] = $a;
                }
            }

            return $merged;
        }
    }

    class TestMessage {
        public $message;
        public $test;
        public $result;

        public function __construct($message, $test, $result) {
            $this->message = $message;
            $this->test = $test;
            $this->result = $result;
        }
    }

    class Test {
        private $ClassName;
        private $TestName;
        private $TestMethod;
        private $SetUpMethod;
        private $TearDownMethod;
        private $Message;
        private $Line;
        private $File;

        const RESULT_SUCCESS = 0;
        const RESULT_FAILED = 1;
        const RESULT_SKIPPED = 2;


        public function __construct($class, $method) {
            $className = get_class($class);
            $this->ClassName = $className;
            $this->TestMethod = [$className, $method];
            $this->SetUpMethod = [$className, 'setUp'];
            $this->TearDownMethod = [$className, 'tearDown'];
            $this->TestName = $method;
        }

        public function getTestName() {
            return $this->TestName;
        }

        public function getClassName() {
            return $this->ClassName;
        }

        public function getMessage() {
            return $this->Message;
        }

        public function getLine() {
            return $this->Line;
        }

        public function getFile() {
            return $this->File;
        }

        public function run() {
            /** @var $testClass iTestable|\zajTestInstance */
            $testClass = new $this->ClassName();

            /** added by Outlast Framework **/
            $testClass->ofw = \zajLib::me();
            $testClass->zajlib = $testClass->ofw;
            $setUpMethodResult = false;

            try {
                if (is_callable($this->SetUpMethod, true) && method_exists($testClass, 'setUp')) {
                    $setUpMethodResult = $testClass->setUp();
                } else {
                    // If no set up method, then we assume success
                    $setUpMethodResult = true;
                }
            } catch (\Exception $e) {
            }

            /** added by Outlast Framework */
            // Only run these tests if result is not explicitly false
            if ($setUpMethodResult !== false) {
                try {
                    /** added by Outlast Framework */
                    $testClass->ofw->error->surpress_errors_during_test(false);
                    // Now run test
                    $testClass->{$this->TestName}();
                    $result = self::RESULT_SUCCESS;
                } catch (TestException $e) {
                    $this->Message = $e->getMessage();
                    $this->Line = $e->getLine();
                    $this->File = pathinfo($e->getFile(), PATHINFO_BASENAME);
                    $result = self::RESULT_FAILED;
                }
            } else {
                // Skipped result
                $result = self::RESULT_SKIPPED;
            }

            try {
                if (is_callable($this->TearDownMethod, true) && method_exists($testClass, 'tearDown')) {
                    $testClass->tearDown();
                }
            } catch (\Exception $e) {
            }

            return $result;
        }
    }

    class CodeCoverageWrapper {
        private $Instance;
        private $ClassName;

        public function __construct($className, $args) {
            $this->ClassName = $className;
            if ($args !== null) {
                $rc = new \ReflectionClass($className);
                $this->Instance = $rc->newInstanceArgs($args);
            } else {
                $this->Instance = new $className();
            }
            Core::log($this->ClassName, $className);
            Core::log($this->ClassName, '__construct');
        }

        public function __call($methodName, $args = null) {
            Core::log($this->ClassName, $methodName);
            if ($args !== null) {
                /** @noinspection PhpParamsInspection */
                return call_user_func_array([$this->Instance, $methodName], $args);
            } else {
                return $this->Instance->{$methodName}();
            }
        }

        public function __get($propertyName) {
            return $this->Instance->{$propertyName};
        }

        public function __set($propertyName, $value) {
            $this->Instance->{$propertyName} = $value;
        }
    }

    class Mock {
        private $IsMock;
        private $Text;
        private $ClassName;
        private $Expectations = [];

        public function __construct($className, $isMock, $language) {
            $this->IsMock = $isMock;
            $this->ClassName = $className;
            $this->Text = TextFactory::getLanguageText($language);
        }

        public function addExpectation($expectation) {
            $this->Expectations[] = $expectation;
        }

        public function verifyExpectations() {
            if (!$this->IsMock) {
                throw new \Exception(
                    $this->ClassName.': '.$this->Text->CannotCallVerifyOnStub
                );
            }

            foreach ($this->Expectations as /** @var Expectation $expectation */
                $expectation) {
                if (!$expectation->verify()) {
                    $Arguments = '';
                    if (isset($expectation->MethodArguments)) {
                        foreach ($expectation->MethodArguments as $argument) {
                            if (isset($Arguments[0])) {
                                $Arguments .= ', ';
                            }
                            $Arguments .= $argument;
                        }
                    }

                    throw new \Exception(
                        $this->Text->ExpectationFailed.' '.
                        $this->ClassName.'->'.$expectation->MethodName.'('.$Arguments.') '.
                        $this->Text->Expected.' #'.$expectation->ExpectedCalls.' '.
                        $this->Text->Called.' #'.$expectation->ActualCalls, 0);
                }
            }
        }

        public function __call($methodName, $args) {
            return $this->getReturnValue('method', $methodName, $args);
        }

        public function __get($propertyName) {
            return $this->getReturnValue('getProperty', $propertyName, []);
        }

        public function __set($propertyName, $value) {
            $this->getReturnValue('setProperty', $propertyName, [$value]);
        }

        private function getReturnValue($type, $methodName, $args) {
            $Expectation = $this->getMatchingExpectation($type, $methodName, $args);
            $Expected = true;
            if ($Expectation === null) {
                $Expected = false;
            }

            if ($Expected) {
                ++$Expectation->ActualCalls;
                if ($Expectation->ReturnException) {
                    throw new \Exception($Expectation->ReturnValue);
                }

                return $Expectation->ReturnValue;
            }

            if ($this->IsMock) {
                throw new \Exception(
                    $this->Text->ExpectationFailed.' '.
                    $this->ClassName.'->'.$methodName.'('.$args.') '.
                    $this->Text->Expected.' #0 '.
                    $this->Text->Called.' #1', 0);
            }

            return null;
        }

        private function getMatchingExpectation($type, $methodName, $arguments) {
            foreach ($this->Expectations as $expectation) {
                if ($expectation->Type === $type) {
                    if ($expectation->MethodName === $methodName) {
                        $isMatch = true;
                        if ($expectation->ExpectArguments) {
                            $isMatch = $this->argumentsMatch(
                                $expectation->MethodArguments,
                                $arguments
                            );
                        }
                        if ($isMatch) {
                            return $expectation;
                        }
                    }
                }
            }

            return null;
        }

        private function argumentsMatch($arguments1, $arguments2) {
            $Count1 = count($arguments1);
            $Count2 = count($arguments2);
            $isMatch = true;
            if ($Count1 === $Count2) {
                for ($i = 0; $i < $Count1; ++$i) {
                    if ($arguments1[$i] === Expect::AnyValue
                        || $arguments2[$i] === Expect::AnyValue) {
                        // No need to match
                    } else {
                        if ($arguments1[$i] !== $arguments2[$i]) {
                            $isMatch = false;
                        }
                    }
                }
            } else {
                $isMatch = false;
            }

            return $isMatch;
        }
    }

    class Scenario {
        private $Text;
        private $Class;
        private $FunctionName;
        private $Inputs = [];
        private $Expectations = [];

        public function __construct($class, $functionName, $language) {
            $this->Class = $class;
            $this->FunctionName = $functionName;
            $this->Text = TextFactory::getLanguageText($language);
        }

        public function with() {
            $this->Inputs[] = func_get_args();

            return $this;
        }

        public function expect() {
            $this->Expectations[] = func_get_args();

            return $this;
        }

        public function verifyExpectations() {
            if (count($this->Inputs) !== count($this->Expectations)) {
                throw new \Exception($this->Text->ScenarioWithExpectMismatch);
            }

            $exceptionText = '';

            while (count($this->Inputs) > 0) {
                $input = array_shift($this->Inputs);
                $expected = array_shift($this->Expectations);
                $expected = $expected[0];

                $actual = call_user_func_array([$this->Class, $this->FunctionName], $input);

                if (is_float($expected)) {
                    if ((string)$expected !== (string)$actual) {
                        $exceptionText .= str_replace('{0}', $expected,
                            str_replace('{1}', $actual, $this->Text->FormatForExpectedButWas));
                    }
                } else if ($expected != $actual) {
                    $exceptionText .= str_replace('{0}', $expected,
                        str_replace('{1}', $actual, $this->Text->FormatForExpectedButWas));
                }
            }

            if ($exceptionText !== '') {
                throw new \Exception($exceptionText, 0);
            }
        }
    }

    class Expectation {
        public $MethodName;
        public $MethodArguments;
        public $ReturnValue;
        public $ReturnException;
        public $ExpectedCalls;
        public $ActualCalls;
        public $ExpectArguments;
        public $ExpectTimes;
        public $Type;
        public $Text;

        public function __construct($language) {
            $this->ExpectedCalls = -1;
            $this->ActualCalls = 0;
            $this->ExpectArguments = false;
            $this->ExpectTimes = false;
            $this->ReturnException = false;
            $this->ReturnValue = null;
            $textFactory = new TextFactory();
            $this->Text = $textFactory->getLanguageText($language);
        }

        public function method($methodName) {
            $this->Type = 'method';
            $this->MethodName = $methodName;

            return $this;
        }

        public function getProperty($propertyName) {
            $this->Type = 'getProperty';
            $this->MethodName = $propertyName;

            return $this;
        }

        public function setProperty($propertyName) {
            $this->Type = 'setProperty';
            $this->MethodName = $propertyName;

            return $this;
        }

        public function with() {
            $this->ExpectArguments = true;
            $this->MethodArguments = func_get_args();

            return $this;
        }

        public function returns($returnValue) {
            if ($this->ReturnValue !== null) {
                throw new \Exception($this->Text->ReturnsOrThrowsNotBoth);
            }
            $this->ReturnValue = $returnValue;

            return $this;
        }

        public function throws($errorMessage) {
            if ($this->ReturnValue !== null) {
                throw new \Exception($this->Text->ReturnsOrThrowsNotBoth);
            }
            $this->ReturnValue = $errorMessage;
            $this->ReturnException = true;

            return $this;
        }

        public function times($expectedCalls) {
            $this->ExpectTimes = true;
            $this->ExpectedCalls = $expectedCalls;

            return $this;
        }

        public function verify() {
            $ExpectationMet = true;
            if ($this->ExpectTimes) {
                if ($this->ExpectedCalls !== $this->ActualCalls) {
                    $ExpectationMet = false;
                }
            }

            return $ExpectationMet;
        }
    }

    class Assertions {
        private $Text;

        public function __construct($language) {
            $this->Text = TextFactory::getLanguageText($language);
        }

        public function areIdentical($expected, $actual) {
            if (is_float($expected)) {
                if ((string)$expected !== (string)$actual) {
                    throw new TestException(
                        str_replace('{0}', $this->getDescription($expected),
                            str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)),
                        0);
                }
            } else if ($expected !== $actual) {
                throw new TestException(
                    str_replace('{0}', $this->getDescription($expected),
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function areNotIdentical($expected, $actual) {
            if (is_float($expected)) {
                if ((string)$expected === (string)$actual) {
                    throw new TestException(
                        str_replace('{0}', $this->getDescription($expected),
                            str_replace('{1}', $this->getDescription($actual),
                                $this->Text->FormatForExpectedNotButWas)), 0);
                }
            } else if ($expected === $actual) {
                throw new TestException(
                    str_replace('{0}', $this->getDescription($expected),
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isTrue($actual) {
            if ($actual !== true) {
                throw new TestException(
                    str_replace('{0}', 'true',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isFalse($actual) {
            if ($actual !== false) {
                throw new TestException(
                    str_replace('{0}', 'false',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function contains($expected, $actual) {
            $result = strpos($actual, $expected);
            if ($result === false) {
                throw new TestException(
                    str_replace('{0}', $this->getDescription($expected),
                        str_replace('{1}', $this->getDescription($actual),
                            $this->Text->FormatForExpectedContainsButWas)), 0);
            }
        }

        public function notContains($expected, $actual) {
            $result = strpos($actual, $expected);
            if ($result !== false) {
                throw new TestException(
                    str_replace('{0}', $this->getDescription($expected),
                        str_replace('{1}', $this->getDescription($actual),
                            $this->Text->FormatForExpectedNotContainsButWas)), 0);
            }
        }

        public function isNull($actual) {
            if ($actual !== null) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotNull($actual) {
            if ($actual === null) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isArray($actual) {
            if (!is_array($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotArray($actual) {
            if (is_array($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isBool($actual) {
            if (!is_bool($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotBool($actual) {
            if (is_bool($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isFloat($actual) {
            if (!is_float($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotFloat($actual) {
            if (is_float($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isInt($actual) {
            if (!is_int($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotInt($actual) {
            if (is_int($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isNumeric($actual) {
            if (!is_numeric($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotNumeric($actual) {
            if (is_numeric($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isObject($actual) {
            if (!is_object($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotObject($actual) {
            if (is_object($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isResource($actual) {
            if (!is_resource($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotResource($actual) {
            if (is_resource($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isScalar($actual) {
            if (!is_scalar($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotScalar($actual) {
            if (is_scalar($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function isString($actual) {
            if (!is_string($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedButWas)), 0);
            }
        }

        public function isNotString($actual) {
            if (is_string($actual)) {
                throw new TestException(
                    str_replace('{0}', 'null',
                        str_replace('{1}', $this->getDescription($actual), $this->Text->FormatForExpectedNotButWas)),
                    0);
            }
        }

        public function fail() {
            throw new TestException($this->Text->Failed, 0);
        }

        public function inconclusive() {
            throw new TestException($this->Text->InconclusiveOrNotImplemented, 0);
        }

        public function isInstanceOfType($expected, $actual) {
            $actualType = get_class($actual);
            if ($expected !== $actualType) {
                throw new TestException(
                    str_replace('{0}', $expected,
                        str_replace('{1}', $actualType, $this->Text->FormatForExpectedButWas)), 0);
            };
        }

        public function isNotInstanceOfType($expected, $actual) {
            $actualType = get_class($actual);
            if ($expected === $actualType) {
                throw new TestException(
                    str_replace('{0}', $expected,
                        str_replace('{1}', $actualType, $this->Text->FormatForExpectedNotButWas)), 0);
            };
        }

        public function throws($class, $methodName, $args = null) {
            $exception = false;

            try {
                if ($args !== null) {
                    /** @noinspection PhpParamsInspection */
                    call_user_func_array([$class, $methodName], $args);
                } else {
                    $class->{$methodName}();
                }
            } catch (\Exception $e) {
                $exception = true;
            }

            if (!$exception) {
                throw new TestException($this->Text->ExpectedExceptionNotThrown, 0);
            }
        }

        private function getDescription($mixed) {
            if (is_object($mixed)) {
                return get_class($mixed);
            } else if (is_bool($mixed)) {
                return $mixed ? 'true' : 'false';
            } else {
                return (string)$mixed;
            }
        }
    }

    class TestException extends \Exception {
        public function __construct($message = null, $code = 0, \Exception $previous = null) {
            parent::__construct($message, $code, $previous);

            $trace = $this->getTrace();

            $this->line = $trace[1]['line'];
            $this->file = $trace[1]['file'];
        }
    }

    interface iOutputTemplate {
        public function getTemplateType();

        public function get($errors, $results, $text, $duration, $methodCalls);
    }

    interface iTestable {
        public function setUp();

        public function tearDown();
    }

    class HtmlTemplate implements iOutputTemplate {
        private $Text;

        public function __construct($language) {
            $this->Text = TextFactory::getLanguageText($language);
        }

        public function getTemplateType() {
            return TemplateType::Html;
        }

        public function get($errors, $results, $text, $duration, $methodCalls) {
            $message = '';
            $failCount = count($errors);
            $passCount = count($results);
            $methodCallCount = count($methodCalls);

            $currentClass = '';
            if ($failCount > 0) {
                $message .= '<h2 class="error">'.$text->Test.' '.$text->Failed.'</h2>';

                $message .= '<ul>';
                foreach ($errors as $error) {
                    $testClassName = $error->Test->getClassName();
                    if ($testClassName != $currentClass) {
                        if ($currentClass === '') {
                            $message .= '<li>';
                        } else {
                            $message .= '</ul></li><li>';
                        }
                        $message .= '<strong>'.$testClassName.'</strong><ul>';
                        $currentClass = $testClassName;
                    }
                    $message .= '<li class="error">'.$error->Message.'</li>';
                }
                $message .= '</ul></li></ul>';
            } else {
                $message .= '<h2 class="ok">'.$text->TestPassed.'</h2>';
            }

            $currentClass = '';
            if ($passCount > 0) {
                $message .= '<ul>';
                foreach ($results as $result) {
                    $testClassName = $result->Test->getClassName();
                    if ($testClassName != $currentClass) {
                        if ($currentClass === '') {
                            $message .= '<li>';
                        } else {
                            $message .= '</ul></li><li>';
                        }
                        $message .= '<strong>'.$testClassName.'</strong><ul>';
                        $currentClass = $testClassName;
                    }
                    $message .= '<li class="ok">'.$result->Message.'</li>';
                }
                $message .= '</ul></li></ul>';
            }

            $message .= '<h3>'.$text->MethodCoverage.'</h3>';
            if ($methodCallCount > 0) {
                $message .= '<ul>';
                foreach ($methodCalls as $key => $value) {
                    $key = str_replace('#', '->', $key);
                    if ($value === 0) {
                        $message .= '<li class="error">'.$key.' '.$text->Called.' '.$value.' '.
                            $text->Times.'</li>';
                    } else {
                        $message .= '<li class="ok">'.$key.' '.$text->Called.' '.$value.' '.
                            $text->Times.'</li>';
                    }
                }
                $message .= '</ul>';
            }

            $message .= '<p>'.str_replace('{0}', $duration, $text->FormatForTestRunTook).'</p>';

            return $this->getTemplateWithMessage($message);
        }

        private function getTemplateWithMessage($content) {
            return str_replace('{0}', $content, '<!DOCTYPE html>
        <html lang="en">
            <head>
                <meta charset="utf-8">
                <title>'.$this->Text->TestResults.'</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <meta name="copyright" content="Steve Fenton 2011-Present">
                <meta name="author" content="Steve Fenton">
                <style>
                    article, aside, figure, footer, header, hgroup, nav, section { display: block; clear: both; }

                    body {
                        font-family: "Century Gothic", "Apple Gothic", sans-serif;
                        font-size: 14px;
                        color: Black;
                        margin: 0;
                        padding-bottom: 5em;
                    }
                
                    .error {
                        color: red;
                    }
                    
                    .ok {
                        color: green;
                    }
                </style>
            </head>
            <body>
                <header>
                    <h1>'.$this->Text->EnhanceTestFramework.'</h1>
                </header>
                
                <article id="maincontent">
                    {0}
                </article>
        
                <footer>
                    <p><a href="http://www.enhance-php.com/">'.$this->Text->EnhanceTestFrameworkFull.'</a> '.
                $this->Text->Copyright.' &copy;2011 - '.date('Y').
                ' <a href="http://www.stevefenton.co.uk/">Steve Fenton</a>.</p>
                </footer>
            </body>
        </html>');
        }
    }

    class XmlTemplate implements iOutputTemplate {
        private $Text;
        private $Tab = "    ";
        private $CR = "\n";

        public function __construct($language) {
            $this->Text = TextFactory::getLanguageText($language);
        }

        public function getTemplateType() {
            return TemplateType::Xml;
        }

        public function get($errors, $results, $text, $duration, $methodCalls) {
            $message = '';
            $failCount = count($errors);

            $message .= '<enhance>'.$this->CR;
            if ($failCount > 0) {
                $message .= $this->getNode(1, 'result', $text->TestFailed);
            } else {
                $message .= $this->getNode(1, 'result', $text->TestPassed);
            }

            $message .= $this->Tab.'<testResults>'.$this->CR.
                $this->getBadResults($errors).
                $this->getGoodResults($results).
                $this->Tab.'</testResults>'.$this->CR.
                $this->Tab.'<codeCoverage>'.$this->CR.
                $this->getCodeCoverage($methodCalls).
                $this->Tab.'</codeCoverage>'.$this->CR;

            $message .= $this->getNode(1, 'testRunDuration', $duration).
                '</enhance>'.$this->CR;

            return $this->getTemplateWithMessage($message);
        }

        public function getBadResults($errors) {
            $message = '';
            foreach ($errors as $error) {
                $message .= $this->getNode(2, 'fail', $error->Message);
            }

            return $message;
        }

        public function getGoodResults($results) {
            $message = '';
            foreach ($results as $result) {
                $message .= $this->getNode(2, 'pass', $result->Message);
            }

            return $message;
        }

        public function getCodeCoverage($methodCalls) {
            $message = '';
            foreach ($methodCalls as $key => $value) {
                $message .= $this->buildCodeCoverageMessage($key, $value);
            }

            return $message;
        }

        private function buildCodeCoverageMessage($key, $value) {
            return $this->Tab.$this->Tab.'<method>'.$this->CR.
                $this->getNode(3, 'name', str_replace('#', '-&gt;', $key)).
                $this->getNode(3, 'timesCalled', $value).
                $this->Tab.$this->Tab.'</method>'.$this->CR;
        }

        private function getNode($tabs, $nodeName, $nodeValue) {
            $node = '';
            for ($i = 0; $i < $tabs; ++$i) {
                $node .= $this->Tab;
            }
            $node .= '<'.$nodeName.'>'.$nodeValue.'</'.$nodeName.'>'.$this->CR;

            return $node;
        }

        private function getTemplateWithMessage($content) {
            return str_replace('{0}', $content, '<?xml version="1.0" encoding="UTF-8" ?>'."\n".
                '{0}');
        }
    }

    class CliTemplate implements iOutputTemplate {
        private $Text;
        private $CR = "\n";

        public function __construct($language) {
            $this->Text = TextFactory::getLanguageText($language);
        }

        public function getTemplateType() {
            return TemplateType::Cli;
        }

        public function get($errors, $results, $text, $duration, $methodCalls) {
            $failCount = count($errors);

            $resultMessage = $text->TestPassed.$this->CR;
            if ($failCount > 0) {
                $resultMessage = $text->TestFailed.$this->CR;
            }

            $message = $this->CR.
                $resultMessage.
                $this->CR.
                $this->getBadResults($errors).
                $this->getGoodResults($results).
                $this->CR.
                $this->getMethodCoverage($methodCalls).
                $this->CR.
                $resultMessage.
                str_replace('{0}', $duration, $text->FormatForTestRunTook).$this->CR;

            return $message;
        }

        public function getBadResults($errors) {
            $message = '';
            foreach ($errors as $error) {
                $message .= $error->Message.$this->CR;
            }

            return $message;
        }

        public function getGoodResults($results) {
            $message = '';
            foreach ($results as $result) {
                $message .= $result->Message.$this->CR;
            }

            return $message;
        }

        public function getMethodCoverage($methodCalls) {
            $message = '';
            foreach ($methodCalls as $key => $value) {
                $message .= str_replace('#', '->', $key).':'.$value.$this->CR;
            }

            return $message;
        }
    }

    class TapTemplate implements iOutputTemplate {
        private $Text;
        private $CR = "\n";

        public function __construct($language) {
            $this->Text = TextFactory::getLanguageText($language);
        }

        public function getTemplateType() {
            return TemplateType::Cli;
        }

        public function get($errors, $results, $text, $duration, $methodCalls) {
            $failCount = count($errors);
            $passCount = count($results);
            $total = $failCount + $passCount;
            $count = 0;

            $message = '1..'.$total.$this->CR;

            foreach ($errors as $error) {
                ++$count;
                $message .= 'not ok '.$count.' '.$error->Message.$this->CR;
            }

            foreach ($results as $result) {
                ++$count;
                $message .= 'ok '.$count.' '.$result->Message.$this->CR;
            }

            return $message;
        }

    }

    class TemplateFactory {
        public static function createOutputTemplate($type, $language) {
            switch ($type) {
                case TemplateType::Xml:
                    return new XmlTemplate($language);
                    break;
                case TemplateType::Html:
                    return new HtmlTemplate($language);
                    break;
                case TemplateType::Cli:
                    return new CliTemplate($language);
                    break;
                case TemplateType::Tap:
                    return new TapTemplate($language);
                    break;
            }

            return new HtmlTemplate($language);
        }
    }

    class TemplateType {
        const Xml = 0;
        const Html = 1;
        const Cli = 2;
        const Tap = 3;
    }

    class Language {
        const English = 'En';
    }

    class Localisation {
        public $Language = Language::English;
    }