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

namespace Module\Shop\Form;

use Pi;
use Pi\Form\Form as BaseForm;

class PropertyForm  extends BaseForm
{
    public function __construct($name = null, $option = array())
    {
        parent::__construct($name);
    }

    public function getInputFilter()
    {
        if (!$this->filter) {
            $this->filter = new PropertyFilter;
        }
        return $this->filter;
    }

    public function init()
    {
        // id
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));
        // title
        $this->add(array(
            'name' => 'title',
            'options' => array(
                'label' => __('Title'),
            ),
            'attributes' => array(
                'type' => 'text',
                'description' => '',
                'required' => true,

            )
        ));
        // order
        $this->add(array(
            'name' => 'order',
            'options' => array(
                'label' => __('Order'),
            ),
            'attributes' => array(
                'type' => 'text',
                'description' => '',

            )
        ));
        // status
        $this->add(array(
            'name' => 'status',
            'type' => 'select',
            'options' => array(
                'label' => __('Status'),
                'value_options' => array(
                    1 => __('Published'),
                    2 => __('Pending review'),
                    3 => __('Draft'),
                    4 => __('Private'),
                    5 => __('Delete'),
                ),
            ),
        ));
        // influence_stock
        $this->add(array(
            'name' => 'influence_stock',
            'type' => 'checkbox',
            'options' => array(
                'label' => __('Influence stock'),
            ),
            'attributes' => array(
                'description' => '',
            )
        ));
        // influence_price
        $this->add(array(
            'name' => 'influence_price',
            'type' => 'checkbox',
            'options' => array(
                'label' => __('Influence price'),
            ),
            'attributes' => array(
                'description' => '',
            )
        ));
        // type
        $this->add(array(
            'name' => 'type',
            'type' => 'select',
            'options' => array(
                'label' => __('Type'),
                'value_options' => array(
                    'checkbox' => __('CheckBox'),
                    'selectbox' => __('SelectBox'),
                ),
            ),
        ));
        // Save
        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => __('Submit'),
            )
        ));
    }
}