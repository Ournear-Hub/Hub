<?php

/**
 * Laravel built-in server router for local QA on Windows.
 * Document root must be public/ (-t public). Resolves paths from this file location
 * so the router works regardless of the shell's current working directory.
 *
 * Same rewrite behavior as vendor server.php without writing to php://stdout
 * (stdout logging can emit notices on Windows and break Inertia/XHR responses).
 */

$publicPath = realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'public');
if ($publicPath === false || ! is_file($publicPath.DIRECTORY_SEPARATOR.'index.php')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Local dev router: public/index.php not found. Start PHP with -t public from the project root.';

    return true;
}

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

if ($uri !== '/' && $uri !== '') {
    $candidate = $publicPath.$uri;
    if (is_file($candidate)) {
        return false;
    }
}

require $publicPath.DIRECTORY_SEPARATOR.'index.php';
