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

namespace Module\Shop\Form;

use Pi;
use Zend\InputFilter\InputFilter;

class ProductFilter extends InputFilter
{
    public function __construct($option = [])
    {
        // id
        $this->add(
            [
                'name'     => 'id',
                'required' => false,
            ]
        );
        // title
        $this->add(
            [
                'name'     => 'title',
                'required' => true,
                'filters'  => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
            ]
        );
        // subtitle
        $this->add(
            [
                'name'     => 'subtitle',
                'required' => false,
                'filters'  => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
            ]
        );
        // slug
        $this->add(
            [
                'name'       => 'slug',
                'required'   => false,
                'filters'    => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
                'validators' => [
                    new \Module\Shop\Validator\SlugDuplicate(
                        [
                            'module' => Pi::service('module')->current(),
                            'table'  => 'product',
                        ]
                    ),
                ],
            ]
        );
        // code
        $this->add(
            [
                'name'       => 'code',
                'required'   => false,
                'filters'    => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
                'validators' => [
                    new \Module\Shop\Validator\CodeDuplicate(
                        [
                            'module' => Pi::service('module')->current(),
                            'table'  => 'product',
                        ]
                    ),
                ],
            ]
        );
        // text_summary
        $this->add(
            [
                'name'     => 'text_summary',
                'required' => false,
                'filters'  => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
            ]
        );
        // text_description
        $this->add(
            [
                'name'     => 'text_description',
                'required' => false,
                'filters'  => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
            ]
        );
        // status
        $this->add(
            [
                'name'     => 'status',
                'required' => true,
            ]
        );
        // category
        $this->add(
            [
                'name'     => 'category',
                'required' => true,
            ]
        );
        // category_main
        $this->add(
            [
                'name'       => 'category_main',
                'required'   => true,
                'validators' => [
                    new \Module\Shop\Validator\Category,
                ],
            ]
        );
        // brand
        if ($option['brand_system']) {
            $this->add(
                [
                    'name'     => 'brand',
                    'required' => false,
                ]
            );
        }
        // image
        $this->add(
            [
                'name'     => 'image',
                'required' => false,
            ]
        );
        // seo_title
        $this->add(
            [
                'name'     => 'seo_title',
                'required' => false,
                'filters'  => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
            ]
        );
        // seo_keywords
        $this->add(
            [
                'name'     => 'seo_keywords',
                'required' => false,
                'filters'  => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
            ]
        );
        // seo_description
        $this->add(
            [
                'name'     => 'seo_description',
                'required' => false,
                'filters'  => [
                    [
                        'name' => 'StringTrim',
                    ],
                ],
            ]
        );
        // tag
        if (Pi::service('module')->isActive('tag')) {
            $this->add(
                [
                    'name'     => 'tag',
                    'required' => false,
                ]
            );
        }
    }
}