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

?>
