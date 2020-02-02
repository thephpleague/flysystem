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

namespace League\Flysystem\InMemory {
    function time() {
        if ( ! is_mocked('time')) {
            return \time();
        }

        return return_mocked_value('time');
    }
}

namespace League\Flysystem\Ftp {
    function ftp_raw(...$arguments)
    {
        if ( ! is_mocked('ftp_raw')) {
            return \ftp_raw(...$arguments);
        }

        return return_mocked_value('ftp_raw');
    }

    function ftp_set_option(...$arguments)
    {
        if ( ! is_mocked('ftp_set_option')) {
            return \ftp_set_option(...$arguments);
        }

        return return_mocked_value('ftp_set_option');
    }

    function ftp_pasv(...$arguments)
    {
        if ( ! is_mocked('ftp_pasv')) {
            return \ftp_pasv(...$arguments);
        }

        return return_mocked_value('ftp_pasv');
    }
}
