<?php

namespace ApironeApi;

class Error
{
    public $errors = array();

    /**
     * 
     * @param string $code 
     * @param string $body 
     * @param string $data 
     * @return void 
     */
    public function __construct($code = '', $body = '', $data = '' )
    {
        if (empty($code)) {
            return;
        }

        $this->add( $code, $body, $data );
    }

    public function add( $code, $body, $data = '')
    {
        $this->errors[$code]['body'] = $body;
        $this->errors[$code]['info'] = $data;
    }

    public function hasError ()
    {
        return (!empty($this->errors)) ? true : false;
    }

}
