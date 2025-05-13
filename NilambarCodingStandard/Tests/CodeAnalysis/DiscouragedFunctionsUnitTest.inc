<?php
load_plugin_textdomain( 'sample-textdomain', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); // Warning.

$obj->load_plugin_textdomain(); // OK, not the global function.
MyClass::load_plugin_textdomain(); // OK, not the global function.
$obj?->load_plugin_textdomain(); // OK, not the global function.

\load_plugin_textdomain(); // Warning.
use function load_plugin_textdomain; // Warning.
use function load_plugin_textdomain as myFunction; // Warning.
use function someOtherFunction as load_plugin_textdomain; // OK, alias, not our target.
