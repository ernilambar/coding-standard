<?php

/**
 * Sample function.
 */
function sample_function() {
}

// Sample constant.
const MY_CONSTANT = 123;

/**
 * Class Sample_Class.
 */
class Sample_Class {
  public function say_hello() {
    echo "Say hello.";
  }
}

trait MyTrait {
  public function myTraitMethod() {
    echo "Hello from MyTrait!";
	}
}

interface MyInterface {
  public function myMethod();
}

/**
 * Function one.
 *
 * @since
 */
function function_one() {
}

/**
 * Function two.
 *
 * @since invalid version
 */
function function_two() {
}

/**
 * Function three.
 *
 * @since 1.0.0
 *
 * @param int $id
 */
function function_three() {
}

/**
 * Function four.
 *
 * @since 1.0.0
 * @return void
 */
function function_four() {
}

/**
 * Function five.
 *
 * @see text
 * @since 1.0.0
 */
function function_five() {
}

/**
 * Function.
 *
 * @since 1.0.0
 * @since 2.0.0 New feature
 */
function function_six() {
}
