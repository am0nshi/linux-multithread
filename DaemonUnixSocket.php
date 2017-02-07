<?php

class DaemonUnixSocket {

    protected $socket = null;
    protected $pid;

    function __construct($pid)
    {
        //TODO:: open pipe
        $this->socket = socket_create(AF_UNIX,SOCK_STREAM,SOL_TCP);
        $this->pid = $pid;
        file_put_contents('run/'.$this->pid.'.sock',"");
        socket_bind($this->socket, 'run/'.$this->pid.'.sock');
    }

    function __destruct()
    {
        //TODO:: Close pipe
        // TODO: Implement __destruct() method.
        socket_close($this->socket);
        unlink('run/'.$this->pid.'.sock');
    }
}