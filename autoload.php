<?php

spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'Elementor_Pay_Addons';
    $prefixNamespace = 'epa_';

    // base directory for the namespace prefix
    $base_dir = EPA_ADDONS_PATH . '/includes/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = strtolower(substr($class, $len));
    $relative_class = str_replace($prefixNamespace, '', $relative_class);
    $relative_class = str_replace('_', '-', $relative_class);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// if ( ! function_exists('it_stripe_express_require_handler')) {
//   function it_stripe_express_require_handler($dir) {
//     $dh = opendir($dir);
//     while (($filename = readdir($dh)) != null) {
//       if ($filename == '.' || $filename == '..') continue;
//       $_dir = $dir . $filename;
//       if (is_file($_dir)) {
//         $extension = pathinfo($_dir, PATHINFO_EXTENSION);
//         if (strtolower($extension) == 'php') {
//           require_once $_dir;
//         }
//       } else if (is_dir($_dir)) {
//         it_stripe_express_require_handler($_dir . '/');
//       }
//     }
//     closedir($dh);
//   }
// }

// if (function_exists('it_stripe_express_require_handler')) {
//   it_stripe_express_require_handler(EPA_ADDONS_PATH . '/includes/');
// }
