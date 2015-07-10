<?php

require_once dirname(dirname(dirname(__FILE__))) . '/metadata.php';
require_once dirname(dirname(dirname(__FILE__))) . '/lib/Services/Paymill/Webhooks.php';

/**
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (http://www.paymill.com)
 */
abstract class ControllerPaymentPaymill extends Controller
{

    abstract protected function getPaymentName();

    public function getVersion()
    {
        $metadata = new metadata();
        return $metadata->getVersion();
    }

    public function index()
    {
        global $config;
        $this->language->load('payment/' . $this->getPaymentName());
        $this->document->setTitle($this->language->get('heading_title') . " (" . $this->getVersion() . ")");
        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
            $this->data['base'] = $this->config->get('config_ssl');
        } elseif(!is_null($this->config->get('config_url'))) {
            $this->data['base'] = $this->config->get('config_url');
        } else{
            $this->data['base'] = preg_replace("/admin\/index\.php/", "", $this->request->server['SCRIPT_NAME']); //shoproot
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');

            $newConfig[$this->getPaymentName() . '_status'] = $this->getPostValue('paymill_status', 0);
            $newConfig[$this->getPaymentName() . '_publickey'] = trim($this->getPostValue('paymill_publickey', ''));
            $newConfig[$this->getPaymentName() . '_privatekey'] = trim($this->getPostValue('paymill_privatekey', ''));
            if($this->getPaymentName() === 'paymillcreditcard') {
                $newConfig[$this->getPaymentName() . '_pci'] = trim($this->getPostValue('paymill_pci', ''));
            }
            $newConfig[$this->getPaymentName() . '_sort_order'] = $this->getPostValue('paymill_sort_order', 0);
            $newConfig[$this->getPaymentName() . '_preauth'] = $this->getPostValue('paymill_preauth', false);
            $newConfig[$this->getPaymentName() . '_fast_checkout'] = $this->getPostValue('paymill_fast_checkout', false);
            $newConfig[$this->getPaymentName() . '_logging'] = $this->getPostValue('paymill_logging', false);
            $newConfig[$this->getPaymentName() . '_debugging'] = $this->getPostValue('paymill_debugging', false);
            $newConfig[$this->getPaymentName() . '_buttonSolution'] = $this->getPostValue('paymill_buttonSolution', false);
            $newConfig[$this->getPaymentName() . '_sepa_date'] = $this->getPostValue('paymill_sepa_date');
            $newConfig[$this->getPaymentName() . '_icon_visa'] = $this->getPostValue('icon_visa');
            $newConfig[$this->getPaymentName() . '_icon_master'] = $this->getPostValue('icon_master');
            $newConfig[$this->getPaymentName() . '_icon_amex'] = $this->getPostValue('icon_amex');
            $newConfig[$this->getPaymentName() . '_icon_jcb'] = $this->getPostValue('icon_jcb');
            $newConfig[$this->getPaymentName() . '_icon_maestro'] = $this->getPostValue('icon_maestro');
            $newConfig[$this->getPaymentName() . '_icon_diners_club'] = $this->getPostValue('icon_diners_club');
            $newConfig[$this->getPaymentName() . '_icon_discover'] = $this->getPostValue('icon_discover');
            $newConfig[$this->getPaymentName() . '_icon_china_unionpay'] = $this->getPostValue('icon_china_unionpay');
            $newConfig[$this->getPaymentName() . '_icon_dankort'] = $this->getPostValue('icon_dankort');
            $newConfig[$this->getPaymentName() . '_icon_carta_si'] = $this->getPostValue('icon_carta_si');
            $newConfig[$this->getPaymentName() . '_icon_carte_bleue'] = $this->getPostValue('icon_carte_bleue');

            $this->model_setting_setting->editSetting($this->getPaymentName(), $newConfig);
            $this->addPaymillWebhook($newConfig[$this->getPaymentName() . '_privatekey']);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/payment', '&token=' . $this->session->data['token']));
        }

        $this->data['breadcrumbs'] = $this->getBreadcrumbs();
        $this->data['heading_title'] = $this->language->get('heading_title') . " (" . $this->getVersion() . ")";
        $this->data['paymill_image_folder'] = '/catalog/view/theme/default/image/payment';

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_payment'] = $this->language->get('text_payment');
        $this->data['text_success'] = $this->language->get('text_success');
        $this->data['text_paymill'] = $this->language->get('text_paymill');
        $this->data['text_sale'] = $this->language->get('text_sale');
        $this->data['text_sale'] = $this->language->get('text_sale');
        $this->data['text_pci_saq_a'] = $this->language->get('text_pci_saq_a');
        $this->data['text_pci_saq_a_ep'] = $this->language->get('text_pci_saq_a_ep');

        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_publickey'] = $this->language->get('entry_publickey');
        $this->data['entry_privatekey'] = $this->language->get('entry_privatekey');
        $this->data['entry_pci'] = $this->language->get('entry_pci');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $this->data['entry_fast_checkout'] = $this->language->get('entry_fast_checkout');
        $this->data['entry_preauth'] = $this->language->get('entry_preauth');
        $this->data['entry_logging'] = $this->language->get('entry_logging');
        $this->data['entry_debugging'] = $this->language->get('entry_debugging');
        $this->data['entry_buttonSolution'] = $this->language->get('entry_buttonSolution');
        $this->data['entry_sepa_date'] = $this->language->get('entry_sepa_date');
        $this->data['entry_specific_creditcard'] = $this->language->get('entry_specific_creditcard');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');
        $this->data['button_logging'] = $this->language->get('button_logging');

        $this->data['action'] = $this->url->link('payment/' . $this->getPaymentName(), '&token=' . $this->session->data['token']);
        $this->data['cancel'] = $this->url->link('extension/payment', '&token=' . $this->session->data['token']);
        $this->data['logging'] = $this->url->link('custom/paymillLogging', '&token=' . $this->session->data['token']);

        $this->data['paymill_status'] = $this->getConfigValue($this->getPaymentName() . '_status');
        $this->data['paymill_publickey'] = $this->getConfigValue($this->getPaymentName() . '_publickey');
        $this->data['paymill_privatekey'] = $this->getConfigValue($this->getPaymentName() . '_privatekey');

        if($this->getPaymentName() === 'paymillcreditcard') {
            $this->data['paymill_pci'] = $this->getConfigValue($this->getPaymentName() . '_pci');
        }

        $this->data['paymill_sort_order'] = $this->getConfigValue($this->getPaymentName() . '_sort_order');
        $this->data['paymill_fast_checkout'] = $this->getConfigValue($this->getPaymentName() . '_fast_checkout');
        $this->data['paymill_preauth'] = $this->getConfigValue($this->getPaymentName() . '_preauth');
        $this->data['paymill_logging'] = $this->getConfigValue($this->getPaymentName() . '_logging');
        $this->data['paymill_debugging'] = $this->getConfigValue($this->getPaymentName() . '_debugging');
        $this->data['paymill_buttonSolution'] = $this->getConfigValue($this->getPaymentName() . '_buttonSolution');
        $this->data['paymill_sepa_date'] = $this->getConfigValue($this->getPaymentName() . '_sepa_date');
        $this->data['paymill_creditcardicons'] = $this->getConfigValue($this->getPaymentName() . '_creditcardicons');
        $this->data['paymill_payment'] = $this->getPaymentName();
        $this->data['paymill_icon_visa'] = $this->getConfigValue($this->getPaymentName() . '_icon_visa');
        $this->data['paymill_icon_master'] = $this->getConfigValue($this->getPaymentName() . '_icon_master');
        $this->data['paymill_icon_amex'] = $this->getConfigValue($this->getPaymentName() . '_icon_amex');
        $this->data['paymill_icon_jcb'] = $this->getConfigValue($this->getPaymentName() . '_icon_jcb');
        $this->data['paymill_icon_maestro'] = $this->getConfigValue($this->getPaymentName() . '_icon_maestro');
        $this->data['paymill_icon_diners_club'] = $this->getConfigValue($this->getPaymentName() . '_icon_diners_club');
        $this->data['paymill_icon_discover'] = $this->getConfigValue($this->getPaymentName() . '_icon_discover');
        $this->data['paymill_icon_china_unionpay'] = $this->getConfigValue($this->getPaymentName() . '_icon_china_unionpay');
        $this->data['paymill_icon_dankort'] = $this->getConfigValue($this->getPaymentName() . '_icon_dankort');
        $this->data['paymill_icon_carta_si'] = $this->getConfigValue($this->getPaymentName() . '_icon_carta_si');
        $this->data['paymill_icon_carte_bleue'] = $this->getConfigValue($this->getPaymentName() . '_icon_carte_bleue');

        $this->template = 'payment/' . $this->getPaymentName() . '.tpl';
        $this->children = array(
            'common/header',
            'common/footer'
        );
        $this->response->setOutput($this->render(true), $this->config->get('config_compression'));
    }

    protected function getBreadcrumbs()
    {
        $breadcrumbs = array();
        $breadcrumbs[] = array(
            'href' => $this->url->link('common/home', '&token=' . $this->session->data['token']),
            'text' => $this->language->get('text_home'),
            'separator' => FALSE
        );

        $breadcrumbs[] = array(
            'href' => $this->url->link('extension/payment', '&token=' . $this->session->data['token']),
            'text' => $this->language->get('text_payment'),
            'separator' => ' :: '
        );

        $breadcrumbs[] = array(
            'href' => $this->url->link('payment/' . $this->getPaymentName(), '&token=' . $this->session->data['token']),
            'text' => $this->language->get('heading_title'),
            'separator' => ' :: '
        );
        return $breadcrumbs;
    }

    protected function getConfigValue($configField)
    {
        if (isset($this->request->post[$configField])) {
            return $this->request->post[$configField];
        } else {
            return $this->config->get($configField);
        }
    }

    protected function getPostValue($configField)
    {
        $result = $this->getConfigValue($configField);
        if (isset($this->request->post[$configField])) {
            $result = $this->request->post[$configField];
        }
        return $result;
    }

    protected function validate()
    {
        $validation = true;
        $publickey = $this->request->post['paymill_publickey'];
        $privatekey = $this->request->post['paymill_privatekey'];

        if (!$this->user->hasPermission('modify', 'payment/' . $this->getPaymentName())) {
            $this->data['error_warning'] = $this->language->get('error_permission');
            $validation = false;
        }

        if (isset($this->request->post['paymill_differnet_amount'])) {
            if (!is_numeric($this->request->post['paymill_differnet_amount'])) {
                $this->data['error_warning'] = $this->language->get('error_different_amount');
                $validation = false;
            }
        }

        if (empty($publickey)) {
            $this->data['error_warning'] = $this->language->get('error_missing_publickey');
            $validation = false;
        }

        if (empty($privatekey)) {
            $this->data['error_warning'] = $this->language->get('error_missing_privatekey');
            $validation = false;
        }
        return $validation;
    }

    public function install()
    {
        $config[$this->getPaymentName() . '_status'] = '0';
        $config[$this->getPaymentName() . '_publickey'] = '';
        $config[$this->getPaymentName() . '_privatekey'] = '';

        if($this->getPaymentName() === 'paymillcreditcard') {
            $config[$this->getPaymentName() . '_pci'] = '0';
        }

        $config[$this->getPaymentName() . '_sort_order'] = '1';
        $config[$this->getPaymentName() . '_fast_checkout'] = '0';
        $config[$this->getPaymentName() . '_preauth'] = '0';
        $config[$this->getPaymentName() . '_different_amount'] = '0.00';
        $config[$this->getPaymentName() . '_logging'] = '1';
        $config[$this->getPaymentName() . '_debugging'] = '1';
        $config[$this->getPaymentName() . '_buttonSolution'] = '0';
        $config[$this->getPaymentName() . '_sepa_date'] = '7';
        $config[$this->getPaymentName() . '_icon_visa'] = '1';
        $config[$this->getPaymentName() . '_icon_master'] = '1';
        $config[$this->getPaymentName() . '_icon_amex'] = '1';
        $config[$this->getPaymentName() . '_icon_jcb'] = '1';
        $config[$this->getPaymentName() . '_icon_maestro'] = '1';
        $config[$this->getPaymentName() . '_icon_diners_club'] = '1';
        $config[$this->getPaymentName() . '_icon_discover'] = '1';
        $config[$this->getPaymentName() . '_icon_china_unionpay'] = '1';
        $config[$this->getPaymentName() . '_icon_dankort'] = '1';
        $config[$this->getPaymentName() . '_icon_carta_si'] = '1';
        $config[$this->getPaymentName() . '_icon_carte_bleue'] = '1';

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting($this->getPaymentName(), $config);

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pigmbh_paymill_logging` ("
            . "`id` int(11) NOT NULL AUTO_INCREMENT,"
            . "`identifier` text NOT NULL,"
            . "`debug` text NOT NULL,"
            . "`message` text NOT NULL,"
            . "`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,"
            . "PRIMARY KEY (`id`)"
            . ") AUTO_INCREMENT=1");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "pigmbh_paymill_orders` ("
            . "`order_id` int(11) NOT NULL,"
            . "`preauth_id` varchar(100) NOT NULL,"
            . "`transaction_id` varchar(100) NOT NULL,"
            . "`refund_amount` DECIMAL(2) NOT NULL DEFAULT 0,"
	        . "PRIMARY KEY (`order_id`)"
            . ")");

    }

    protected function addPaymillWebhook($privateKey)
    {
        $webhookObject = new Services_Paymill_Webhooks($privateKey, 'https://api.paymill.com/v2/');
        $url = $this->url->link('payment/' . $this->getPaymentName() . '/webHookEndpoint');
        $webhookUrl = str_replace('/admin', '', $url);
        $webhookObject->create(array(
            "url" => $webhookUrl,
            "event_types" => array('refund.succeeded')
        ));
    }

    public function uninstall()
    {

    }

}
