<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

namespace Module\Shop\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Zend\Json\Json;

class CartController extends ActionController
{
    public function indexAction()
    {
        // Check user is login or not
        //Pi::service('authentication')->requireLogin();
        // Check order is active or inactive
        if (!$this->config('order_active')) {
            $this->getResponse()->setStatusCode(401);
            $this->terminate(__('So sorry, At this moment order is inactive'), '', 'error-404');
            $this->view()->setLayout('layout-simple');
            return;
        }
        // Get basket
        $basket = Pi::api('basket', 'shop')->getBasket();
        // Check basket
        if (empty($basket) || empty($basket['products'])) {
            $module = $this->params('module');
            $url = array('', 'module' => $module, 'controller' => 'index');
            $this->jump($url, __('Your cart are empty.'), 'error');
        }
        // Set view
        $this->view()->setTemplate('checkout-cart');
        $this->view()->assign('basket', $basket);
    }

    public function addAction()
    {
        // Get module
        $module = $this->params('module');
        // Get config
        $config = Pi::service('registry')->config->read($module);
        // Check order is active or inactive
        if (!$config['order_active']) {
            $this->getResponse()->setStatusCode(401);
            $this->terminate(__('So sorry, At this moment order is inactive'), '', 'error-404');
            $this->view()->setLayout('layout-simple');
            return;
        }
        // Check post
        if ($this->request->isPost()) {
            // Get post
            $data = $this->request->getPost()->toArray();
            //$this->view()->assign('test', $data);
            //$this->view()->setTemplate('empty');
            // Find product
            $product = Pi::api('product', 'shop')->getProductLight($data['id']);
            // Check product
            if (!in_array($product['marketable'], array(1, 2))) {
                $message = __('The product was not marketable.');
                $this->jump($product['productUrl'], $message, 'error');
            } else {
                // Check color
                $property = array();
                if (isset($data['property']) && !empty($data['property'])) {
                    $property = $data['property'];
                }
                // Set basket
                Pi::api('basket', 'shop')->setBasket($product['id'], 1, $property, $product['marketable']);
                // Go to cart
                $url = array('', 'module' => $module, 'controller' => 'cart', 'action' => 'index');
                return $this->redirect()->toRoute('', $url);
            }
        } else {
            // Go to cart
            $url = array('', 'module' => $module, 'controller' => 'cart', 'action' => 'index');
            return $this->redirect()->toRoute('', $url);
        }
    }

    public function updateAction()
    {
        // Get basket
        $basket = Pi::api('basket', 'shop')->getBasket();
        return array(
            'status' => 1,
            'price' => $basket['total']['price_view'],
            'discount' => $basket['total']['discount_view'],
            'number' => $basket['total']['number_view'],
            'total' => $basket['total']['total_price_view'],
        );
    }

    public function emptyAction()
    {
        // Set empty
        Pi::api('basket', 'shop')->emptyBasket();
        // Back
        $module = $this->params('module');
        $url = array('', 'module' => $module, 'controller' => 'index', 'action' => 'index');
        $this->jump($url, __('Your cart are empty'), 'success');
    }

    public function basketAction()
    {
        // Get basket
        $basket = Pi::api('basket', 'shop')->getBasket();
        // Get info from url
        $process = $this->params('process');
        $product = $this->params('product');
        $module = $this->params('module');
        // Set return
        $return = array();
        $return['message'] = __('Please select product');
        $return['id'] = $product;
        $return['ajaxStatus'] = 0;
        $return['actionStatus'] = 0;
        $return['actionName'] = $process;
        // process
        switch ($process) {
            case 'remove':
                Pi::api('basket', 'shop')->removeProduct($product);
                $return['message'] = __('Selected product removed from your cart');
                $return['ajaxStatus'] = 1;
                $return['actionStatus'] = 1;
                break;

            case 'number':
                $number = $basket['products'][$product]['number'];
                if ($number > 0) {
                    $getNumber = $this->params('number');
                    $newNumber = $number + $getNumber;
                    if ($newNumber > 0) {
                        // Update number
                        $newTotal = $newNumber * $basket['products'][$product]['price'];
                        Pi::api('basket', 'shop')->updateBasket($product, $newNumber);
                        // Set return
                        $return['message'] = __('Update number');
                        $return['actionNumber'] = $newNumber;
                        $return['ajaxStatus'] = 1;
                        $return['actionStatus'] = 1;
                        $return['actionTotal'] = Pi::api('api', 'shop')->viewPrice($newTotal);
                    } else {
                        $return['message'] = __('You can not set product number to 0');
                        $return['ajaxStatus'] = 1;
                        $return['actionStatus'] = 0;
                    }
                }
                break;
        }
        // return
        return $return;
    }

    public function completeAction()
    {
        // Set template
        $this->view()->setTemplate(false);
        // Get basket
        $basket = Pi::api('basket', 'shop')->getBasket();
        // Set can pay array
        $canPay = array();
        // Set order array
        $order = array();
        $order['module_name'] = $this->params('module');
        $order['type_payment'] = $this->config('order_type');
        $order['type_commodity'] = 'product';
        // Set products to order
        foreach ($basket['products'] as $product) {
            // Set single product
            $singelProduct = array(
                'product' => $product['id'],
                'product_price' => $product['price'],
                'discount_price' => 0,
                'shipping_price' => $product['price_shipping'],
                'packing_price' => 0,
                'vat_price' => 0,
                'number' => $product['number'],
                'title' => $product['title'],
                'extra' => json::encode($product['property']),
            );
            $order['product'][$product['id']] = $singelProduct;
            $canPay[$product['id']] = $product['can_pay'];
        }
        // Check can pay or not
        $order['can_pay'] = (in_array(2, $canPay)) ? 2 : 1;
        // Unset shop session
        Pi::api('basket', 'shop')->emptyBasket();
        // Set and go to order
        $url = Pi::api('order', 'order')->setOrderInfo($order);
        Pi::service('url')->redirect($url);
    }

    /* public function finishAction()
    {
        // Check user is login or not
        Pi::service('authentication')->requireLogin();
        // Check order is active or inactive
        if (!$this->config('order_active')) {
            $url = array('', 'module' => $this->params('module'), 'controller' => 'index', 'action' => 'index');
            $this->jump($url, __('So sorry, At this moment order is inactive'));
        }
        // Get info from url
        $id = $this->params('id');
        $module = $this->params('module');
        // Find order
        $order = Pi::api('order', 'order')->getOrder($id);
        // Check order
        if (!$order['id']) {
            $url = array('', 'module' => $module, 'controller' => 'index', 'action' => 'index');
            $this->jump($url, __('Order not set.'));
        }
        // Check user
        if ($order['uid'] != Pi::user()->getId()) {
            $url = array('', 'module' => $module, 'controller' => 'index', 'action' => 'index');
            $this->jump($url, __('It not your order.'));
        }
        // Check status payment
        if ($order['status_payment'] != 2) {
            $url = array('', 'module' => $module, 'controller' => 'index', 'action' => 'index');
            $this->jump($url, __('This order not pay'));
        }
        // Check time payment
        $time = time() - 3600;
        if ($time > $order['time_payment']) {
            $url = array('', 'module' => $module, 'controller' => 'index', 'action' => 'index');
            $this->jump($url, __('This is old order and you pay it before'));
        }
        // Set links
        $order['order_link'] = Pi::url($this->url('order', array(
            'module' => 'order',
            'controller' => 'detail',
            'action' => 'index',
            'id' => $order['id']
        )));
        $order['user_link'] = Pi::url($this->url('order', array(
            'module' => 'order',
            'controller' => 'index',
            'action' => 'index',
        )));
        $order['index_link'] = Pi::url($this->url('', array(
            'module' => $module,
            'controller' => 'index',
            'action' => 'index',
        )));
        // Get invoice information
        Pi::service('i18n')->load(array('module/order', 'default'));
        $invoices = Pi::api('invoice', 'order')->getInvoiceFromOrder($order['id']);
        // Set view
        $this->view()->setTemplate('checkout-finish');
        $this->view()->assign('order', $order);
        $this->view()->assign('invoices', $invoices);
    } */

    /* protected function setEmpty()
    {
        if (isset($_SESSION['shop']['cart'])) {
            unset($_SESSION['shop']['cart']);
        }
    } */

    /* protected function setInvoice()
    {    
    	// Get cart
        $cart = $_SESSION['shop']['cart'];
        // Set invoice
        $invoice = array();
        $invoice['total']['price'] = 0;
        $invoice['total']['number'] = 0;
        $invoice['total']['discount'] = 0;
        $invoice['total']['shipping'] = 0;
        //$invoice['total']['plan'] = 0;
        // Set invoice product
    	foreach ($cart['product'] as $product) {
    		// Set product item
            $item = array();
            $item['id'] = $product['id'];
            $item['number'] = $product['number'];
            $item['price'] = $product['price'];
            $item['price_view'] = Pi::api('api', 'shop')->viewPrice($item['price']);
            $item['discount'] = 0;
            $item['total'] = ($product['number'] * $product['price']);
            $item['total_view'] = Pi::api('api', 'shop')->viewPrice($item['total']);
            $invoice['product'][$product['id']] = $item;
            // Set total
    		$invoice['total']['price'] = $invoice['total']['price'] + ($product['price'] * $product['number']);
    		$invoice['total']['number'] = $invoice['total']['number'] + $product['number'];
    		$invoice['total']['discount'] = $invoice['total']['discount'] + 0;
            // Update cart product
            $cart['product'][$item['id']]['total'] = $item['total'];
            $cart['product'][$item['id']]['total_view'] = $item['total_view'];
            // Set plan
            //$invoice['total']['plan'] = $product['plan'];
    	}
        // Set price
        //$invoice['total']['location'] = (isset($cart['invoice']['total']['location'])) ? $cart['invoice']['total']['location'] : 0;
        //$invoice['total']['delivery'] = (isset($cart['invoice']['total']['delivery'])) ? $cart['invoice']['total']['delivery'] : 0;
        //$invoice['total']['payment'] = (isset($cart['invoice']['total']['payment'])) ? $cart['invoice']['total']['payment'] : 'offline';
        //$invoice['total']['location_title'] = (isset($cart['invoice']['total']['location_title'])) ? $cart['invoice']['total']['location_title'] : '';
        //$invoice['total']['delivery_title'] = (isset($cart['invoice']['total']['delivery_title'])) ? $cart['invoice']['total']['delivery_title'] : '';
        //$invoice['total']['payment_title'] = (isset($cart['invoice']['total']['payment_title'])) ? $cart['invoice']['total']['payment_title'] : __('Offline');
        $invoice['total']['shipping'] = (isset($cart['invoice']['total']['shipping'])) ? intval($cart['invoice']['total']['shipping']) : 0;
        $invoice['total']['total_price'] = intval($invoice['total']['price'] - $invoice['total']['discount']) + $invoice['total']['shipping'];
        $invoice['total']['price_view'] = Pi::api('api', 'shop')->viewPrice($invoice['total']['price']);
    	$invoice['total']['discount_view'] = Pi::api('api', 'shop')->viewPrice($invoice['total']['discount']);
        $invoice['total']['shipping_view'] = Pi::api('api', 'shop')->viewPrice($invoice['total']['shipping']);
    	$invoice['total']['total_price_view'] = Pi::api('api', 'shop')->viewPrice($invoice['total']['total_price']);
    	// Set Seaaion
    	$cart['invoice'] = $invoice;
        $_SESSION['shop']['cart'] = $cart;
        // return invoice
        return $invoice;
    } */

    /* protected function setProduct($product)
    {
        $cart = $_SESSION['shop']['cart'];
        // Set number
        if (isset($cart['product'][$product['id']]) 
            && !empty($cart['product'][$product['id']])) 
        {
            if (isset($cart['product'][$product['id']]['number']) 
                && !empty($cart['product'][$product['id']]['number'])) 
            {
                $product['number'] = $cart['product'][$product['id']]['number'] + 1;
            } else {
                $product['number'] = 1;
            }
        } else {
            $product['number'] = 1;
        }
        // Set total price
        $product['total'] = intval(($product['price'] * $product['number']));
        $product['total_view'] = Pi::api('api', 'shop')->viewPrice($product['total']);
        // Set session
        $cart['product'][$product['id']] = $product;
        $_SESSION['shop']['cart'] = $cart;
    } */
}