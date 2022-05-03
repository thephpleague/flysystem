<?php

namespace League\Flysystem\Local {
    function rmdir(...$arguments)
    {
        if ( ! is_mocked('rmdir')) {
            return \rmdir(...$arguments);
        }

        return return_mocked_value('rmdir');
    }

    function unlink(...$arguments)
    {
        if ( ! is_mocked('unlink')) {
            return \unlink(...$arguments);
        }

        return return_mocked_value('unlink');
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
    function time()
    {
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

    function ftp_pwd(...$arguments)
    {
        if ( ! is_mocked('ftp_pwd')) {
            return \ftp_pwd(...$arguments);
        }

        return return_mocked_value('ftp_pwd');
    }

    function ftp_fput(...$arguments)
    {
        if ( ! is_mocked('ftp_fput')) {
            return \ftp_fput(...$arguments);
        }

        return return_mocked_value('ftp_fput');
    }

    function ftp_chmod(...$arguments)
    {
        if ( ! is_mocked('ftp_chmod')) {
            return \ftp_chmod(...$arguments);
        }

        return return_mocked_value('ftp_chmod');
    }

    function ftp_mkdir(...$arguments)
    {
        if ( ! is_mocked('ftp_mkdir')) {
            return \ftp_mkdir(...$arguments);
        }

        return return_mocked_value('ftp_mkdir');
    }

    function ftp_delete(...$arguments)
    {
        if ( ! is_mocked('ftp_delete')) {
            return \ftp_delete(...$arguments);
        }

        return return_mocked_value('ftp_delete');
    }

    function ftp_rmdir(...$arguments)
    {
        if ( ! is_mocked('ftp_rmdir')) {
            return \ftp_rmdir(...$arguments);
        }

        return return_mocked_value('ftp_rmdir');
    }

    function ftp_fget(...$arguments)
    {
        if ( ! is_mocked('ftp_fget')) {
            return \ftp_fget(...$arguments);
        }

        return return_mocked_value('ftp_fget');
    }

    function ftp_rawlist(...$arguments)
    {
        if ( ! is_mocked('ftp_rawlist')) {
            return \ftp_rawlist(...$arguments);
        }

        return return_mocked_value('ftp_rawlist');
    }
}

namespace League\Flysystem\ZipArchive {
    function stream_get_contents(...$arguments)
    {
        if ( ! is_mocked('stream_get_contents')) {
            return \stream_get_contents(...$arguments);
        }

        return return_mocked_value('stream_get_contents');
    }
}
