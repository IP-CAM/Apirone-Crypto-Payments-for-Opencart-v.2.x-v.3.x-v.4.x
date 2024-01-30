<?php

namespace ApironeApi;

class Error
{
    public $code;
    public $body;
    public $info;

    /**
     * 
     * @param string $code 
     * @param string $body 
     * @param string $info 
     * @return void 
     */
    public function __construct($code = '', $body = '', $info = '' )
    {
        if (empty($code)) {
            return;
        }

        $this->add( $code, $body, $info );
    }

    public function add( $code, $body, $info = '')
    {
        $this->code = $code;
        $this->body = $body;
        $this->info = $info;
    }

    public function hasError ()
    {
        return (!empty($this->code)) ? true : false;
    }

    public function __toString()
    {
        $toString = ['code' => $this->code, 'body' => json_decode($this->body), 'info' => json_decode($this->info)];

        return (!empty($this->code)) ? print_r($toString) : '';
    }

}
