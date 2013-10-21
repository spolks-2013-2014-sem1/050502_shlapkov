<?
error_reporting(E_ALL);
set_time_limit(0);

ob_implicit_flush(); //clear output

echo "client or server?\n";
$mode = trim(fgets(STDIN));

$address = "localhost";
$port = 25558;

if ($mode == "server") {
  $rsSock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
  socket_bind($rsSock, $address, $port); //bind name socket_bind
  socket_listen($rsSock, 5); //5 - max connections
  $hInputFile = fopen("inputfile", "r");
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
  
  $hOutputFile = fopen("outputfile", "w");
  
  echo "Receiving file...\n";
  
  do {
    $result = socket_recv($rsSock, $sData, 2048, MSG_WAITALL);
    if ($result !== false){
      fwrite($hOutputFile, $sData);
    }
  } while ($result === false);
  
  echo "File is accepted!\n"; 
  
  fclose($hOutputFile);
  socket_close($rsSock);
}

?>
