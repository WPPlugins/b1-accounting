<?php

/**
 * Library that allows to communicate with B1 API.
 * @author Serj Ivanov
 */
class B1
{

    /**
     * Library version.
     */
    const VERSION = '0.0.6';

    /**
     * Plugin name.
     */
    const PLUGIN_NAME = 'Woocommerce';

    /**
     * Number of files used for log rotation. Defaults to 10.
     */
    const MAX_LOG_FILES = 10;

    /**
     * Maximum log file size in kilo-bytes (KB). Defaults to 1024 (1MB).
     */
    const MAX_FILE_SIZE = 1024;

    /**
     * Log directory name. Will be created in the same directory as library.
     */
    const LOG_DIR = 'log';

    /**
     * Log file name. Defaults to 'data.log'.
     */
    const LOG_FILE = 'data.log';

    /**
     * Error log level.
     */
    const LEVEL_ERROR = 'ERROR';

    /**
     * Warning log level.
     */
    const LEVEL_WARNING = 'WARNING';

    /**
     * Info log level.
     */
    const LEVEL_INFO = 'INFO';

    /**
     * @var bool Whenever to enable logging. Default is false.
     */
    private $log = true;

    /**
     * Server URL where all requests should go.
     */
    private $baseApiUrl = 'https://www.b1.lt/api/';

    /**
     * @var string API key. Required for all requests to B1.
     */
    private $apiKey;

    /**
     * @var string Private key. Required for all requests to B1.
     */
    private $privateKey;

    /**
     * @var int Request timeout.
     */
    private $timeout = 30;

    /**
     * @var array Attributes that are allowed to be set.
     */
    private $configKeys = ['baseApiUrl', 'apiKey', 'privateKey', 'timeout', 'maxRedirects', 'log'];

    public function __construct($config)
    {
        foreach ($this->configKeys as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }
        if (empty($this->apiKey)) {
            throw new B1Exception('API key is not provided', $config);
        }
        if (empty($this->privateKey)) {
            throw new B1Exception('Private key is not provided', $config);
        }
    }

    /**
     * Generates invoice url in B1 system.
     * @param int $orderId Order ID.
     * @param string $shopId Shop ID.
     * @return string Generated url in B1 system.
     */
    public function generateInvoiceUrl($orderId, $shopId)
    {
        return $this->generateUrl('shop/invoice/get', ['orderId' => $orderId, 'prefix' => $shopId]);
    }

    /**
     * Appends required params & generates the url.
     * @param string $path Path.
     * @param array $data Additional parameters to add to the url.
     * @return string Generated url.
     */
    public function generateUrl($path, $data)
    {
        $data = $this->buildRequestData($data);
        return $this->baseApiUrl . $path . '?' . http_build_query($data);
    }

    /**
     * Executes the request. All requests to B1 have to go through here.
     * @param string $path Path in B1 system.
     * @param array $data Data to send to B1.
     * @return bool|array False on error, array on success.
     * @throws B1Exception
     */
    public function exec($path, $data = [])
    {
        try {
            $data = $this->buildRequestData($data);
            return $this->executeRequest($path, $data);
        } catch (B1Exception $e) {
            $this->log($e);
            throw $e;
        }
    }

    /**
     * Builds request data array.
     * @param array $data that needs to be incorporated in the request.
     * @return array Built request array.
     * @throws B1Exception
     */
    private function buildRequestData($data)
    {
        $data['version'] = B1::VERSION;
        $data['pluginName'] = B1::PLUGIN_NAME;
        $data['time'] = time();
        $data['apiKey'] = $this->apiKey;
        $data['signature'] = $this->signRequestData($data);
        return $data;
    }

    /**
     * Generates signature.
     * @param array $data Data to sign.
     * @return string Signature.
     */
    private function signRequestData(array $data)
    {
        return hash_hmac('sha512', http_build_query($data), $this->privateKey);
    }

    /**
     * Makes the call to the API.
     * @param string $path Path in B1 system.
     * @param array $data Data to send.
     * @return mixed|null
     * @throws B1Exception
     */
    private function executeRequest($path, $data)
    {
        $url = $this->baseApiUrl . $path;
        $dataToSend = http_build_query($data);

        $args = array(
            'body' => $data,
            'timeout' => $this->timeout,
            'headers' => array(),
        );
        $response = wp_remote_post($url, $args);
        $debug = [
            'url' => $url,
            'path' => $path,
            'data' => $data,
            'sent' => $dataToSend,
            'debug' => $url . '?' . $dataToSend,
            'received' => $response,
        ];

        if (is_wp_error($response)) {
            throw new B1RequestException($response->get_error_message(), $debug);
        } else {
            $code = $response['response']['code'];
            switch ($code) {
                case 200:
                    $this->log($debug, self::LEVEL_INFO);
                    return json_decode($response['body'], true);
                case 400:
                    throw new B1ValidationException("Data validation failure.", $debug);
                case 404:
                    throw new B1ResourceNotFoundException("Resource not found.", $debug);
                case 409:
                    throw new B1DuplicateException("Object already exists in the B1 system.", $debug);
                case 500:
                    throw new B1InternalErrorException("B1 API internal error.", $debug);
                case 503:
                    throw new B1ServiceUnavailableException("B1 API is currently unavailable.", $debug);
                default:
                    throw new B1Exception("B1 API fatal error.", $debug);
            }
        }
    }

    /**
     * Logs the message.
     * @param mixed $msg Message.
     * @param string $level Message level.
     * @return bool
     */
    private function log($msg, $level = self::LEVEL_ERROR)
    {
        if (!$this->log) {
            return true;
        }
        if ($msg instanceof B1Exception) {
            $msg = $msg->getMessage() . ' DEBUG: ' . print_r($msg->getExtraData(), true);
        } else if (!is_string($msg)) {
            $msg = print_r($msg, true);
        }
        $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::LOG_DIR;
        if (!is_dir($path)) {
            mkdir($path);
        }
        $file = $path . DIRECTORY_SEPARATOR . self::LOG_FILE;
        $fp = @fopen($file, 'a');
        @flock($fp, LOCK_EX);

        $data = $level . ' ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-') . ' ' . date('[Y-m-d H:i:s O]') . ' v' . self::VERSION . ': ' . $msg . "\n";

        if (@filesize($file) > self::MAX_FILE_SIZE * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $data);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }

    /**
     * Rotates the log files.
     */
    private function rotateFiles()
    {
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::LOG_DIR . DIRECTORY_SEPARATOR . self::LOG_FILE;
        for ($i = self::MAX_LOG_FILES; $i > 0; --$i) {
            $rotateFile = $file . '.' . $i;
            if (is_file($rotateFile)) {
                if ($i === self::MAX_LOG_FILES) {
                    @unlink($rotateFile);
                } else {
                    @rename($rotateFile, $file . '.' . ($i + 1));
                }
            }
        }
        if (is_file($file)) {
            @rename($file, $file . '.1');
        }
        clearstatcache();
    }

}

/**
 * Base exception class for all exceptions in this library
 */
class B1Exception extends Exception
{

    /**
     * @var array
     */
    private $extraData;

    public function __construct($message = "", $extraData = [], $code = 0, Throwable $previous = null)
    {
        $this->extraData = $extraData;
        parent::__construct($message, $code, $previous);
    }

    public function getExtraData()
    {
        return $this->extraData;
    }

}

class B1RequestException extends B1Exception
{

}

class B1ValidationException extends B1Exception
{

}

class B1ResourceNotFoundException extends B1Exception
{

}

class B1DuplicateException extends B1Exception
{

}

class B1InternalErrorException extends B1Exception
{

}

class B1ServiceUnavailableException extends B1Exception
{

}
