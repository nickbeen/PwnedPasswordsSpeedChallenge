<?php

namespace HaveIBeenPwned\PwnedPasswordsSpeedChallenge;

class Help
{
    public static function usage()
    {
        print
"USAGE:
    php ./ppsc.php [--help] [--skip-cache] [--clear-cache] [inputFile] [outputFile]

ARGUMENTS:
    [inputFile]     Newline-delimited password list to check against HaveIBeenPwned
    [outputFile]    File to store the results in csv format

OPTIONS:
    --help          Shows this help message
    --skip-cache    Don't save local cache to disk when completed, defaults to false
    --clear-cache   Clear the local cache before starting, defaults to false";
    }
}
