<?php
namespace Opencart\Catalog\Controller\Extension\Apirone\Payment;

require_once(DIR_EXTENSION . 'apirone/system/library/apirone_api/Apirone.php');
require_once(DIR_EXTENSION . 'apirone/system/library/apirone_api/Payment.php');

use ApironeApi\Apirone;
use ApironeApi\Payment;

function pa($mixed) {
    echo '<pre>';
    print_r($mixed);
    echo '</pre>';
}
class ApironeMccp extends \Opencart\System\Engine\Controller {

    public function index() {

        $data['button_confirm'] = $this->language->get('button_confirm');
        $this->load->model('checkout/order');
        $this->load->language('extension/apirone/payment/apirone_mccp');
        $this->load->model('extension/apirone/payment/apirone_mccp');

        $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $account = unserialize($this->config->get('payment_apirone_mccp_account'))->account;
        $showTestnet = $this->model_extension_apirone_payment_apirone_mccp->showTestnet();
        $factor = (float) $this->config->get('payment_apirone_mccp_factor');

        $data['coins'] = Payment::getCoins($account, $order['total'] * $order['currency_value'] * $factor, $order['currency_code'], $showTestnet);
        $data['order_id'] = $order['order_id'];
        $data['order_key'] = Payment::makeInvoiceSecret( $this->config->get('payment_apirone_mccp_secret'), $order['total'] . $order['date_added']);
        $data['url_redirect'] = $this->url->link('extension/apirone/payment/apirone_mccp|confirm');

        return $this->load->view('extension/apirone/payment/apirone_mccp', $data);
    }

    public function confirm() {

        $this->load->model('checkout/order');
        $this->load->language('extension/apirone/payment/apirone_mccp');
        $this->load->model('extension/apirone/payment/apirone_mccp');

        $currency = (isset($this->request->get['currency'])) ? (string) $this->request->get['currency'] : '';
        $order_key = (isset($this->request->get['key'])) ? (string) $this->request->get['key'] : '';

        $secret = $this->config->get('payment_apirone_mccp_secret');
        $order_id = isset($this->request->get['order']) ? $this->request->get['order'] : '';
        $order = $this->model_checkout_order->getOrder((int)$order_id);

        // Redirect to cart if order not found
        if (empty($order) || !Payment::checkInvoiceSecret($order_key, $secret, $order['total'] . $order['date_added'])) {
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }

        $currencyInfo = Apirone::getCurrency($currency);

        // Is order invoice aready exists
        $orderInvoice = $this->model_extension_apirone_payment_apirone_mccp->getInvoiceByOrderId($order_id);
        if ($orderInvoice) {
            // Update invoice when page loaded or reloaded & status != 0 (expired || completed)
            if (Payment::invoiceStatus($orderInvoice) != 0) {
                $invoice_data = Apirone::invoiceInfoPublic($orderInvoice->invoice);
                if ($invoice_data) {
                    $invoiceUpdated = $this->model_extension_apirone_payment_apirone_mccp->updateInvoice($orderInvoice->order_id, $invoice_data);
                    $orderInvoice = ($invoiceUpdated) ? $invoiceUpdated : $orderInvoice;
                }
            }

            $this->showInvoice($orderInvoice, $currencyInfo);
            return;
        }

        $factor = (float) $this->config->get('payment_apirone_mccp_factor');
        $totalCrypto = Payment::fiat2crypto($order['total'] * $order['currency_value'] * $factor, $order['currency_code'], $currency);
        $amount = (int) Payment::cur2min($totalCrypto, $currencyInfo->{'units-factor'});

        $lifetime = (int) $this->config->get('payment_apirone_mccp_timeout');
        $invoiceSecret = Payment::makeInvoiceSecret($secret, $order_id);
        $callback = $this->url->link('extension/apirone/payment/apirone_mccp|callback', array('id' => $invoiceSecret), true);

        $created = Apirone::invoiceCreate(
            unserialize($this->config->get('payment_apirone_mccp_account')),
            Payment::makeInvoiceData($currency, $amount, $lifetime, $callback, $order['total'], $order['currency_code'])
        );

        if($created) {
            $invoice = $this->model_extension_apirone_payment_apirone_mccp->updateInvoice($order_id, $created);
            $this->showInvoice($invoice, $currencyInfo, true);

            return;
        }
        else {
            $this->response->redirect($this->url->link('checkout/cart'));
            return;
        }
    }

    public function callback() {
        $this->load->model('checkout/order');
        $this->load->model('extension/apirone/payment/apirone_mccp');
        $params = false;

        $data = file_get_contents('php://input');
        if($data) {
            $params = json_decode($data);
        }

        if (!$params) {
            http_response_code(400);
            $this->response->setOutput("Data not received");
            return;
        }
        if (!property_exists($params, 'invoice') || !property_exists($params, 'status')) {
            http_response_code(400);
            $this->response->setOutput("Wrong params received: " . json_encode($params));
            return;        
        }

        $callback_secret = (isset($this->request->get['id'])) ? (string) $this->request->get['id'] : '';
        $secret = $this->config->get('payment_apirone_mccp_secret');

        $invoice = $this->model_extension_apirone_payment_apirone_mccp->getInvoiceById($params->invoice);
        
        if (!$invoice) {
            http_response_code(404);
            $this->response->setOutput("Invoice not found: " . $params->invoice);
            return;
        }

        if (!Payment::checkInvoiceSecret($callback_secret, $secret, $invoice->order_id)) {
            http_response_code(403);
            $this->response->setOutput("Secret not valid: " . $callback_secret);
            return;
        }

        $invoiceUpdated = Apirone::invoiceInfoPublic($invoice->invoice);

        if($invoiceUpdated) {
            $this->model_extension_apirone_payment_apirone_mccp->updateInvoice($invoice->order_id, $invoiceUpdated);
        }
    }

    public function status() {
        $this->load->model('extension/apirone/payment/apirone_mccp');
        $id = $this->request->get['id'];
        $invoice = $this->model_extension_apirone_payment_apirone_mccp->getInvoiceById($id);
        
        echo Payment::invoiceStatus($invoice);
    }
    
    protected function showInvoice($invoice, &$currency, $clear_cart = false) {
        $merchant = $this->config->get('payment_apirone_mccp_merchantname');

        if ($merchant == '') {
            $merchant = $this->config->get('config_name');
        }
        $data['title'] = "Pay via Crypto";
        $data['style_raw'] = '/' . Payment::getAssetsPath('style.min.css', DIR_OPENCART);
        $data['script_raw'] = '/' . Payment::getAssetsPath('script.min.js', DIR_OPENCART);

        $statusLink = $this->url->link('extension/apirone/payment/apirone_mccp|status', 'id=' . $invoice->invoice);

        $data['invoice'] = Payment::invoice($invoice, $currency, $statusLink, $merchant);

        foreach (glob("catalog/view/javascript/jquery/jquery*.min.js") as $filename) {
            $data['jquery'] = '/' . $filename;
        }

        if($clear_cart) {
            unset($this->session->data['order_id']);
            $this->cart->clear();
        }

        $this->response->setOutput($this->load->view('extension/apirone/payment/apirone_mccp_invoice', $data));
        return;
    }

}