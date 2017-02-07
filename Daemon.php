<?php

class Daemon {

    protected $masterPid, $pid;
    protected $daemonsCount;

    protected $daemon;
    protected $working=1;

    protected $childs = [];

    function __construct($count)
    {
        $this->masterPid = posix_getpid();
        $this->initSharedComponents();
        $this->daemonsCount = $count;

        ignore_user_abort(false);

        for($i=0;$i < $this->daemonsCount; $i++){
            $this->fork();
        }
        $this->detectRole();

        /*
        for($i=0;$i < $this->daemonsCount; $i++){
            $pid = pcntl_fork();
            if($pid){//host
                $this->childs[] = $pid;
            } else {//child
                if(posix_getppid() != $this->masterPid){ exit(); }//prevent recursion
            }
        }

        if($this->isHost()){
            $this->registerAsHost();
        } else {
            $this->registerAsDaemon();
        }
        $this->registerSignalsCallback();
        $this->run();*/
    }

    public function fork(){
        $pid = pcntl_fork();
        if($pid){//host
            $this->childs[] = $pid;
        } else {//child
            if(posix_getppid() != $this->masterPid){ exit(); }//prevent recursion
        }
    }

    public function detectRole(){
        if($this->isHost()){
            $this->registerAsHost();
        } else {
            $this->registerAsDaemon();
        }
        $this->registerSignalsCallback();
        $this->run();
    }

    /**
     * Share nothing ideology
     * We can use it, but can't close it
     */
    protected function initSharedComponents(){
        //TODO:: db, opened files - here
    }

    protected function registerAsDaemon(){
        $this->daemon = new DaemonWorker();
        unset($this->childs);//unregister for childrens
    }

    protected function registerAsHost(){
        $this->daemon = new DaemonServer();
    }

    private function isHost(){
        return $this->masterPid == getmypid();
    }

    function __destruct()
    {
//        var_dump('calling desctructor in daemon '.$this->pid);
//        unlink($this->pid.'.pid');
    }

    protected function run(){
        while($this->working){
            pcntl_signal_dispatch();

            if($this->isHost()){
                $recreateCount=0;
                foreach($this->childs as $position=>$pid){
                    /**
                     * someone die, and we begin re-create worker process
                     */
                    if(pcntl_waitpid($pid,$status='',WNOHANG) == -1){
                        unset($this->childs[$position]);
                        var_dump('Childrens count in isHost='.(int)$this->isHost().' : '.count($this->childs).'. Died process - #'.$pid);
                        $recreateCount++;
                        /*$this->fork();
    //                    $this->detectRole();
                        if(!$this->isHost()){
                            $this->registerAsDaemon();
                        } else {
                            exit();
                        }*/
                    }
                }
                for($i=0;$i<$recreateCount;$i++){
                    $this->fork();
                    if(posix_getppid() != $this->masterPid && getmypid() != $this->masterPid) {
                        exit();//prevent recursion
                    } else if(!$this->isHost()){
                        var_dump('Re-creating closed process with new pid #'.getmypid());
                        $this->registerAsDaemon();
                        break;
                    }
                }
                if($this->isHost()){
                    $this->daemon->serve($this->childs);
                }
            } else {
                $this->daemon->serve();
            }
        }
    }

    protected function registerSignalsCallback(){
        pcntl_signal(SIGTERM, [$this,"signalHandler"]);
        pcntl_signal(SIGINT, [$this,"signalHandler"]);
    }

//    Обработчик
    function signalHandler($signo) {
        var_dump('Getting signal handler with $signo = '.$signo);
        switch($signo) {
            case SIGTERM: {
                $this->working = false;
                var_dump('Closing SIGTERM daemon #'.getmypid());
                exit(0);
            }
            case SIGINT: { //ctrl+C - break the process
                $this->working = false;
                var_dump('Closing SIGINT daemon #'.getmypid());
                exit(0);
            }
            case SIGCHLD: { //1 child change state
//                $this->working = false;
//                $this->__destruct();
//                foreach($this->childs as $pid){
//                    pcntl_waitpid($pid);
//                $res = pcntl_waitpid($pid, $status, WNOHANG);
//                // If the process has already exited
//                if($res == -1 || $res > 0){
//                    unset($childs[$key]);
//                }
//                }

                var_dump('Get SIGCHLD in #'.getmypid());
                exit(0);
            }
            default: {
                //все остальные сигналы
            }
        }
    }

}