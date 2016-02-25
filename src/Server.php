<?php
namespace GlobalData;
use Workerman\Worker;

/**
 * Global data server.
 */
class Server
{
    /**
     * Worker instance.
     * @var worker
     */
    protected $_worker = null;

    /**
     * All data.
     * @var array
     */
    protected $_dataArray = array();

    /**
     * Construct.
     * @param string $ip
     * @param int $port
     */
    public function __construct($ip = '0.0.0.0', $port = 2207)
    {
        $worker = new Worker("frame://$ip:$port");
        $worker->count = 1;
        $worker->name = 'globalDataServer';
        $worker->onMessage = array($this, 'onMessage') ;
        $this->_worker = $worker; 
    }
    
    /**
     * onMessage.
     * @param TcpConnection $connection
     * @param string $buffer
     */
    public function onMessage($connection, $buffer)
    {
        if($buffer === 'ping')
        {
            return;
        }
        $data = unserialize($buffer);
        if(!$buffer || !isset($data['cmd']) || !isset($data['key']))
        {
            return $connection->close('bad request');
        }
        $cmd = $data['cmd'];
        $key = $data['key'];
        switch($cmd) 
        {
            case 'get':
                if(!isset($this->_dataArray[$key]))
                {
                   return $connection->send('N;');
                }
                return $connection->send($this->_dataArray[$key]);
                break;
            case 'set':
                $this->_dataArray[$key] = $data['value'];
                $connection->send('ok');
                break;
            case 'cas':
                if(!isset($this->_dataArray[$key]) || md5($this->_dataArray[$key]) === $data['md5'])
                {
                    $this->_dataArray[$key] = $data['value'];
                    return $connection->send('ok');
                }
                $connection->send('mismatch');
                break;
            case 'delete':
                unset($this->_dataArray[$key]);
                $connection->send('ok');
                break;
            default:
                $connection->close('bad cmd '. $cmd);
        }
    }
}


