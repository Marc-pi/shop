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
namespace Module\Shop\Controller\Feed;

use Pi;
use Pi\Mvc\Controller\FeedController;
use Pi\Feed\Model as DataModel;

class IndexController extends FeedController
{
    public function indexAction()
    {
        $feed = $this->getDataModel(array(
            'title' => __('Shop feed'),
            'description' => __('Recent products.'),
            'date_created' => time(),
        ));
        $order = array('time_create DESC', 'id DESC');
        $where = array('status' => 1);
        $select = $this->getModel('product')->select()->where($where)->order($order)->limit(10);
        $rowset = $this->getModel('product')->selectWith($select);
        foreach ($rowset as $row) {
            $entry = array();
            $entry['title'] = $row->title;
            $description = (empty($row->text_summary)) ? $row->text_description : $row->text_summary;
            $entry['description'] = strtolower(trim($description));
            $entry['date_modified'] = (int)$row->time_create;
            $entry['link'] = Pi::url(Pi::service('url')->assemble('shop', array(
                'module' => $this->getModule(),
                'controller' => 'product',
                'slug' => $row->slug,
            )));
            $feed->entry = $entry;
        }
        return $feed;
    }
}