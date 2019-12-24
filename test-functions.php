<?php

declare(strict_types=1);

function return_mocked_value(string $name)
{
    return array_shift($GLOBALS['__FM:RETURNS:' . $name]);
}

function reset_function_mocks()
{
    foreach ($GLOBALS as $name) {
        if (is_string($name) && substr($name, 0, 5) === '__FM:') {
            unset($GLOBALS[$name]);
        }
    }
}

function mock_function(string $name, ...$returns)
{
    $GLOBALS['__FM:FUNC_IS_MOCKED:' . $name] = 'yes';
    $GLOBALS['__FM:RETURNS:' . $name] = $returns;
}

function is_mocked(string $name)
{
    return ($GLOBALS['__FM:FUNC_IS_MOCKED:' . $name] ?? 'no') === 'yes';
}
