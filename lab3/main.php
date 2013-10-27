<?
error_reporting(E_ALL);
set_time_limit(0);

ob_implicit_flush(); //clear output

$address = "localhost";
$port = trim($argv[1]);
$mode = trim($argv[2]);
$filename = trim($argv[3]);
if (empty($filename)) die("No correct filename\n");

if ($mode == "server") {
    $rsSock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_bind($rsSock, $address, $port); //bind name socket_bind
    socket_listen($rsSock, 5); //5 - max connections
    $hInputFile = fopen($filename, "r");
    $rsServ = socket_accept($rsSock);

    while (!feof($hInputFile)) {
        $sData = fread($hInputFile, 2048);
        do {
            $result = socket_write($rsServ, $sData, strlen($sData));
            if ($result === false) {
                $errormsg = socket_strerror(socket_last_error());
                echo $errormsg."\n";
            }
        }
        while ($result === false);
    }

    socket_close($rsServ);
    fclose($hInputFile);
    socket_close($rsSock);
}

if ($mode == "client") {
    $rsSock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    $rsClient = socket_connect($rsSock, $address, $port);

    $hOutputFile = fopen($filename, "w");

    echo "Receiving file...\n";

    do {
        do {
            $result = socket_read($rsSock, 2048, PHP_BINARY_READ);
            if ($result !== false){
                fwrite($hOutputFile, $result);
            }
        } while ($result === false);
    } while ($result);

    echo "File is accepted!\n";

    fclose($hOutputFile);
    socket_close($rsSock);
}

?>
