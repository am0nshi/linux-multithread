<?php
include '/var/www/micro/whitelist-micro/vendor/autoload.php';

class DaemonWorker {

    private $pipein;
    private $pipeout;
    private $filein;
    private $fileout;

//    private $lapNo = 0;
    private $framework;

    private $text;

    function __construct()
    {
        $this->text = '#'.getmypid().': '.str_pad('',900,rand(0,9)).PHP_EOL;

//        $this->framework = $this->registerFramework();

        var_dump('creating worker daemon '.getmypid());
        $this->filein = 'run/'.getmypid().'.pipein';
        if(!posix_mkfifo($this->filein, 0700)){
            die('Error: Could not create the named IN pipe: '. posix_strerror(posix_errno()) . " at #".getmypid()." with ppid #".posix_getppid()."\n");
        }
        $this->fileout = 'run/'.getmypid().'.pipeout';
        if(!posix_mkfifo($this->fileout, 0700)){
            die('Error: Could not create the named OUT pipe: '. posix_strerror(posix_errno()) . " at #".getmypid()." with ppid #".posix_getppid()."\n");
        }

        $this->pipein = fopen($this->filein, 'r+');
        stream_set_blocking($this->pipein, false);
        $this->pipeout = fopen($this->fileout, 'w+');
        stream_set_blocking($this->pipeout, false);

        if(!$this->pipein || !$this->pipeout){
            die('Error: Could not open the named pipe: '. posix_strerror(posix_errno()) . " at #".getmypid(). "\n");
        }
        var_dump('Pipe created at #'.getmypid());

    }

    public function serve(){
        var_dump('Serving as worker as #'.getmypid());
//        var_dump($this->framework);

        while (($message = fgets($this->pipein, 4096)) !== false) {
            var_dump('<<<<<<<<<<<<<GOTCHAAAAAAAAAAAAAAAAAAAAAAAAAA!!!!!!!!!!!!!!!!!!!!!!vvvvvv');
            var_dump($message);
            pcntl_signal_dispatch();
            return $this->proceedFramework($message);
        }
        usleep(750000);

//        while(1){
//            fgets($pipe_read)
//        }
//
//        for($i=0;$i<4;$i++){
//            var_dump('Serving as worker as #'.getmypid().' at loop num '.$i);
////            usleep(300000);
//            pcntl_signal_dispatch();
//        }
////        exit();
//        die();
    }

    function __destruct()
    {
        fclose($this->pipein);
        unlink($this->filein);
        fclose($this->pipeout);
        unlink($this->fileout);
        var_dump('calling desctructor in daemon '.getmypid());
    }

    public function registerFramework(){
        try {
            (new \Dotenv\Dotenv('/var/www/micro/whitelist-micro/'))->load();
        } catch (\Dotenv\Exception\InvalidPathException $e) {
            //
            var_dump('Failed to run DOT.ENV MODULE!');
        }

        $app = new Laravel\Lumen\Application(
            realpath('/var/www/micro/whitelist-micro/')
        );
        $app->withEloquent();
        $app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
            require '/var/www/micro/whitelist-micro/app/Http/routes.php';
        });
        return $app;
    }

    public function proceedFramework($request){
        $response = $this->framework->handle(\App\Http\Request::fromString($request));
        return $response;
    }

    public function closeFramework(){
        $this->framework->flush();
    }

}