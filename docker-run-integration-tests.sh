#!/usr/bin/env bash

echo "=================== UNIT-TESTS ==================="

eval "docker-compose exec php ./vendor/bin/phpunit --configuration phpunit.integration.xml --colors=always --verbose --testdox"

echo "\n============================================="
