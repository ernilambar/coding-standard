<?php

// Comment without TODO.
$someVar = "This is fine";

/*
   This is a
   multiline comment
*/

// TODO: Need to fix this later! // violation
$anotherVar = 123;

/**
 * This is a docblock.
 *
 * @param int $param1
 * @return void
 */
function myFunction($param1)
{
    // TODO: This is broken! // violation
    echo $param1;
}

//  todo: lowercase should be detected too // violation
