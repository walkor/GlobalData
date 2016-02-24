# GlobalData
进程间数据共享组件，用于分布式数据共享。服务端基于[Workerman](https://github.com/walkor/Workerman)。客户端可用于任何PHP项目。

# 服务端
```php
use Workerman\Worker;
require_once __DIR__ . '/../../Workerman/Autoloader.php';
require_once __DIR__ . '/../src/Server.php';

$worker = new GlobalData\Server();

Worker::runAll();
```
