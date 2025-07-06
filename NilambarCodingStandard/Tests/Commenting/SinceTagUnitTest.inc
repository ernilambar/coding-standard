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

/**
 * Class Class_one.
 */
abstract class Class_one {
  /**
   * Function.
   *
   * @see text
   */
  public function say_hello() {
    echo "Say hello.";
  }

  /**
   * Another function.
   *
   * @see link
   */
  public static function say_hola() {
    echo "Say hola.";
  }
}

/**
 * Function with empty @since tag.
 *
 * @since
 */
function function_ten() {
}

/**
 * Function with invalid version format - no dots.
 *
 * @since 1a2b3c
 */
function function_thirteen() {
}

/**
 * Function with invalid version format - starts with letter.
 *
 * @since v1.0.0
 */
function function_fourteen() {
}

/**
 * Function with invalid version format - contains letters.
 *
 * @since 1.0.0a
 */
function function_fifteen() {
}

/**
 * Function with @since tag not as first tag.
 *
 * @param string $name
 * @since 1.0.0
 */
function function_eighteen() {
}

/**
 * Function with multiple @since tags separated by other tags.
 *
 * @since 1.0.0
 * @param int $id
 * @since 2.0.0
 */
function function_twenty() {
}

/**
 * Function with multiple @since tags in correct order.
 *
 * @since 1.0.0
 * @since 2.0.0
 * @since 3.0.0
 */
function function_twenty_one() {
}

/**
 * Function with @since tag and multi-line description.
 *
 * @since 1.0.0 Added new feature
 *               with multiple lines
 */
function function_twenty_three() {
}

/**
 * Function with @since tag and leading whitespace.
 *
 * @since  1.0.0
 */
function function_twenty_five() {
}

/**
 * Function with @since tag and extra spaces.
 *
 * @since    1.0.0
 */
function function_twenty_six() {
}

/**
 * Function with @since tag and newline.
 *
 * @since 1.0.0
 * New line description
 */
function function_twenty_eight() {
}

/**
 * Function with @since tag missing empty line before it.
 * @since 1.0.0
 */
function function_missing_empty_line() {
}

/**
 * Function with @since tag and other tags, missing empty line before @since.
 * @since 1.0.0
 * @param string $name
 * @return void
 */
function function_with_tags_missing_empty_line() {
}
