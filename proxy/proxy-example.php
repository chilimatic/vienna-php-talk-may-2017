<?php

/**
 * Class ToBeExtended
 */
class ToBeExtended {

    private function myFunc(int $number): int {
        return $number;
    }

    private function myOtherFunc(int $myNumber): int {
        return $myNumber;
    }
}


$methodSet = [
    'myFunc' => function($context, ReflectionMethod $reflection, int $number, float $secondNumber) : float {
        return (float)  $reflection->invokeArgs($context, [$number]) + $secondNumber;
    },
    'myOtherFunc' => function($context, ReflectionMethod $reflection, int $myOtherNumber) : float {
        return (float)  $reflection->invokeArgs($context, [$myOtherNumber]);
    }
];


function overrideMethods($instance, array $methodSet) {
    $reflectionRegister = [];

    $reflectionClass = new ReflectionClass($instance);
    $reflectionClass->getDocComment();
    /**
     * @var $closure Closure
     */
    foreach ($methodSet as $key => $closure) {
        $method = $reflectionClass->getMethod($key);
        $method->setAccessible(true);
        $reflectionRegister[$key] = $method;
        $closure->bindTo($instance);
    }


    $proxy = new class {
        private $virtualRegister = [];
        private $reflectionRegister = [];
        private $methodContext;

        public function __get($key) {
            return $this->virtualRegister[$key];
        }

        public function __call($methodName, $param) {
            array_unshift($param, $this->methodContext, $this->reflectionRegister[$methodName]);
            return call_user_func_array($this->virtualRegister[$methodName], $param);
        }

        public function __set($key, $value): void {
            return;
        }

        public function __isset($key): bool {
            return isset($this->virtualRegister[$key]);
        }
    };

    $reflectionClassProxy = new ReflectionClass($proxy);

    setProperty(
        $proxy,
        $reflectionClassProxy->getProperty('virtualRegister'),
        $methodSet
    );

    /**
     * @var ReflectionProperty
     */
    setProperty(
        $proxy,
        $reflectionClassProxy->getProperty('reflectionRegister'),
        $reflectionRegister
    );

    setProperty(
        $proxy,
        $reflectionClassProxy->getProperty('methodContext'),
        $instance
    );

    return $proxy;
}

/**
 * @param $instance
 * @param ReflectionProperty $property
 * @param $value
 */
function setProperty($instance, ReflectionProperty $property, $value) {
    $property->setAccessible(true);
    $property->setValue($instance, $value);
    $property->setAccessible(false);
}

$test = overrideMethods(new ToBeExtended(), $methodSet);

var_dump($test->myFunc(1, 2));