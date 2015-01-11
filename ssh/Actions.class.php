<?php



/**
 * Мениджър на машини за отдалечен достъп
 *
 *
 * @category  bgerp
 * @package   ssh
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class ssh_Actions
{
    
    
    private $host;
    
    private $port = 22;
    
    private $user;
    
    private $pass;
    
    private $connection;
    
    
    /**
     * Конструктор
     */
    public function __construct($host, $port, $user, $pass)
    {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        
        $this->connect();
    }
    
    
    /**
     * Връща кънекшън ресурс
     * 
     * @param string $host
     * @return resource
     */
    private function connect ()
    {
        
        if ($this->connection) {
            
            return $this->connection
        }

        // Проверяваме дали е достъпен
        $timeoutInSeconds = 1;
        if (!($fp = @fsockopen($this->host, $this->port, $errCode, $errStr, $timeoutInSeconds))) {
            throw new core_exception_Expect("{$this->host}: не може да бъде достигнат");
        }
        fclose($fp);
        
        // Проверяваме има ли ssh2 модул инсталиран
        if (!function_exists('ssh2_connect')) {
            throw new core_exception_Expect("Липсващ PHP модул: <b>`ssh2`</b>
                инсталирайте от командна линия с: apt-get install libssh2-php");
        }
        
        // Свързваме се по ssh
        $this->connection = @ssh2_connect($this->host, $this->port);
        if (!$this->connection) {
            throw new core_exception_Expect("{$this->host}: няма ssh връзка");
        }
        
        if (!@ssh2_auth_password($this->connection, $this->user, $this->pass)) {
            throw new core_exception_Expect("{$this->host}: грешен потребител или парола.");
        }
    }
    
    
    /**
     * Изпълнява команда на отдалечен хост
     *
     * @param string $command
     * @param string $output [optionаl]
     * @param string $errors [optionаl]
     */
    public function exec($command, &$output=NULL, &$errors=NULL)
    {

        // Изпълняваме командата
        $stream = ssh2_exec($this->connection, $command);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        
        stream_set_blocking($stream, true);
        stream_set_blocking($errorStream, true);
        
        // Връщаме резултат
        $output = stream_get_contents($stream);
        $errors = stream_get_contents($errorStream);
        
        fclose($stream);
        fclose($errorStream);
    }

    /**
     * Качва файл на отдалечен хост
     *
     * @param string $host
     * @param string $fileName - име на локалния файл
     */
    public function put($localFileName)
    {
        
        $remoteFileName = $localFileName;
        
        if (ssh2_scp_send($this->connection, $localFileName, $remoteFileName)) {
            throw new core_exception_Expect("Грешка при качване на файл от отдалечен хост");
        }
    }
    
    /**
     * Смъква файл от отдалечен хост
     *
     * @param string $host
     * @param string $fileName име на отдалечения файл
     */
    public function get($remoteFileName)
    {
        $localFileName = $remoteFileName;
        
        if (!ssh2_scp_recv($this->connection, $remoteFileName, $localFileName)) {
            throw core_exception_Expect("Грешка при сваляне на файл от отдалечен хост");
        }        
    }
    
}
