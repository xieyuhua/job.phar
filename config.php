<?php
use think\Db;

//tsx 注册自动加载函数
spl_autoload_register(function ($name){
    $name = str_replace('\\',DIRECTORY_SEPARATOR, $name);
    $namespace = explode('/', $name);
    switch ($namespace[0]) {
        case 'app':
          	$name = $name;
            break; 
        default:
            $name = $name;
            break;
    }
    $file = $name.'.php';
    if(is_file($file)){
        @include_once $file;
    }else{
     //   echo '++'.$file.PHP_EOL;
    }
});

$db = [
    'type'            => 'Oracle',
    // 服务器地址
    'hostname'        => '192.168.9.6',
    // 数据库名
    'database'        => 'hydee',
    // 用户名
    'username'        => 'H51',
    // 密码
    'password'        => 'hoft',
    // 端口
    'hostport'        => '1521',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          =>  [\PDO::ATTR_CASE   => \PDO::CASE_LOWER],
     // 数据库调试模式
    //  'debug'           => true,
    
    // 数据库编码默认采用utf8
    'charset'         => 'AL32UTF8',
     // 是否需要断线重连
    'break_reconnect' => true,
    // 数据库表前缀
    'prefix'          => '',
];
Db::setConfig($db);

// 'debug'  => true,
Db::listen(function ($sql, $time, $master) {
    $line = sprintf(
        "[%s] conn=%s master=%s time=%.3fs sql=%s\n",
        date('Y-m-d H:i:s'),
        Db::getConfig('default'),
        $master ? 'Y' : 'N',
        $time,
        $sql
    );
    file_put_contents('sql.log', $line, FILE_APPEND);
    // 慢 SQL
    if ($time > 0.3) {
        file_put_contents('slow.sql.log', $line, FILE_APPEND);
    }
});


// 创建进程
function createSingletonDaemon($processName, $callback, $options = []) {
    $defaults = [
        'pid_dir' => 'log',
        'log_dir' => 'log',
        'umask' => 0,
    ];
    $options = array_merge($defaults, $options);
    $pidFile = $options['pid_dir'] . '/' . $processName . '.pid';
    $lockFile = $pidFile . '.lock';
    // 使用文件锁避免并发启动
    $lockFp = fopen($lockFile, 'w+');
    if (!$lockFp) {
        throw new Exception("无法创建锁文件: {$lockFile}");
    }
    if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
        fclose($lockFp);
        throw new Exception("另一个启动进程正在运行");
    }
    // 检查现有进程
    if (file_exists($pidFile)) {
        $pid = intval(trim(file_get_contents($pidFile)));
        // 重新启动
        switch ($options['cmd']) {
            case 'stop':
                // 发送SIGTERM（优雅终止）
                if (!posix_kill($pid, SIGTERM)) {
                    flock($lockFp, LOCK_UN);
                    fclose($lockFp);
                    echo "进程 '{$processName}' 关进程闭 (PID: {$pid})".PHP_EOL;
                }
                break;
            case 'restart':
                // 发送SIGTERM（优雅终止）
                if (!posix_kill($pid, SIGTERM)) {
                    flock($lockFp, LOCK_UN);
                    fclose($lockFp);
                    echo "进程 '{$processName}' 关进程闭 (PID: {$pid})".PHP_EOL;
                }
                break;
            default:
                if ($pid > 0 && posix_kill($pid, 0)) {
                    flock($lockFp, LOCK_UN);
                    fclose($lockFp);
                    throw new Exception("进程 '{$processName}' 已在运行 (PID: {$pid})");
                }
                break;
        }
        // 清理旧的 PID 文件
        unlink($pidFile);
    }
    // 创建守护进程
    $process = new Swoole\Process(function () use ($processName, $pidFile, $callback, $options) {
        // 子进程继续执行
        cli_set_process_title($processName);
        file_put_contents($pidFile, getmypid());
        // 执行任务
        if (is_callable($callback)) {
            $callback();
        }
        Swoole\Event::wait();
    }, false, false);
    $process->start();
    // 释放锁
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
    unlink($lockFile);
    
    return intval(trim(file_get_contents($pidFile)));
}

// 延时队列处理
try {
    $pid = createSingletonDaemon('my_service', function () {
        // redis  客户端
        $redisdb = new \Redis();
        $redisdb->connect('127.0.0.1', 6379); 
        $redisdb->auth('123456');
        
        // $data_push['uuid']  = uniqid();
        // $data_push['topic'] = 'erpData';
        // $data_push['jobClass'] = "\\queue\\ErpData";
        // $data_push['jobMethod'] = 'setCache';
        
        // $redisdb->rpush("erpData", json_encode($data_push));
        // $redisdb->zAdd('delay_queue', time(), json_encode($data_push));
        
        
        // 定时任务
        Swoole\Timer::tick(1000, function () use($redisdb) {
            // 获取到期任务
            $max = 10;
            $items = $redisdb->zRangeByScore('delay_queue', 0, time(), [
                'withscores' => true,
                'limit'      => [0, $max]
            ]);
            if ($items) {
                foreach ($items as $member => $score) {
                    $jobData = json_decode($member, true);
                    if($jobData['topic']??''){
                        // 写入队列
                        $redisdb->rpush($jobData['topic'], $member);
                    }
                    // 从延时队列中移除
                    $redisdb->zRem("delay_queue", $member);
                }
            }
            
            // $total = $redisdb->zCard($key);
            // $ready = $redisdb->zCount($key, 0, time() );
            // $list = [
            //     'total' => $total,
            //     'ready' => $ready,
            //     'delayed' => $total - $ready
            // ];
            // var_dump($list);
        });
        Swoole\Event::wait();
    }, ['cmd'=> $argv[1] ]);
    echo "守护进程启动成功，PID: {$pid}\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}


return $config = [
    //项目/系统标识
    'system'            => 'erpData-swoole-jobs',
    //log目录
    'logPath'            => __DIR__ . '/log',
    'logSaveFileApp'     => 'application.log', //默认log存储名字
    'logSaveFileWorker'  => 'crontab.log', // 进程启动相关log存储名字
    'pidPath'            => __DIR__ . '/log',
    'sleep'              => 2, // 队列没消息时，暂停秒数
    'queueMaxNum'        => 10, // 队列达到一定长度，启动动态子进程个数发和送消息提醒
    'maxPopNum'          => 50, //子进程最多执行任务数，达到这个数量之后，自动退出
    'excuteTime'         => 600, // 子进程最长执行时间，防止内存泄漏，后自动退出，，，
    /*
     *staticWorker Delebai7 worker id: 0, pid: 20641 is done!!! popNum: 0, Timing: 600.40233206749 
     */
    'queueTickTimer'     => 1000 * 10, //一定时间间隔（毫秒）检查队列长度;默认10秒钟
    'messageTickTimer'   => 1000 * 180, //一定时间间隔（毫秒）发送消息提醒;默认3分钟
    'processName'        => 'erpData-swoole', // 设置进程名, 方便管理, 默认值 swooleTopicQueue
    'eachJobExit'        => false, // true 开启； false 关闭；每个job执行完之后，主动exit,防止业务代码出现（正常不需要开启）

    //job任务相关
    'job'         => [
        //job相关属性
        'profile'=> [
            'maxTime'=> 120, //单个job最大执行时间
            'minTime'=> 0.0001, //单个job最少执行时间
        ],
        //每一个任务(job)的进程数设置
        'topics'  => [
            ['name'=> 'erpData', 'workerMinNum'=>1, 'workerMaxNum'=>1, 'queueMaxNum'=>1000],
        ],
        //任务缓存
        'queue'   => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'password'=> '123456',
        ]
   ],
   'message'=> [
        'author'  => 'erp数据缓存',
        'token'   =>  'bd1f85b53462db29aa35f7233ac3d2a2737d3c9b491fde661fed1f5eb7124048',
   ],
];
