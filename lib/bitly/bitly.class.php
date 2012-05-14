<?php

class Bitly
{
    
    const HOST = 'https://api-ssl.bitly.com';
    
    const ENDPOINT_SHORTEN = '/v3/shorten';
    
    const TIMEOUT = 4;
    
    /**
     * Holds the api key
     *
     * @var string
     */
    protected $_apikey;
    
    /**
     * Getter for $_apikey
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->_apikey;
    }
    
    /**
     * Setter for $_apikey
     *
     * @param string $value
     * @return Bitly
     */
    public function setApiKey($value)
    {
        $this->_apikey = $value;
        return $this;
    }
    
    /**
     * Holds the login name
     *
     * @var string
     */
    protected $_login;
    
    /**
     * Getter for $_login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->_login;
    }
    
    /**
     * Setter for $_login
     *
     * @param string $value
     * @return Bitly
     */
    public function setLogin($value)
    {
        $this->_login = $value;
        return $this;
    }
    
    /**
     * Returns some general parameters
     *
     * @return array
     */
    protected function _getGeneralParams()
    {
        return array(
            'login'  => $this->getLogin(),
            'apiKey' => $this->getApiKey(),
            'format' => 'json'
        );
    }
    
    /**
     * Shortens a url
     *
     * This method shortens a given url with bit.ly and returns the short url
     * in an array
     *
     * @param array $options
     * @return array
     */
    public function shorten(array $options)
    {
        $params = $this->_getGeneralParams();
        
        if (!isset($options['longUrl'])) {
            throw new Exception('"longUrl" not specified');
        } else {
            $params['longUrl'] = urlencode($options['longUrl']);
        }
        
        $params = $this->_getParams($params);
        $url = self::HOST . self::ENDPOINT_SHORTEN . '?' . $params;
        
        $ch = $this->_prepareCurl($url);
        $return = $this->_curlExecute($ch);
        
        return json_decode($return);
    }

    /**
     * Prepares a curl request
     *
     * @param string $url
     * @return resource
     */
    protected function _prepareCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT * 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        
        return $ch;
    }
    
    /**
     * Executes a curl request
     *
     * @param resource $ch
     */
    protected function _curlExecute($ch)
    {
        $return = curl_exec($ch);
        curl_close($ch);
        
        return $return;
    }
    
    protected function _getParams($params)
    {
        $url_params = array();
        foreach ($params as $key => $value) {
            $url_params[] = $key . '=' . $value;
        }
        
        $params = implode('&', $url_params);
        
        return $params;
    }
}