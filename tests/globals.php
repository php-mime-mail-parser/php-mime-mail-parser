<?php

namespace {
    // This allow us to configure the behavior of the "global mock"
    $mockTmpFile = false;
}

namespace PhpMimeMailParser {
    function tmpfile()
    {
        global $mockTmpFile;
        if (isset($mockTmpFile) && $mockTmpFile === true) {
            return false;
        } else {
            return call_user_func_array('\tmpfile', func_get_args());
        }
    }
}
