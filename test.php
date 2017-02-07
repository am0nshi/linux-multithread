<?php
include 'vendor/autoload.php';
//include "DaemonWorkerStatus.php.php";
include "DaemonCommunication.php";
include "DaemonServer.php";
include "DaemonWorker.php";
//include "DaemonUnixSocket.php";
include "Daemon.php";

$daemon = new Daemon(8);