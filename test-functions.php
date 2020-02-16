<?php

declare(strict_types=1);

function return_mocked_value(string $name)
{
    return array_shift($_ENV['__FM:RETURNS:' . $name]);
}

function reset_function_mocks()
{
    foreach (array_keys($_ENV) as $name) {
        if (is_string($name) && substr($name, 0, 5) === '__FM:') {
            unset($_ENV[$name]);
        }
    }
}

function mock_function(string $name, ...$returns)
{
    $_ENV['__FM:FUNC_IS_MOCKED:' . $name] = 'yes';
    $_ENV['__FM:RETURNS:' . $name] = $returns;
}

function is_mocked(string $name)
{
    return ($_ENV['__FM:FUNC_IS_MOCKED:' . $name] ?? 'no') === 'yes';
}

function stream_with_contents(string $contents)
{
    $stream = fopen('php://temp', 'w+b');
    fwrite($stream, $contents);
    rewind($stream);

    return $stream;
}

function delete_directory(string $dir): void
{
    if ( ! is_dir($dir)) {
        return;
    }

    foreach ((array) scandir($dir) as $file) {
        if ('.' === $file || '..' === $file) {
            continue;
        }
        if (is_dir("$dir/$file")) {
            delete_directory("$dir/$file");
        } else {
            unlink("$dir/$file");
        }
    }
    rmdir($dir);
}
