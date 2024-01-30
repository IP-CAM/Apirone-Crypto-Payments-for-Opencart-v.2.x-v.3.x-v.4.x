<?php

namespace ApironeApi;

require_once(__DIR__ . '/Request.php');
require_once(__DIR__ . '/LoggerWrapper.php');
require_once(__DIR__ . '/Db.php');

require_once(__DIR__ . '/Utils.php');


use \ApironeApi\Request as Request;
use \ApironeApi\LoggerWrapper as LoggerWrapper;
use InvalidArgumentException;

class Apirone
{

    use Utils;

    static $currencyIconUrl = 'https://apirone.com/static/img2/%s.svg';

    /**
     * Get list of supported currencies
     * 
     * @param string $type wallets|accounts
     * @return Error|string|void|false 
     */
    public static function getCurrency ($abbr)
    {
        $result = self::currencyList();
        if ($result == false)
            return false;

        foreach ($result as $currency) {
            if ($currency->abbr == $abbr) {
                return $currency;
            }
        }

        return false; 
    }

    /**
     * Get list of supported currencies
     * 
     * @param string $type wallets|accounts
     * @return Error|string|void|false 
     */
    public static function currencyList ()
    {
        $result = self::serviceInfo();
        if ($result == false)
            return array();

        $currencies = $result->currencies;

        foreach ($currencies as $currency) {
            if (!property_exists($currency, 'dust-rate')) {
                $currency->{'dust-rate'} = 1000;
            }
            $currency->address = '';
            $currency->icon = self::currencyIcon($currency->abbr);
            $currency->testnet = (substr_count(strtolower($currency->name), 'testnet') > 0) ? 1 : 0;
        }

        return $currencies; 
    }

    /**
     * Returns a list of set currencies
     *
     * @param mixed $account 
     * @param bool $activeOnly 
     * @return mixed 
     */
    public static function accountCurrencyList($account, $activeOnly = true)
    {
        $accountInfo = self::accountInfo($account);
        $serviceInfo = self::serviceInfo();

        if ($accountInfo == false || $serviceInfo == false)
            return false;

        $info = $accountInfo->info;
        $currencies = $serviceInfo->currencies;

        $destinations = array();
        $activeCurrencies = array();

        // Get destinations
        foreach ($info as $item) {
            $destinations[$item->currency] = $item->destinations ? $item->destinations[0]->address : false;
        }

        // Get currencies list
        foreach ($currencies as $item) {
            unset($item->{'units'});
            unset($item->{'processing-fee'});
            unset($item->{'fee-free-limit'});
            unset($item->{'address-types'});
            unset($item->{'default-address-type'});
            unset($item->{'minimal-confirmations'});

            if (!property_exists($item, 'dust-rate')) {
                $item->{'dust-rate'} = 1000;
            }
            $item->address = $destinations[$item->abbr];
            $item->icon = self::currencyIcon($item->abbr);
            $item->testnet = (substr_count(strtolower($item->name), 'testnet') > 0) ? 1 : 0;
            if($activeOnly && !empty($item->address)) {
                $activeCurrencies[] = $item;
            }
        }

        return ($activeOnly) ? $activeCurrencies : $currencies;
    }

    /**
     * Get list of supported currencies
     * 
     * @param string $type wallets|accounts
     * @return Error|string|void|false 
     */
    public static function serviceInfo ($type = 'accounts')
    {
        $endpoint = '/v2/' . $type;

        return Request::execute('options', $endpoint);
    }

    /**
     * Create new account
     * 
     * @return json|false 
     */
    public static function accountCreate ()
    {
        $endpoint = '/v2/accounts';

        return Request::execute('post', $endpoint);
    }

    /**
     * Get account info
     * 
     * @param mixed $account_id 
     * @param mixed $currency 
     * @return json|false 
     */
    public static function accountInfo ($account_id, $currency = false)
    {
        $endpoint = '/v2/accounts/' . $account_id;
        $params = ($currency) ? array('currency' => $currency) : array();

        return Request::execute('get', $endpoint, $params);
    }

    public static function setTransferAddress($account, $currency, $address, $policy = 'percentage')
    {
        $endpoint = '/v2/accounts/' . $account->account;

        $params['transfer-key'] = $account->{'transfer-key'};
        $params['currency'] = $currency;
        $params['destinations'] = null;
        $params['processing-fee-policy'] = $policy;
        if ($address) {
            $params['destinations'][] = array("address" => $address);
        }

        return Request::execute('patch', $endpoint, $params, true);
    }

    /**
     * 
     * @param object $account
     * @param object $invoiceData 
     * @return mixed 
     */
    public static function invoiceCreate ($account, $invoiceData)
    {
        $endpoint = '/v2/accounts/' . $account->account . '/invoices';

        return Request::execute('post', $endpoint, $invoiceData, true);
    }

    public static function invoiceInfoPublic($invoice_id)
    {
        $endpoint = '/v2/invoices/' . $invoice_id;

        return Request::execute('get', $endpoint);
    }

    /**
     * Configure logger
     *
     * @param mixed $loggerInstance 
     * @param bool $debugMode 
     * @return void 
     * @throws InvalidArgumentException 
     */
    public static function setLogger($loggerInstance, $debugMode = false)
    {
        LoggerWrapper::setLogger($loggerInstance, $debugMode);   
    }

    /**
     * Get currency icon URL
     * 
     * @param mixed $abbr 
     * @return string 
     */
    static public function currencyIcon ($abbr)
    {
        return sprintf(self::$currencyIconUrl, str_replace('@', '_', $abbr));
    }
}
