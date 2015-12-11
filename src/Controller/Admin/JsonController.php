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
namespace Module\Shop\Controller\Admin;

use Pi;
use Pi\Mvc\Controller\ActionController;
use Module\Guide\Form\SitemapForm;
use Module\Guide\Form\RegenerateImageForm;

class JsonController extends ActionController
{
    public function indexAction()
    {
        // Get info from url
        $module = $this->params('module');
        // Get config
        $config = Pi::service('registry')->config->read($module);
        // Get config
        $links = array();

        $links['productAll'] = Pi::url($this->url('shop', array(
            'module' => $module,
            'controller' => 'json',
            'action' => 'productAll',
            'update' => strtotime("11-12-10"),
            'password' => (!empty($config['json_password'])) ? $config['json_password'] : '',
        )));

        $links['productCategory'] = Pi::url($this->url('shop', array(
            'module' => $module,
            'controller' => 'json',
            'action' => 'productCategory',
            'id' => 1,
            'update' => strtotime("11-12-10"),
            'password' => (!empty($config['json_password'])) ? $config['json_password'] : '',
        )));

        $links['productSingle'] = Pi::url($this->url('shop', array(
            'module' => $module,
            'controller' => 'json',
            'action' => 'productSingle',
            'id' => 1,
            'password' => (!empty($config['json_password'])) ? $config['json_password'] : '',
        )));

        // Set template
        $this->view()->setTemplate('json-index');
        $this->view()->assign('links', $links);
    }
}