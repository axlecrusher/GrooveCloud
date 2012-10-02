<?php

function randBytes($length) 
{
	$bytes;

    $fp = @fopen('/dev/urandom','rb');
    if ($fp !== FALSE) {
        $bytes = @fread($fp,$length);
        @fclose($fp);
    }

    return $bytes;
}

function HashData($d)
{
	return base64_encode( hash("sha256", $d . $_SESSION['salt'], true) );
}

function CheckHash($d,$hash)
{
	if (strcmp($hash, base64_encode( hash("sha256", $d . $_SESSION['salt'], true) ) ) == 0)
	{
		return true;
	}
	return false;
}

/* Copyright (c) 2012 Joshua Allen

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

?>
