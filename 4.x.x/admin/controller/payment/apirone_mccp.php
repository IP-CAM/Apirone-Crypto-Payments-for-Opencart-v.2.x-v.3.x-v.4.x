<?php
namespace Opencart\Admin\Controller\Extension\Apirone\Payment;

require_once(DIR_EXTENSION . 'apirone/system/library/apirone_api/Apirone.php');
require_once(DIR_EXTENSION . 'apirone/system/library/apirone_api/Db.php');

// Define Plugin version
define ('PLUGIN_VERSION', '1.1.1');

use ApironeApi\Apirone;
use ApironeApi\Db;


class ApironeMccp extends \Opencart\System\Engine\Controller {
    private $error = array();

    public function index(): void {
        $this->update();
        $this->load->language('extension/apirone/payment/apirone_mccp');
        $this->load->model('extension/apirone/payment/apirone_mccp');

        $account = unserialize( $this->config->get('payment_apirone_mccp_account') );
        $secret = $this->config->get('payment_apirone_mccp_secret');

        $apirone_currencies = \ApironeApi\Apirone::currencyList();
        $plugin_currencies = unserialize( $this->config->get('payment_apirone_mccp_currencies') );

        $errors_count = 0;
        $active_currencies = 0;
        $currencies = array();

        if (!$this->user->hasPermission('modify', 'extension/apirone/payment/apirone_mccp')) {
            $data['error'] = $this->language->get('error_permission');
            $errors_count++;
        }

        foreach ($apirone_currencies as $item) {
            $currency = new \stdClass();

            $currency->name = $item->name;
            $currency->abbr = $item->abbr;
            $currency->{'dust-rate'} = $item->{'dust-rate'};
            $currency->{'units-factor'} = $item->{'units-factor'};
            $currency->address = '';
            $currency->currency_tooltip = sprintf($this->language->get('currency_activate_tooltip'), $item->name);
            $currency->testnet = $item->testnet;
            $currency->icon = $item->icon;

            // Set address from config 
            if ($plugin_currencies) {
                $currency->address = $plugin_currencies[$item->abbr]->address;
            }
            // Set address from config 
            if ($this->request->server['REQUEST_METHOD'] == 'POST') {
                $currency->address = $_POST['address'][$item->abbr];
                if ($currency->address != '') {
                $result = \ApironeApi\Apirone::setTransferAddress($account, $item->abbr, $currency->address);
                        if ($result == false) {
                        $currency->error = 1;
                        $errors_count++;
                    }
                }
            }
            // Set tooltip
            if (empty($currency->address))
                $currency->currency_tooltip = sprintf($this->language->get('currency_activate_tooltip'), $item->name);
            else {
                $currency->currency_tooltip = sprintf($this->language->get('currency_deactivate_tooltip'), $item->name);
                $active_currencies++;
            }
            $currencies[$item->abbr] = $currency;
        }

        // Set values into template vars
        $this->setValue($data, 'payment_apirone_mccp_version');
        $this->setValue($data, 'payment_apirone_mccp_timeout', true);
        $this->setValue($data, 'payment_apirone_mccp_invoice_created_status_id');
        $this->setValue($data, 'payment_apirone_mccp_invoice_paid_status_id');
        $this->setValue($data, 'payment_apirone_mccp_invoice_partpaid_status_id');
        $this->setValue($data, 'payment_apirone_mccp_invoice_overpaid_status_id');
        $this->setValue($data, 'payment_apirone_mccp_invoice_completed_status_id');
        $this->setValue($data, 'payment_apirone_mccp_invoice_expired_status_id');
        $this->setValue($data, 'payment_apirone_mccp_geo_zone_id');
        $this->setValue($data, 'payment_apirone_mccp_status');
        $this->setValue($data, 'payment_apirone_mccp_sort_order');
        $this->setValue($data, 'payment_apirone_mccp_merchantname');
        $this->setValue($data, 'payment_apirone_mccp_secret');
        $this->setValue($data, 'payment_apirone_mccp_testcustomer');

        if ($active_currencies == 0 || $data['payment_apirone_mccp_timeout'] <= 0 ) {
            $errors_count++;            
        }

        $errors_count = $errors_count + count($this->error);

        // Save settings if post & no errors
        $this->load->model('setting/setting');
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

            $json = [];
            if ($errors_count == 0) {   
                $_settings['payment_apirone_mccp_account'] = PLUGIN_VERSION;         
                $_settings['payment_apirone_mccp_account'] = serialize($account);
                $_settings['payment_apirone_mccp_secret'] = $secret;
                $_settings['payment_apirone_mccp_currencies'] = serialize($currencies);

                $_settings['payment_apirone_mccp_timeout'] = $_POST['payment_apirone_mccp_timeout'];
                $_settings['payment_apirone_mccp_invoice_created_status_id'] = $_POST['payment_apirone_mccp_invoice_created_status_id'];
                $_settings['payment_apirone_mccp_invoice_paid_status_id'] = $_POST['payment_apirone_mccp_invoice_paid_status_id'];
                $_settings['payment_apirone_mccp_invoice_partpaid_status_id'] = $_POST['payment_apirone_mccp_invoice_partpaid_status_id'];
                $_settings['payment_apirone_mccp_invoice_overpaid_status_id'] = $_POST['payment_apirone_mccp_invoice_overpaid_status_id'];
                $_settings['payment_apirone_mccp_invoice_completed_status_id'] = $_POST['payment_apirone_mccp_invoice_completed_status_id'];
                $_settings['payment_apirone_mccp_invoice_expired_status_id'] = $_POST['payment_apirone_mccp_invoice_expired_status_id'];
                $_settings['payment_apirone_mccp_geo_zone_id'] = $_POST['payment_apirone_mccp_geo_zone_id'];
                $_settings['payment_apirone_mccp_status'] = $_POST['payment_apirone_mccp_status'];
                $_settings['payment_apirone_mccp_sort_order'] = $_POST['payment_apirone_mccp_sort_order'];
                $_settings['payment_apirone_mccp_merchantname'] = $_POST['payment_apirone_mccp_merchantname'];
                $_settings['payment_apirone_mccp_testcustomer'] = $_POST['payment_apirone_mccp_testcustomer'];

                $this->model_setting_setting->editSetting('payment_apirone_mccp', $_settings);
            }
            else {
                // No addresses
                if (count($currencies) == 0 ) {
                    $json['error']['warning'] = $this->language->get('error_service_not_available');
                } else {
                    if ($active_currencies == 0 ) {
                        $json['error']['warning'] = $this->language->get('error_empty_currencies');
                    }
                }
                // Wrong addresses
                foreach ($currencies as $key => $currency) {
                    if (property_exists($currency, 'error')) {
                        $json['error']['address_' . $key] = $this->language->get('currency_address_incorrect');
                    }
                }
                // Payment timeout
                if($data['payment_apirone_mccp_timeout'] == '') {
                    $json['error']['timeout'] = $this->language->get('error_apirone_mccp_timeout');
                }
                if($data['payment_apirone_mccp_timeout'] <= 0) {
                    $json['error']['timeout'] = $this->language->get('error_apirone_mccp_timeout_positive');
                }
            }

            if (!$json) {
                $this->load->model('setting/setting');

                $this->model_setting_setting->editSetting('payment_bank_transfer', $this->request->post);

                $json['success'] = $this->language->get('text_success');
            }

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // =============================================================================================
        // Set template variables

        $this->document->setTitle($this->language->get('heading_title'));

        $data = array_merge($data, $this->load->language('apirone_mccp'));

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['order_statuses'][] = ['order_status_id' => 0, 'name' => $this->language->get('text_missing')];

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $data['currencies'] = $currencies;
        // Can't get currency list
        if (count($currencies) == 0) {
            $data['error'] = $this->language->get('error_service_not_available');
        }
        

        $this->getBreadcrumbsAndActions($data);
        $data['errors'] = $this->error;
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/apirone/payment/apirone_mccp', $data));
    }

    protected function validate() {
        
    }

    protected function getBreadcrumbsAndActions(&$data) {
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token='. $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token='. $this->session->data['user_token'] . '&type=payment', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/apirone/payment/apirone_mccp', 'user_token='. $this->session->data['user_token'], true)
        );

        $data['save'] = $this->url->link('extension/apirone/payment/apirone_mccp', 'user_token='. $this->session->data['user_token'], true);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token='. $this->session->data['user_token'] . '&type=payment', true);
    }

    protected function setValue(&$data, $value, $required = false) {
        if (isset($this->request->post[$value])) {
            $data[$value] = $this->request->post[$value];
        }
        else {
            $data[$value] = $this->config->get($value);
        }
        if ($required && empty($data[$value])) {
            $this->error[$value] = $this->language->get(str_replace('payment', 'error', $value));
        }
    }

    // Install / Uninstall plugin
    public function install(): void {

        $this->load->model('extension/apirone/payment/apirone_mccp');
        $this->load->model('setting/setting');

        $data = array(
            'payment_apirone_mccp_secret' => md5(time(). $this->session->data['user_token']),
            'payment_apirone_mccp_version' => PLUGIN_VERSION,
            'payment_apirone_mccp_invoice_created_status_id' => '1',
            'payment_apirone_mccp_invoice_paid_status_id' => '1',
            'payment_apirone_mccp_invoice_partpaid_status_id' => '1',
            'payment_apirone_mccp_invoice_overpaid_status_id' => '1',
            'payment_apirone_mccp_invoice_completed_status_id' => '5',
            'payment_apirone_mccp_invoice_expired_status_id' => '16',
            'payment_apirone_mccp_timeout' => '1800',
            'payment_apirone_mccp_sort_order' => '0',
        );

        $account = \ApironeApi\Apirone::accountCreate();

        if($account) {
            $data['payment_apirone_mccp_account']  = serialize($account);
        }

        $this->model_setting_setting->editSetting('payment_apirone_mccp', $data);

        $query = \ApironeApi\Db::createInvoicesTableQuery(DB_PREFIX);
        $this->model_extension_apirone_payment_apirone_mccp->install_invoices_table($query);
    }

    public function uninstall(): void {
        $this->load->model('extension/apirone/payment/apirone_mccp');
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('payment_apirone_mccp');

        $query = \ApironeApi\Db::deleteInvoicesTableQuery(DB_PREFIX);
        $this->model_extension_apirone_payment_apirone_mccp->delete_invoices_table($query);
    }

    private function update(): void {
        $this->load->model('setting/setting');
        $version = $this->model_setting_setting->getValue('payment_apirone_mccp_version');

        if ($version == '') {
            $this->upd_1_0_1__1_1_0();
            $version = '1.1.0';
        }
        if ($version == '1.1.0') {
            $this->upd_1_1_0__1_1_1();
            $version = '1.1.1';
        }

        return;
    }

    private function upd_1_0_1__1_1_0(): void {
        $current = $this->model_setting_setting->getSetting('payment_apirone_mccp');

        $data = $current;

        $pending = array_key_exists('payment_apirone_mccp_pending_status_id', $current) ? $current['payment_apirone_mccp_pending_status_id'] : 1;
        $completed = array_key_exists('payment_apirone_mccp_completed_status_id', $current) ? $current['payment_apirone_mccp_completed_status_id'] : 5;
        $voided = array_key_exists('payment_apirone_mccp_voided_status_id', $current) ? $current['payment_apirone_mccp_voided_status_id'] : 16;

        // Add new settings
        $data['payment_apirone_mccp_version'] = '1.1.0';
        $data['payment_apirone_mccp_invoice_created_status_id'] = $pending;
        $data['payment_apirone_mccp_invoice_paid_status_id'] = $pending;
        $data['payment_apirone_mccp_invoice_partpaid_status_id'] = $pending;
        $data['payment_apirone_mccp_invoice_overpaid_status_id'] = $pending;
        $data['payment_apirone_mccp_invoice_completed_status_id'] = $completed;
        $data['payment_apirone_mccp_invoice_expired_status_id'] = $voided;

        // Remove old settings
        unset($data['payment_apirone_mccp_status_id']);
        unset($data['payment_apirone_mccp_pending_status_id']);
        unset($data['payment_apirone_mccp_voided_status_id']);

        $this->model_setting_setting->editSetting('payment_apirone_mccp', $data);
    }

    private function upd_1_1_0__1_1_1() {
        $data = $this->model_setting_setting->getSetting('payment_apirone_mccp');
        $data['payment_apirone_mccp_version'] = '1.1.1';

        $this->model_setting_setting->editSetting('payment_apirone_mccp', $data);
    }

}
