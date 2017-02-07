<?php

class DaemonServer {

    protected $pids = [];

    protected $server = null;
    protected $pool = [];
    protected $workerPipes = [];

    function __construct()
    {
        unlink('logs.txt');
        file_put_contents('server.pid', getmypid());
        //create socket listener
        //create childs list
        var_dump('creating server daemon '.getmypid());

//        $this->tcpServer = (new \OVM\Unicast\SocketServer(80))->init();
//        $this->pool[0] = $this->tcpServer->getStream();

    }

    /**
     * Register child in workers scope
     * @param $pid
     */
//    public function registerChild($id){
//        $this->pids[] = new DaemonWorkerStatus($id);
//    }
//
//    public function killChild($id){
//
//    }

    public function serve($childrens){
        $request = "GET /whitelist?var1=val1 HTTP/1.1
Host: www.nowhere123.com
Accept: image/gif, image/jpeg, */*
Accept-Language: en-us
Accept-Encoding: gzip, deflate
User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)

<html><body><h1>It works!</h1></body></html>
";


        var_dump('REFRESHING PIPES #'.getmypid());
        $this->refreshPipes($childrens);

        $pid = $childrens[array_rand($childrens)];
        var_dump('>>>>>>>>>>Sending data from server to children #'.$pid);
//        $this->sendToPipe($pid,'WOOOOHAAAA!!!!! {}{}{} 8====3 {}{}{}');
        $this->sendToPipe($pid,$request);


        var_dump('Serving as server '.getmypid().' with childrens : '.json_encode($childrens));
//        usleep(750000);
        usleep(750000);

        pcntl_signal_dispatch();
    }


    function __destruct()
    {
        unlink('server.pid');
        foreach($this->pool as $pid=>$pipe){
            $this->closePipe($pid);
        }
        // TODO: Implement __destruct() method.
    }


    private function refreshPipes($childrens){
        $died = [];
        foreach($childrens as $pid){
            if(isset($this->pool[$pid])){continue;}//already exist
            $this->workerPipes[$pid] = fopen('run/'.$pid.'.pipein', 'w+');
            $this->pool[$pid] = fopen('run/'.$pid.'.pipeout', 'r+');
            stream_set_blocking($this->pool[$pid], false);
            stream_set_blocking($this->workerPipes[$pid], false);
        }
        if(array_diff_key(array_flip($childrens),$this->pool)){
            $died = array_diff_key(array_flip($childrens),$this->pool);
            $died = array_flip($died);
            var_dump('Found died process in server childrens:  '.json_encode($died));
//            var_dump('Found died process in server childrens:  '.json_encode(array_diff_key(array_flip($childrens),$this->pool)));
        }
        foreach($died as $pid){
            $this->closePipe($pid);
        }
//        stream_set_blocking($pipe, false);
//        $pipe = fopen('run/'.$pid.'.pipein', 'w+');

    }

    private function sendToPipe($pid,$message){
//        return;
//        stream_set_blocking($pipe, false);
        fputs($this->workerPipes[$pid], $message, 4096);
    }
    private function closePipe($pid){
        fclose($this->pool[$pid]);
        fclose($this->workerPipes[$pid]);
    }
}