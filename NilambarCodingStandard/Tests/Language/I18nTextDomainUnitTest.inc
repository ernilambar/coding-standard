<?php
__( 'Hello World' ); // Bad - missing domain.
__( 'Save Changes' ); // Good - core translation, no domain needed.
_x( 'Duplicate', 'action label' ); // Good - core translation, no domain needed.

// Good examples (with domain):
__( 'Hello World', 'my-plugin' ); // Good.
__( 'Save Changes', 'my-plugin' ); // Good.
_x( 'Duplicate', 'action label', 'my-plugin' ); // Good.
