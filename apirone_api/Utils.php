<?php
namespace ApironeApi;

// require_once(__DIR__ . '/Request.php');

trait Utils {

    /**
     * Returnt transaction link to blockchair.com
     * 
     * @param mixed $currency
     * @return string 
     */
    public static function getTransactionLink($currency) {
        if ($currency->abbr == 'tbtc') 
            return 'https://blockchair.com/bitcoin/testnet/transaction/';
        
        return sprintf('https://blockchair.com/%s/transactions/', strtolower(str_replace([' ', '(', ')'], ['-', '/', ''],  $currency->name)));
    }

    /**
     * Returnt transaction link to blockchair.com
     * 
     * @param mixed $currency
     * @return string 
     */
    public static function getAddressLink($currency, $address) {
        if ($currency->abbr == 'tbtc') 
            return 'https://blockchair.com/bitcoin/testnet/address/' . $address;
        
        return sprintf('https://blockchair.com/%s/address/', strtolower(str_replace([' ', '(', ')'], ['-', '/', ''],  $currency->name))) . $address;
    }

    /**
     * Return img tag with QR-code link
     * 
     * @param mixed $currency 
     * @param mixed $input_address 
     * @param mixed $remains 
     * @return void 
     */
    public static function getQrLink($currency, $input_address, $remains) {
        $prefix = (substr_count($input_address, ':') > 0 ) ? '' : strtolower(str_replace([' ', '(', ')'], ['-', '', ''],  $currency->name)) . ':';

        return 'https://chart.googleapis.com/chart?chs=225x225&cht=qr&chl=' . urlencode($prefix . $input_address . "?amount=" . $remains);
    }

    /**
     * Return masked transaction hash
     * 
     * @param mixed $hash 
     * @return string 
     */
    public static function maskTransactionHash ($hash) {
        return substr($hash, 0, 8) . '......' . substr($hash, -8);
    }

    /**
     * Convert to decimal and trim trailing zeros if $zeroTrim set true
     * 
     * @param mixed $value 
     * @param bool $zeroTrim (optional)
     * @return string 
     */
    public static function exp2dec($value, $zeroTrim = false) {
        if ($zeroTrim)
            return rtrim(rtrim(sprintf('%.8f', floatval($value)), 0), '.');
        
        return sprintf('%.8f', floatval($value));
    }

    public static function min2cur($value, $unitsFactor) {
        return $value * $unitsFactor;
    }

    public static function cur2min($value, $unitsFactor) {
        return $value / $unitsFactor;
    }

    /**
     * Convert fiat value to crypto by request to apirone api
     * 
     * @param mixed $value 
     * @param string $from 
     * @param string $to 
     * @return mixed 
     */
    static public function fiat2crypto($value, $from='usd', $to = 'btc') {
        $from = strtolower(trim($from));
        $to = strtolower(trim($to));
        if ($from == $to) {
            return $value;
        }

        $endpoint = '/v1/to' . strtolower($to);
        $params = array(
            'currency' => trim(strtolower($from)),
            'value' => trim(strtolower($value))
        );
        $result = Request::execute('get', $endpoint, $params );

        if (Request::isResponseError($result)) {
            Log::debug($result);
            return false;
        }
        else
            return (float) $result;
    }

    static public function getAssets($filename, $minify = false) {
        $path = __DIR__ . '/assets/' . $filename;

        $content = false;

        if (file_exists($path)) {
            $content = file_get_contents($path);
        }

        if ($minify) {
            return self::minify($content);
        }
        return $content;
    }

    static public function getAssetsPath($filename, $wwwroot = false) {
        $path = __DIR__ . '/assets/' . $filename;

        return ($wwwroot) ? str_replace($wwwroot, '', $path) : $path;    
    }

    public static function minify($string)
    {
        $search = array(
            '/(\n|^)(\x20+|\t)/',
            '/(\n|^)\/\/(.*?)(\n|$)/',
            '/\n/',
            '/\<\!--.*?-->/',
            '/(\x20+|\t)/', # Delete multispace (Without \n)
            '/\>\s+\</', # strip whitespaces between tags
            '/(\"|\')\s+\>/', # strip whitespaces between quotation ("') and end tags
            '/=\s+(\"|\')/'); # strip whitespaces between = "'

        $replace = array(
            "\n",
            "\n",
            " ",
            "",
            " ",
            "><",
            "$1>",
            "=$1");

            $string = preg_replace($search, $replace, $string);
            return $string;
    }


}
