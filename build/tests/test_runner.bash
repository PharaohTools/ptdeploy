#!/bin/bash -ex
git checkout -b spike_phpcs || true
mkdir -p tests/reports/phpcs
phpcbf src/ --error-severity=1 --warning-severity=8 --ignore="*/Libraries/*,*.tpl.php" --report-full --standard=PSR2 > tests/reports/phpcs/cbfout
phpcs src/ --error-severity=1 --warning-severity=8 --ignore="*/Libraries/*,*.tpl.php" --report-full --standard=PSR2 > tests/reports/phpcs/csout
exit 0