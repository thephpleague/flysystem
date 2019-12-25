<?php

namespace League\Flysystem\Local {
    function rmdir(...$arguments)
    {
        if ( ! is_mocked('rmdir')) {
            return \rmdir(...$arguments);
        }

        return return_mocked_value('rmdir');
    }

    function filemtime(...$arguments)
    {
        if ( ! is_mocked('filemtime')) {
            return \filemtime(...$arguments);
        }

        return return_mocked_value('filemtime');
    }

    function filesize(...$arguments)
    {
        if ( ! is_mocked('filesize')) {
            return \filesize(...$arguments);
        }

        return return_mocked_value('filesize');
    }
}
