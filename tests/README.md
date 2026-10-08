# PHP integration tests

These tests use WordPress's standard PHPUnit test library and the real Tachyon plugin. Install a WordPress test environment with a **disposable test database**, PHPUnit and the WordPress-required PHPUnit polyfills, then run:

```sh
WP_TESTS_DIR=/path/to/wordpress-tests-lib \
TACHYON_PLUGIN_DIR=/path/to/tachyon-plugin \
phpunit -c phpunit.xml.dist
```

The WordPress test library's `wp-tests-config.php` must point to that disposable database and a compatible WordPress installation. If using the `wp-phpunit/wp-phpunit` package, set `WP_PHPUNIT__TESTS_CONFIG` to your test configuration file. Set `WP_TESTS_PHPUNIT_POLYFILLS_PATH` in that configuration if the polyfills are not already discoverable.

The suite checks API output and crop/filter preservation; it does not require a running Tachyon image server. Actual image delivery and consuming plugins such as Yoast need separate integration verification.
