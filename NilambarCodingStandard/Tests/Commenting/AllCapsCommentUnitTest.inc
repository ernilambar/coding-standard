<?php

// SET VARIABLE.
$someVar = "Some content";

// IMPORTANT: 123 STEPS.
$anotherVar = 123;

// Fetch data.
$data = fetch();

// if ( false === \as_has_scheduled_action( 'update_action' ) ) {
//   \as_schedule_recurring_action( strtotime( 'now' ), DAY_IN_SECONDS, 'update_action', [], '', true );
// }

// RETURNS ONE VALUE.
function test_one() {
}

// CLASS ONE.
class Test_One{
	// VAR TEST.
	protects var $test_var;

	// RETURNS ONE VALUE.
	public function test_one() {
	}
}

/*
 * MULTI-LINE COMMENT IN ALL CAPS.
 * THIS SHOULD BE DETECTED.
 */
function test_multiline() {
}

/**
 * DOCBLOCK COMMENT IN ALL CAPS.
 * THIS SHOULD ALSO BE DETECTED.
 */
function test_docblock() {
}

/*
 * Mixed case comment.
 * This should NOT be detected.
 */
function test_mixed_case() {
}

/**
 * DocBlock with mixed case.
 * This should NOT be detected.
 */
function test_docblock_mixed() {
}

// Single line with numbers and symbols: IMPORTANT: 123 STEPS.
$test = 'value';

// Single line with mixed content: This is OK but THIS PART SHOULD BE DETECTED.
$another = 'test';

/*
 * Multi-line with mixed content:
 * This line is OK.
 * BUT THIS LINE SHOULD BE DETECTED.
 * And this line is OK again.
 */
function test_mixed_multiline() {
}

/**
 * DocBlock with mixed content:
 * This is normal.
 * BUT THIS SHOULD BE DETECTED.
 * And this is normal again.
 */
function test_docblock_mixed_content() {
}
