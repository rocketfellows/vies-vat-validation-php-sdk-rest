#!/usr/bin/env bash

sh ./docker-run-phpcs.sh && \
sh ./docker-run-phpstan.sh && \
sh ./docker-run-integration-tests.sh && \
sh ./docker-run-unit-tests.sh
