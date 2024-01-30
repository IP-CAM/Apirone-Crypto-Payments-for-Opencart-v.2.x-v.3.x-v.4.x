<?php

use ApironeApi\Apirone;
use ApironeApi\LoggerWrapper;
use ApironeApi\Payment;

require_once(DIR_SYSTEM . 'library/apirone_api/Apirone.php');
require_once(DIR_SYSTEM . 'library/apirone_api/LoggerWrapper.php');
require_once(DIR_SYSTEM . 'library/apirone_api/Payment.php');

class ControllerExtensionPaymentApironeMccp extends Controller
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $logger = new \Log('apirone.log');
        $debug = (bool) $this->config->get('apirone_mccp_debug');
        try {
            Apirone::setLogger($logger, $debug);
        }
        catch (Exception $e) {
            $this->log->write($e->getMessage());
        }
    }

    public function index()
    {

        $data['button_confirm'] = $this->language->get('button_confirm');
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/apirone_mccp');
        $this->load->model('extension/payment/apirone_mccp');

        $data = array_merge($data, $this->load->language('apirone_mccp'));
        $data['error_message'] = false;

        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $account = unserialize($this->config->get('apirone_mccp_account'))->account;
        $showTestnet = $this->model_extension_payment_apirone_mccp->showTestnet();
        $factor = (float) $this->config->get('apirone_mccp_factor');

        $data['coins'] = Payment::getCoins($account, $order['total'] * $order['currency_value'] * $factor, $order['currency_code'], $showTestnet);
        $data['order_id'] = $order['order_id'];
        $data['order_key'] = Payment::makeInvoiceSecret( $this->config->get('apirone_mccp_secret'), $order['total']);
        $data['url_redirect'] = $this->url->link('extension/payment/apirone_mccp/confirm');

        return $this->load->view('extension/payment/apirone_mccp', $data);
    }

    public function confirm()
    {
        $this->load->model('checkout/order');
        $this->load->language('extension/payment/apirone_mccp');
        $this->load->model('extension/payment/apirone_mccp');

        $currency = (isset($this->request->get['currency'])) ? (string) $this->request->get['currency'] : '';
        $order_key = (isset($this->request->get['key'])) ? (string) $this->request->get['key'] : '';
        $order_id = (isset($this->request->get['order'])) ? (int) $this->request->get['order'] : 0;

        $secret = $this->config->get('apirone_mccp_secret');
        $order = $this->model_checkout_order->getOrder($order_id);
        // Exit if $order_key is !valid
        if (!Payment::checkInvoiceSecret($order_key, $secret, $order['total'])) {
            return;
        }

        $currencyInfo = Apirone::getCurrency($currency);

        // Is order invoice already exists
        $orderInvoice = $this->model_extension_payment_apirone_mccp->getInvoiceByOrderId($order_id);
        if ($orderInvoice) {
            // Update invoice when page loaded or reloaded & status != 0 (expired || completed)
            if (Payment::invoiceStatus($orderInvoice) != 0) {
                $invoice_data = Apirone::invoiceInfoPublic($orderInvoice->invoice);
                if ($invoice_data) {
                    $invoiceUpdated = $this->model_extension_payment_apirone_mccp->updateInvoice($orderInvoice->order_id, $invoice_data);
                    $orderInvoice = ($invoiceUpdated) ? $invoiceUpdated : $orderInvoice;
                }
            }

            $this->showInvoice($orderInvoice, $currencyInfo);
            return;
        }
        $factor = (float) $this->config->get('apirone_mccp_factor');

        $totalCrypto = Payment::fiat2crypto($order['total'] * $order['currency_value'] * $factor, $order['currency_code'], $currency);
        $amount = (int) Payment::cur2min($totalCrypto, $currencyInfo->{'units-factor'});

        $lifetime = (int) $this->config->get('apirone_mccp_timeout');
        $invoiceSecret = Payment::makeInvoiceSecret($secret, $order_id);
        $callback = $this->url->link('extension/payment/apirone_mccp/callback&id=' . $invoiceSecret);

        $created = Apirone::invoiceCreate(
            unserialize($this->config->get('apirone_mccp_account')),
            Payment::makeInvoiceData($currency, $amount, $lifetime, $callback, $order['total'], $order['currency_code'])
        );

        if($created) {
            $invoice = $this->model_extension_payment_apirone_mccp->updateInvoice($order_id, $created);
            $this->showInvoice($invoice, $currencyInfo, true);

            return;
        }
        $this->response->redirect($this->url->link('checkout/cart'));
    }

    public function callback()
    {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/apirone_mccp');
        $params = false;

        $data = file_get_contents('php://input');
        if($data) {
            $params = json_decode($data);
        }

        if (!$params) {
            http_response_code(400);
            $message = "Data not received";
            $this->response->setOutput($message);
            LoggerWrapper::callbackError($message);

            return;
        }
        if (!property_exists($params, 'invoice') || !property_exists($params, 'status')) {
            http_response_code(400);
            $message = "Wrong params received: " . json_encode($params);
            $this->response->setOutput($message);
            LoggerWrapper::callbackError($message);

            return;        
        }


        $callback_secret = (isset($this->request->get['id'])) ? (string) $this->request->get['id'] : '';
        $secret = $this->config->get('apirone_mccp_secret');

        $invoice = $this->model_extension_payment_apirone_mccp->getInvoiceById($params->invoice);
        
        if (!$invoice) {
            http_response_code(404);
            $message = "Invoice not found: " . $params->invoice;
            $this->response->setOutput($message);
            LoggerWrapper::callbackError($message);

            return;
        }

        if (!Payment::checkInvoiceSecret($callback_secret, $secret, $invoice->order_id)) {
            http_response_code(403);
            $message = "Secret not valid: " . $callback_secret;
            $this->response->setOutput("Secret not valid: " . $callback_secret);
            LoggerWrapper::callbackError($message);

            return;
        }

        $invoiceUpdated = Apirone::invoiceInfoPublic($invoice->invoice);

        if($invoiceUpdated) {
            $this->model_extension_payment_apirone_mccp->updateInvoice($invoice->order_id, $invoiceUpdated);
        }

        LoggerWrapper::callbackDebug('', $params);
    }

    public function status()
    {
        $this->load->model('extension/payment/apirone_mccp');
        $id = $this->request->get['id'];

        echo Payment::invoiceStatus($this->model_extension_payment_apirone_mccp->getInvoiceById($id));
    }
    
    protected function showInvoice($invoice, &$currency, $clear_cart = false)
    {
        $merchant = $this->config->get('apirone_mccp_merchantname');

        if ($merchant == '') {
            $merchant = $this->config->get('config_name');
        }

        $data['style'] = '<style>' . Payment::getAssets('style.min.css') . '</style>';
        $data['script'] = '<script type="text/javascript">' . Payment::getAssets('script.js') . '</script>';

        $statusLink = $this->url->link('extension/payment/apirone_mccp/status', 'id=' . $invoice->invoice);

        $data['invoice'] = Payment::invoice($invoice, $currency, $statusLink, $merchant);
        $data['title'] = '';

        if ($clear_cart) {
            $this->cart->clear();    
        }

        $this->response->setOutput($this->load->view('extension/payment/apirone_mccp_invoice', $data));
        return;
    }
}
