<?php

wp_enqueue_script( 'hello-world', 'https://hello-world/jquery.min.js', [], '1.0.0' ); // bad
wp_enqueue_script( 'hello-world', 'https://hello-world/jquery.datepicker.min.js', [], '1.0.0' ); // good
wp_enqueue_script( 'js-hoverintent', 'https://hello-world/jquery.hoverintent.js' ); // bad
