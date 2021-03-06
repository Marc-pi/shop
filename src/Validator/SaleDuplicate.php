<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt New BSD License
 */

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */

namespace Module\Shop\Validator;

use Pi;
use Zend\Validator\AbstractValidator;

class SaleDuplicate extends AbstractValidator
{
    const TAKEN = 'nameExists';

    /**
     * @var array
     */
    protected $messageTemplates
        = [
            self::TAKEN => 'This product added on sale list before',
        ];

    protected $options
        = [
            'module', 'table', 'type',
        ];

    /**
     * Name validate
     *
     * @param  mixed $value
     * @param  array $context
     *
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $this->setValue($value);
        if (null !== $value) {
            switch ($this->options['type']) {
                case 'product';
                    $where = ['product' => $value];
                    break;

                case 'category';
                    $where = ['category' => $value];
                    break;
            }
            if (!empty($context['id'])) {
                $where['id <> ?'] = $context['id'];
            }
            $rowset = Pi::model($this->options['table'], $this->options['module'])->select($where);
            if ($rowset->count()) {
                $this->error(static::TAKEN);
                return false;
            }
        }
        return true;
    }
}