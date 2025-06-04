#!/bin/bash
# Run PHPUnit tests for the form_reference_field module.

set -e

MODULE_PATH="web/modules/form_reference_field"
TEST_PATH="$MODULE_PATH/tests/src/Kernel"
PHPUNIT_CONFIG="phpunit.xml"
BASE_PATH="/app" # It usually gets executed inside a container

MODULE_PATH="$BASE_PATH/$MODULE_PATH"
TEST_PATH="$BASE_PATH/$TEST_PATH"
PHPUNIT_CONFIG="$BASE_PATH/$PHPUNIT_CONFIG"

echo "Using PHPUnit configuration file: $PHPUNIT_CONFIG"
echo "Running tests in: $TEST_PATH"
echo "Module path: $MODULE_PATH"

# Run PHPUnit for this module's kernel tests (without --no-deprecation-warnings, which is not supported)
php $BASE_PATH/vendor/bin/phpunit --configuration "$PHPUNIT_CONFIG" --testsuite Kernel --filter FormReferenceWidgetTest "$TEST_PATH"
