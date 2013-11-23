<?php
error_reporting(E_ALL); //see all errors
ob_implicit_flush(); //clear buffer

$BUFFER_SIZE = 1024;

$address = "localhost";
$port = $argv[1];
$mode = $argv[2];

if ($mode == "server") {
    $filename = $argv[3];
}

if ($mode == "server") {

    $rsSock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_bind($rsSock, $address, $port) or die("Error: can not bind socket"); //bind name socket_bind
    socket_listen($rsSock, 5); //5 - max connections

    $hInputFile = @fopen($filename, "r");
    $hInputFile or die("Error: no correct filename.\n");

    $rsServ = socket_accept($rsSock);
    //send filename
    $result = socket_send($rsServ, $filename."\n", strlen($filename) + 1, 0);
    if ($result === false) {
        $errormsg = socket_strerror(socket_last_error());
        echo $errormsg."\n";
    }

    $bMSG_OOB = false;  //flag sending out-of-band data
    while (!feof($hInputFile)) {
        $sData = fread($hInputFile, $BUFFER_SIZE);
        do {
            if (rand(0, 1) && ($bMSG_OOB == false)){
                echo "Send ".strlen(($sData))." bytes\n";
                socket_send($rsServ, "!", 1, MSG_OOB);
                $bMSG_OOB = true;
            }

            $result = socket_write($rsServ, $sData, $BUFFER_SIZE);

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

    //receive filename
    $filename = trim(socket_read($rsSock, $BUFFER_SIZE, PHP_NORMAL_READ));
    $hOutputFile = @fopen($filename."_receive", "w");
    $hOutputFile or die("Error: can not create file.\n");

    echo "Receiving file...\n";

    do {
        do {
            $read = $write  = NULL;
            $except = array($rsSock);
            $num_changed_sockets = socket_select($read, $write, $except, 0);
            if ($num_changed_sockets > 0){
                socket_recv($rsSock, $buf, 1, MSG_OOB);
            }

            $result = socket_read($rsSock, $BUFFER_SIZE);

            if ($result !== false){
                if ($num_changed_sockets > 0)
                    echo "Receiving ".strlen($result)." bytes.\n";
                fwrite($hOutputFile, $result);
            }
        } while ($result === false);
    } while ($result);

    echo "File is accepted!\n";

    fclose($hOutputFile);
    socket_close($rsSock);
}
?>
