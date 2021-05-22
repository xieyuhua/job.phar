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
