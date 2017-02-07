<?php

class DaemonCommunication {

    protected $socket = null;

    function __construct($masterPid)
    {
        //TODO:: open pipe

    }

    function __destruct()
    {
        //TODO:: Close pipe
        // TODO: Implement __destruct() method.
    }
}