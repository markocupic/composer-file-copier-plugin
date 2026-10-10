#!/bin/bash
echo "::error::VERSION $(composer show composer/composer 2>/dev/null | grep versions)"
F=--display-notices; vendor/bin/phpunit --version | grep -q 'PHPUnit 9' && F=
vendor/bin/phpunit $F > /tmp/t.log 2>&1; R=$?
echo "::error::TESTS $(tail -c 3000 /tmp/t.log | tr '\n' '|')"
exit $R
