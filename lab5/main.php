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

    if (!($rsSock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        die("Couldn't create socket: [$errorcode] $errormsg \n");
    }

    if (!socket_bind($rsSock, $address, $port)) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        die("Couldn't bind socket: [$errorcode] $errormsg \n");
    }

    $hInputFile = @fopen($filename, "r");
    $hInputFile or die("Error: no correct filename.\n");

    echo "Waiting to connect client ... \n";

    while (1){
        $r = socket_recvfrom($rsSock, $buf, 1024, 0, $address, $port);
        if ($buf == "ok") {
            echo "Connection done. Sending filename...\n";
            $result = socket_sendto($rsSock, $filename, $BUFFER_SIZE, 0, $address, $port);
            echo "Send file size...\n";
            $result = socket_sendto($rsSock, filesize($filename), $BUFFER_SIZE, 0, $address, $port);
            while (!feof($hInputFile)) {
                $sData = fread($hInputFile, $BUFFER_SIZE);
                do {
                    socket_sendto($rsSock, strlen($sData) , $BUFFER_SIZE , 0 , $address , $port);    //byte send
                    socket_sendto($rsSock, $sData , strlen($sData) , 0 , $address , $port);     //data send

                    echo "Sending ".strlen($sData)." bytes\n";

                    $r = socket_recvfrom($rsSock, $buf, 1024, 0, $address, $port);
                } while ($buf != "ok");
            }
        }
        if ($buf == "complete") {
            break;
        }
    }
    fclose($hInputFile);
    socket_close($rsSock);
}


if ($mode == "client") {

    if (!($rsSock = socket_create(AF_INET, SOCK_DGRAM, 0))){
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        die("Couldn't create socket: [$errorcode] $errormsg \n");
    }

    echo "Receiving file...\n";

    if (!socket_sendto($rsSock, "ok" , 2 , 0 , $address , $port)) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        die("Could not send data: [$errorcode] $errormsg \n");
    }

    //receive filename
    if(socket_recv ( $rsSock , $filename , $BUFFER_SIZE , MSG_WAITALL ) === FALSE) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        die("Could not receive data: [$errorcode] $errormsg \n");
    }

    $hOutputFile = @fopen($filename."_receive", "w");
    $hOutputFile or die("Error: can not create file.\n");

    //receive filesize
    if(socket_recv ( $rsSock , $filesize , $BUFFER_SIZE , MSG_WAITALL ) === FALSE) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        die("Could not receive data: [$errorcode] $errormsg \n");
    }

    $receive_bytes = 0;
    while ($receive_bytes < $filesize) {
        //recv size
        if(socket_recv ( $rsSock , $sizemsg , $BUFFER_SIZE , MSG_WAITALL ) === FALSE) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            echo "Could not receive data: [$errorcode] $errormsg \n";
        }
        //recv data
        if(socket_recv ( $rsSock , $reply , $BUFFER_SIZE , MSG_WAITALL ) === FALSE) {
            $errorcode = socket_last_error();
            $errormsg = socket_strerror($errorcode);
            echo "Could not receive data: [$errorcode] $errormsg \n";
        }

        if ($sizemsg != strlen($reply)) {
            $msgsend = "error";
            echo "Error. Repeat.";
        } else {
            $msgsend = "ok";
            $receive_bytes += strlen($reply);
            echo "Receive ".strlen($reply)." bytes\n";
            fwrite($hOutputFile, $reply);
        }
        socket_sendto($rsSock, $msgsend , $BUFFER_SIZE , 0 , $address , $port);
    }

    echo "File is accepted!\n";
    socket_sendto($rsSock, "complete" , $BUFFER_SIZE , 0 , $address , $port);

    fclose($hOutputFile);
    socket_close($rsSock);
}
?>
