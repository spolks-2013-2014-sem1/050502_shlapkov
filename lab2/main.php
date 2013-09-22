<?
error_reporting(E_ALL);
set_time_limit(0);

ob_implicit_flush(); //clear output

$address = "192.168.43.12";
$port = 31445;

$s = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($s, $address, $port); //bind name socket_bind
socket_listen($s, 5); //5 - max connections

do {
  $msgsock = socket_accept($s);
  $msg = "User: desu, computer: lalka\n";
  
  socket_write($msgsock, $msg, strlen($msg));
  do {
    $msgread = socket_read($msgsock, 2048, PHP_NORMAL_READ); //input connections
    
    if ($msgread === false){
      echo "Unable to connection. Error:".socket_strerror(socket_last_error($msgsock))."\n";
      break(2);
    }
    if (!$msgread = trim($msgread)){
      continue;
    }
    if ($msgread == 'exit'){
      break;
    }
    if ($msgread == 'connection_close'){
      socket_close($msgsock);
      break 2;
    }
    $talkback = 'Ok, you write: '.$msgread."\n";
    socket_write($msgsock, $talkback, strlen($talkback));
    
    echo $talkback."\n";
  } while (true);
  socket_close($msgsock);
}while(true);

socket_close($s);

?>
