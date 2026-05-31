<?php
/**
 * PHPUnit bootstrap for Algonquian Education Center.
 *
 * Configure WP_TESTS_DIR to point to the WordPress test suite before running.
 */

$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "WordPress test suite not found. Set WP_TESTS_DIR.\n";
    exit(1);
}

require_once $_tests_dir . '/includes/functions.php';

function _algq_education_manually_load_plugin() {
    require dirname(__DIR__) . '/algq-education-center.php';
}

tests_add_filter('muplugins_loaded', '_algq_education_manually_load_plugin');

require $_tests_dir . '/includes/bootstrap.php';
