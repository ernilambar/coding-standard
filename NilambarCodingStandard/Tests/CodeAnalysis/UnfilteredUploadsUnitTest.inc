<?php

define( 'ALLOW_UNFILTERED_UPLOADS', true ); // violation
define( "ALLOW_UNFILTERED_UPLOADS", 1 ); // violation

if ( ! defined( 'ALLOW_UNFILTERED_UPLOADS' ) ) {
	define( 'ALLOW_UNFILTERED_UPLOADS', true ); // violation
}

define("ALLOW_UNFILTERED_UPLOADS",1); // violation
