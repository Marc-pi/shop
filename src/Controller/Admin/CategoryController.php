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
use Pi\Filter;
use Pi\Mvc\Controller\ActionController;
use Pi\Paginator\Paginator;
use Pi\File\Transfer\Upload;
use Module\Shop\Form\CategoryForm;
use Module\Shop\Form\CategoryFilter;
use Zend\Db\Sql\Predicate\Expression;

class CategoryController extends ActionController
{
    /**
     * Image Prefix
     */
    protected $ImageCategoryPrefix = 'category-';

    /**
     * index Action
     */
    public function indexAction()
    {
        $type = $this->params('type', 'category');
        // Set count
        $where = array('type' => $type);
        $count = array('count' => new Expression('count(*)'));
        $select = $this->getModel('category')->select()->columns($count)->where($where);
        $count = $this->getModel('category')->selectWith($select)->current()->count;
        // Set view
        $this->view()->setTemplate('category-index');
        $this->view()->assign('count', $count);
        $this->view()->assign('type', $type);
    }

    /**
     * update Action
     */
    public function updateAction()
    {
        // Get id
        $id = $this->params('id');
        $module = $this->params('module');
        $type = $this->params('type', 'category');
        $option = array(
            'isNew' => true,
            'type' => $type,
        );
        // Find category
        if ($id) {
            $category = $this->getModel('category')->find($id)->toArray();
            if ($category['image']) {
                $category['thumbUrl'] = sprintf('upload/%s/thumb/%s/%s', $this->config('image_path'), $category['path'], $category['image']);
                $option['thumbUrl'] = Pi::url($category['thumbUrl']);
                $option['removeUrl'] = $this->url('', array('action' => 'remove', 'id' => $category['id']));
            }
            $option['isNew'] = false;
        }
        // Set form
        $form = new CategoryForm('category', $option);
        $form->setAttribute('enctype', 'multipart/form-data');
        if ($this->request->isPost()) {
            $data = $this->request->getPost();
            $file = $this->request->getFiles();
            // Set slug
            $slug = ($data['slug']) ? $data['slug'] : $data['title'];
            $filter = new Filter\Slug;
            $data['slug'] = $filter($slug);
            // Form filter
            $form->setInputFilter(new CategoryFilter($option));
            $form->setData($data);
            if ($form->isValid()) {
                $values = $form->getData();
                // upload image
                if (!empty($file['image']['name'])) {
                    // Set upload path
                    $values['path'] = sprintf('%s/%s', date('Y'), date('m'));
                    $originalPath = Pi::path(sprintf('upload/%s/original/%s', $this->config('image_path'), $values['path']));
                    // Image name
                    $imageName = Pi::api('image', 'shop')->rename($file['image']['name'], $this->ImageCategoryPrefix, $values['path']);
                    // Upload
                    $uploader = new Upload;
                    $uploader->setDestination($originalPath);
                    $uploader->setRename($imageName);
                    $uploader->setExtension($this->config('image_extension'));
                    $uploader->setSize($this->config('image_size'));
                    if ($uploader->isValid()) {
                        $uploader->receive();
                        // Get image name
                        $values['image'] = $uploader->getUploaded('image');
                        // process image
                        Pi::api('image', 'shop')->process($values['image'], $values['path']);
                    } else {
                        $this->jump(array('action' => 'update'), __('Problem in upload image. please try again'));
                    }
                } elseif (!isset($values['image'])) {
                    $values['image'] = '';
                }
                // Set seo_title
                $title = ($values['seo_title']) ? $values['seo_title'] : $values['title'];
                $filter = new Filter\HeadTitle;
                $values['seo_title'] = $filter($title);
                // Set seo_keywords
                $keywords = ($values['seo_keywords']) ? $values['seo_keywords'] : $values['title'];
                $filter = new Filter\HeadKeywords;
                $filter->setOptions(array(
                    'force_replace_space' => (bool)$this->config('force_replace_space'),
                ));
                $values['seo_keywords'] = $filter($keywords);
                // Set seo_description
                $description = ($values['seo_description']) ? $values['seo_description'] : $values['title'];
                $filter = new Filter\HeadDescription;
                $values['seo_description'] = $filter($description);
                // Set time
                if (empty($values['id'])) {
                    $values['time_create'] = time();
                }
                $values['time_update'] = time();
                // Save values
                if (!empty($values['id'])) {
                    $row = $this->getModel('category')->find($values['id']);
                } else {
                    $row = $this->getModel('category')->createRow();
                }
                $row->assign($values);
                $row->save();
                // Add / Edit sitemap
                if (Pi::service('module')->isActive('sitemap')) {
                    // Set loc
                    $loc = Pi::url($this->url('shop', array(
                        'module' => $module,
                        'controller' => 'category',
                        'slug' => $values['slug']
                    )));
                    // Update sitemap
                    Pi::api('sitemap', 'sitemap')->singleLink($loc, $row->status, $module, 'category', $row->id);
                }
                // Set sale
                if (isset($values['sale_percent']) && intval($values['sale_percent']) > 0) {
                    $sale = array();
                    $sale['percent'] = intval($values['sale_percent']);
                    $sale['time_publish'] = strtotime($values['sale_time_publish']);
                    $sale['time_expire'] = strtotime($values['sale_time_expire']);
                    $sale['type'] = 'category';
                    $sale['category'] = $row->id;
                    $sale['status'] = 1;
                    $row = $this->getModel('sale')->createRow();
                    $row->assign($sale);
                    $row->save();
                    // Clear registry
                    Pi::registry('saleInformation', 'shop')->clear();
                }
                // Clear registry
                Pi::registry('categoryList', 'shop')->clear();
                Pi::registry('categoryRoute', 'shop')->clear();
                // Add log
                $operation = (empty($values['id'])) ? 'add' : 'edit';
                Pi::api('log', 'shop')->addLog('category', $row->id, $operation);
                $message = __('Category data saved successfully.');
                $this->jump(array('action' => 'index', 'type' => $type), $message);
            }
        } else {
            if ($id) {
                $form->setData($category);
            } else {
                $category = array();
                $category['sale_time_publish'] = date("Y-m-d H:i:s", time());
                $category['sale_time_expire'] = date("Y-m-d H:i:s", strtotime("+1 month"));
                $form->setData($category);
            }
        }
        // Set view
        $this->view()->setTemplate('category-update');
        $this->view()->assign('form', $form);
        $this->view()->assign('title', __('Add a category'));
        $this->view()->assign('message', $message);
    }

    public function removeAction()
    {
        // Get id and status
        $id = $this->params('id');
        // set category
        $category = $this->getModel('category')->find($id);
        // Check
        if ($category && !empty($id)) {
            // remove file
            /* $files = array(
                Pi::path(sprintf('upload/%s/original/%s/%s', $this->config('image_path'), $category->path, $category->image)),
                Pi::path(sprintf('upload/%s/large/%s/%s', $this->config('image_path'), $category->path, $category->image)),
                Pi::path(sprintf('upload/%s/medium/%s/%s', $this->config('image_path'), $category->path, $category->image)),
                Pi::path(sprintf('upload/%s/thumb/%s/%s', $this->config('image_path'), $category->path, $category->image)),
            );
            Pi::service('file')->remove($files); */
            // clear DB
            $category->image = '';
            $category->path = '';
            // Save
            if ($category->save()) {
                $message = sprintf(__('Image of %s removed'), $category->title);
                $status = 1;
            } else {
                $message = __('Image not remove');
                $status = 0;
            }
        } else {
            $message = __('Please select category');
            $status = 0;
        }
        return array(
            'status' => $status,
            'message' => $message,
        );
    }

    public function ajaxAction()
    {
        // Get page
        $current = $this->params('current', 1);
        $rowCount = $this->params('rowCount', 25);
        $module = $this->params('module');
        $type = $this->params('type', 'category');
        $searchPhrase = $this->params('searchPhrase');
        $sort = $this->params('sort');

        // Get info
        $rows = array();
        $where = array('type' => $type);
        if (!empty($searchPhrase)) {
            $where['title LIKE ?'] = '%' . $searchPhrase . '%';
        }
        $columns = array('id', 'title', 'slug', 'status', 'display_order', 'type');


        if (isset($sort['id'])) {
            if ($sort['id'] == 'asc') {
                $order = array('id ASC');
            } else {
                $order = array('id DESC');
            }
        } elseif (isset($sort['title'])) {
            if ($sort['title'] == 'asc') {
                $order = array('title ASC', 'id ASC');
            } else {
                $order = array('title DESC', 'id DESC');
            }
        } elseif (isset($sort['display_order'])) {
            if ($sort['display_order'] == 'asc') {
                $order = array('display_order ASC', 'id ASC');
            } else {
                $order = array('display_order DESC', 'id DESC');
            }
        } else {
            $order = array('id DESC');
        }

        $offset = (int)($current - 1) * $rowCount;
        $limit = intval($rowCount);
        $select = $this->getModel('category')->select()->columns($columns)->where($where)->order($order)->offset($offset)->limit($limit);
        $rowset = $this->getModel('category')->selectWith($select);
        // Make list
        foreach ($rowset as $row) {
            $rows[] = array(
                'id' => $row->id,
                'title' => $row->title,
                'display_order' => $row->display_order,
                'status' => $row->status,
                'view_url' => Pi::url($this->url('shop', array(
                    'module' => $module,
                    'controller' => 'category',
                    'slug' => $row->slug,
                ))),
                'edit_url' => Pi::url($this->url('admin', array(
                    'module' => $module,
                    'controller' => 'category',
                    'action' => 'update',
                    'type' => $row->type,
                    'id' => $row->id,
                ))),
            );
        }
        // Set count
        $count = array('count' => new Expression('count(*)'));
        $select = $this->getModel('category')->select()->columns($count)->where($where);
        $count = $this->getModel('category')->selectWith($select)->current()->count;
        // Set result
        $result = array(
            'current' => $current,
            'rowCount' =>  $rowCount,
            'total' => intval($count),
            'rows' => $rows,
        );
        return $result;
    }

    public function syncAction()
    {
        // Get info
        $start = $this->params('start', 0);
        $count = $this->params('count');
        $complete = $this->params('complete', 0);
        $confirm = $this->params('confirm', 0);

        // Check confirm
        if ($confirm == 1) {
            // Get category list
            $categoryList = Pi::registry('categoryList', 'shop')->read();
            // Get products and send
            $where = array(
                'id > ?' => $start,
            );
            $order = array('id ASC');
            $select = $this->getModel('product')->select()->where($where)->order($order)->limit(50);
            $rowset = $this->getModel('product')->selectWith($select);

            // Make list
            foreach ($rowset as $row) {
                $product = Pi::api('product', 'shop')->canonizeProduct($row, $categoryList);

                // Make category list as json
                $category = $product['category'];
                $category[] = $product['category_main'];
                $category = json_encode(array_unique($category));

                // Update link table
                Pi::api('category', 'shop')->setLink(
                    $product['id'],
                    $category,
                    $product['time_create'],
                    $product['time_update'],
                    $product['price'],
                    $product['stock'],
                    $product['status'],
                    $product['recommended']
                );

                // Update product
                $this->getModel('product')->update(
                    array('category' => $category),
                    array('id' => (int)$product['id'])
                );

                // Set extra
                $lastId = $product['id'];
                $complete++;
            }
            // Get count
            if (!$count) {
                $columns = array('count' => new Expression('count(*)'));
                $select = $this->getModel('product')->select()->columns($columns);
                $count = $this->getModel('product')->selectWith($select)->current()->count;
            }
            // Set complete
            $percent = (100 * $complete) / $count;
            // Set next url
            if ($complete >= $count) {
                $nextUrl = '';
            } else {
                $nextUrl = Pi::url($this->url('', array(
                    'action' => 'sync',
                    'start' => $lastId,
                    'count' => $count,
                    'complete' => $complete,
                    'confirm' => $confirm,
                )));
            }

            $info = array(
                'start' => $lastId,
                'count' => $count,
                'complete' => $complete,
                'percent' => $percent,
                'nextUrl' => $nextUrl,
            );

            $percent = ($percent > 99 && $percent < 100) ? (intval($percent) + 1) : intval($percent);
        } else {
            $info = array();
            $percent = 0;
            $nextUrl = Pi::url($this->url('', array(
                'action' => 'sync',
                'confirm' => 1,
            )));
        }
        // Set view
        $this->view()->setTemplate('category-sync');
        $this->view()->assign('nextUrl', $nextUrl);
        $this->view()->assign('percent', $percent);
        $this->view()->assign('info', $info);
        $this->view()->assign('confirm', $confirm);
    }
}