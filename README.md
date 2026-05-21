# job.phar

想要启动 Phar 文件打包功能，首先要在 php.ini 中配置 phar.readonly = Off

```
//打包
<?php
$fileName = __DIR__ . '/test/test.phar';
if(is_file($fileName))
{
    unlink($fileName);
}
$phar = new Phar($fileName);
$phar->stopBuffering();
$phar->buildFromDirectory(__DIR__ . '/src');



//解包
<?php
$fileName = __DIR__ . '/test.phar';
$phar = new Phar($fileName);
$re=$phar->extractTo("Thrift-origin");
var_dump($re);
exit;
```

```
// 'debug'  => true,
Db::listen(function ($sql, $time, $master) {
    if ($time > 0.5) {
        $redis = new Redis(); $redis->connect('127.0.0.1', 6379);
        $redis->rPush('sql_log_queue', json_encode(['sql'=>$sql, 'time'=>$time, 't'=>time()]));
        $redis->close();
    }
});

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
```


//扩展 xieyuhua.so
```
<?php
if (version_compare(PHP_VERSION, 7, '<'))
    die("PHP must later than version 7.0\n");
if (php_sapi_name() !== 'cli')
    die("Must run in cli mode\n");
if (!extension_loaded('xieyuhua'))
    die("The extension: 'xieyuhua' not loaded\n");
if ($argc <= 1)
    die("\nusage: php xieyuhua.php file.php ...     encrypt the php file(s) or directory(s)\n\n");
array_shift($argv);
foreach ($argv as $fileName) {
    if (is_file($fileName)) {
        handle($fileName);
    } elseif (is_dir($fileName)) {
        $DirectoriesIt = new RecursiveDirectoryIterator($fileName, FilesystemIterator::SKIP_DOTS);
        $AllIt         = new RecursiveIteratorIterator($DirectoriesIt);
        $it            = new RegexIterator($AllIt, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($it as $v)
            handle($v[0]);
    } else {
        echo "Unknowing file: '$fileName'\n";
    }
}

function handle($file)
{
    if ($fp = fopen($file, 'rb+') and $fileSize = filesize($file)) {
        $data = tonyenc_encode(fread($fp, $fileSize));
        if ($data !== false) {
            if (file_put_contents($file, '') !== false) {
                rewind($fp);
                fwrite($fp, $data);
            }
        }
        fclose($fp);
    }
}

```
![image](https://user-images.githubusercontent.com/29120060/119220434-2b4f6000-bb1d-11eb-8dd7-4a757c75d5a0.png)

