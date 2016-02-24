<?php
namespace GlobalData;
/**
 *  Global data client.
 *  @version 1.0.0
 */
class Client 
{
    /**
     * Global server ip.
     * @var string
     */
    protected $_globalServerIP = null;
    
    /**
     * Global server port..
     * @var int
     */
    protected $_globalServerPort = null;
    
    /**
     * Connection to global server.
     * @var resource
     */
    protected $_globalConnection = null;
    
    /**
     * Cache.
     * @var array
     */
    protected $_cache = array();
    
    /**
     * Construct.
     * @param string $global_server_ip
     * @param int $global_server_port
     */
    public function __construct($global_server_ip = '127.0.0.1', $global_server_port = 2207)
    {
        $this->_globalServerIP = $global_server_ip;
        $this->_globalServerPort = $global_server_port;
        $this->connect();
    }

    /**
     * Connect to global server.
     * @throws \Exception
     */
    protected function connect()
    {
        $this->_globalConnection = stream_socket_client("tcp://{$this->_globalServerIP}:{$this->_globalServerPort}", $code, $msg, 5);
        if(!$this->_globalConnection)
        {
            throw new \Exception($msg);
        }
        if(function_exists('socket_import_stream'))
        {
            $socket   = socket_import_stream($this->_globalConnection);
            socket_set_option($socket, SOL_SOCKET, SO_KEEPALIVE, 1);
            socket_set_option($socket, SOL_TCP, TCP_NODELAY, 1);
        }
        stream_set_timeout($this->_globalConnection, 5);
        if($this->_globalServerIP !== '127.0.0.1' && class_exists('Workerman\Lib\Timer'))
        {
            Workerman\Lib\Timer::add(25, function($socket){
                fwrite($socket, "ping\n");
            }, array($this->_globalConnection));
        } 
    }

    /**
     * Magic methods __set.
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    public function __set($key, $value) 
    {
        $buffer = serialize(array(
           'cmd'   => 'set',
           'key'   => $key,
           'value' => serialize($value),
        ))."\n";
        $this->writeToRemote($buffer);
        $this->readFromRemote();
    }

    /**
     * Magic methods __isset.
     * @param string $key
     */
    public function __isset($key)
    {
        return !null === $this->__get($key);
    }

    /**
     * Magic methods __unset.
     * @param string $key
     * @throws \Exception
     */
    public function __unset($key) 
    {
        $buffer = serialize(array(
           'cmd' => 'delete',
           'key' => $key
        ))."\n";
        $this->writeToRemote($buffer);
        $this->readFromRemote();
    }
  
    /**
     * Magic methods __get.
     * @param string $key
     * @throws \Exception
     */
    public function __get($key)
    {
        $buffer = serialize(array(
           'cmd' => 'get',
           'key' => $key,
        ))."\n";
        $this->writeToRemote($buffer);
        return unserialize(rtrim($this->readFromRemote(), "\n"));
    }

    /**
     * Cas.
     * @param string $key
     * @param mixed $value
     * @throws \Exception
     */
    public function cas($key, $old_value, $new_value)
    {
        $buffer = serialize(array(
           'cmd'     => 'cas',
           'md5' => md5(serialize($old_value)),
           'key'     => $key,
           'value'   => serialize($new_value),
        ))."\n";
        $this->writeToRemote($buffer);
        return "ok\n" === $this->readFromRemote();
    }

    /**
     * Write data to global server.
     * @param string $buffer
     */
    protected function writeToRemote($buffer)
    {
        $len = fwrite($this->_globalConnection, $buffer);
        if($len !== strlen($buffer))
        {
            throw new \Exception('writeToRemote fail');
        }
    }
    
    /**
     * Read data from global server.
     * @throws Exception
     */
    protected function readFromRemote()
    {
        $all_buffer = '';
        while(1)
        {
            $buffer = fread($this->_globalConnection, 8192);
            if($buffer === '' || $buffer === false)
            {
                throw new \Exception('readFromRemote fail');
            }
            $all_buffer .= $buffer;
            if($all_buffer[strlen($all_buffer)-1] === "\n")
            {
                break;
            }
        }
        return $all_buffer;
    }
}
