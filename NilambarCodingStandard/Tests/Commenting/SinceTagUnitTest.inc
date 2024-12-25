<?php
/**
 * Sample function.
 */
function sample_function() {
}

/**
 * Sample constant.
 */
const MY_CONSTANT = 123;

/**
 * Class Sample_Class.
 */
class Sample_Class {
  public function say_hello() {
    echo "Say hello.";
  }
}

/**
 * MyTrait.
 */
trait MyTrait {
  public function myTraitMethod() {
    echo "Hello from MyTrait!";
	}
}

/**
 * MyInterface.
 */
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

/**
 * Function.
 *
 * @since initial
 * @since stable New feature
 */
function function_seven() {
}

/**
 * Function.
 *
 * @since 1.0.0
 * @see link
 * @since 2.0.0 New feature
 */
function function_eight() {
}

function function_nine() {
}
