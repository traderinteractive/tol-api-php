#!/usr/bin/env php
<?php
$returnStatus = null;
passthru('docker build --tag=tol-api-php ' . __DIR__, $returnStatus);
if ($returnStatus !== 0) {
    exit(1);
}

$links = array(createLink('mongo'), createLink('redis'));

passthru(
    'docker run --name tol-api-php-build --tty --rm --volume ' . __DIR__ . ':/code ' . implode(' ', $links) . ' tol-api-php',
    $returnStatus
);
if ($returnStatus !== 0) {
    exit(1);
}

function createLink($name)
{
    $returnStatus = null;
    passthru("docker inspect --format='{{.Name}}' tol-api-php-{$name}", $returnStatus);
    if ($returnStatus !== 0) {
        passthru("docker run --name tol-api-php-{$name} --detach {$name}", $returnStatus);
        if ($returnStatus !== 0) {
            exit(1);
        }
    }

    return "--link tol-api-php-{$name}:{$name}";
}
