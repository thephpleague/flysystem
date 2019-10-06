<?php
namespace League\Flysystem\Util;

class Mode
{
    /**
     * Converts mode from string representation to decimal if necessary
     *
     * @param mixed $modeString
     *
     * @return int
     */
    public static function mode($modeString)
    {
        // we don't process it if it's not a string
        if (!is_string($modeString)) {
            return $modeString;
        }

        // this takes care of '755', '0755', '1755', '01755' 
        if (is_numeric($modeString)) {
            return octdec($modeString);
        }

        // this point is reached in case of something like 'drwSrwxr-T'

        // here we'll store the three usual octets for start
        $mode = 0;

        // this will hold the first octet; we'll merge it in $mode at the end
        $special = 0;

        // let's go through the string char by char
        for ($i = 0; $i < strlen($modeString); $i++)
        {
            // for each iteration we shift the mode by one bit
            // thus putting a 0 at the end
            $mode = $mode << 1;

            // we must also shift the special bits once every three chars
            // works for both drwxrwxrwt and rwxrwxrwt
            if (0 == $i % 3) {
                $special = $special << 1;
            }

            $char = $modeString[$i];

            // the special bit is set on these letters taking the place of execution bit
            // their order is fixed so it's enough to check if we got any of those
            if (in_array($char, ['S', 's', 'T', 't'])) {
                $special |= 1;
            }

            // these letters are the only that don't set the corresponding bit in mode
            if (in_array($char, ['-', 'S', 'T', 'd'])) {
                continue;
            }

            // if we reached this, set the bit on
            $mode |= 1;
        }

        // finally shift the special bits so they come first and return everything
        return $mode |= ($special << 9);
    }
}
