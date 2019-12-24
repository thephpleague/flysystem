<?php

namespace League\Flysystem\Local {
    function rmdir(...$arguments)
    {
        if ( ! is_mocked('rmdir')) {
            return \rmdir(...$arguments);
        }

        return return_mocked_value('rmdir');
    }
}
