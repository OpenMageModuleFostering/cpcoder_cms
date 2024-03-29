<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Cms manage blocks controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Cpcoder_Cms_Adminhtml_Cms_BlockController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return Mage_Adminhtml_Cms_BlockController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('cms/block')
            ->_addBreadcrumb(Mage::helper('cms')->__('CMS'), Mage::helper('cms')->__('CMS'))
            ->_addBreadcrumb(Mage::helper('cms')->__('Static Blocks'), Mage::helper('cms')->__('Static Blocks'))
        ;
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_title($this->__('CMS'))->_title($this->__('Static Blocks'));

        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * Create new CMS block
     */
    public function newAction()
    {
        // the same form is used to create and edit
        $this->_forward('edit');
    }

    /**
     * Edit CMS block
     */
    public function editAction()
    {
        $this->_title($this->__('CMS'))->_title($this->__('Static Blocks'));

        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('block_id');
        $model = Mage::getModel('cms/block');

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (! $model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cms')->__('This block no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($model->getId() ? $model->getTitle() : $this->__('New Block'));

        // 3. Set entered data if was error when we do save
        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (! empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        Mage::register('cms_block', $model);

        // 5. Build edit form
        $this->_initAction()
            ->_addBreadcrumb($id ? Mage::helper('cms')->__('Edit Block') : Mage::helper('cms')->__('New Block'), $id ? Mage::helper('cms')->__('Edit Block') : Mage::helper('cms')->__('New Block'))
            ->renderLayout();
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        // check if data sent
        if ($data = $this->getRequest()->getPost()) {

            $id = $this->getRequest()->getParam('block_id');
            $model = Mage::getModel('cms/block')->load($id);
            if (!$model->getId() && $id) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cms')->__('This block no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }

            // init model and set data

            $model->setData($data);

            // try to save it
            try {
                // save the data
                $model->save();
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cms')->__('The block has been saved.'));
                // clear previously saved data from session
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('block_id' => $model->getId()));
                    return;
                }
                // go to grid
                $this->_redirect('*/*/');
                return;

            } catch (Exception $e) {
                // display error message
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                // save data in session
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                // redirect to edit form
                $this->_redirect('*/*/edit', array('block_id' => $this->getRequest()->getParam('block_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('block_id');
        // check if we know what should be deleted
        if ($id = $this->getRequest()->getParam('block_id')) {
            $title = "";
            try {
                // init model and delete
                $model = Mage::getModel('cms/block');
                $model->load($id);
                $title = $model->getTitle();
                $model->delete();
                // display success message
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('cms')->__('The block has been deleted.'));
                // go to grid
                $this->_redirectUrl(Mage::helper('adminhtml')->getUrl('adminhtml/cms_block/index'));
                return;

            } catch (Exception $e) {
                // display error message
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                // go back to edit form
                $this->_redirect('admin/cms_block/edit', array('block_id' => $id));
                return;
            }
        }
        // display error message
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cms')->__('Unable to find a block to delete.'));
        // go to grid
        $this->_redirectUrl(Mage::helper('adminhtml')->getUrl('adminhtml/cms_block/index'));
    }


    /**
     * Mass Delete action
     */

    public function massDeleteAction()
    {
        $blockIds = $this->getRequest()->getParam('block_ids');
        
        if(!is_array($blockIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('cpcoder_cms')->__('Unable to find blocks to delete.'));
        } else {
            try {
                
                $blockModel = Mage::getModel('cms/block');

                foreach ($blockIds as $blockId) {
                    $blockModel->load($blockId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('cpcoder_cms')->__(
                'Total of %d record(s) were deleted.', count($blockIds)
                )
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
         
        $this->_redirectUrl(Mage::helper('adminhtml')->getUrl('adminhtml/cms_block/index'));
    }

    /**
     * Update block(s) status action
     *
     */
    public function massStatusAction()
    {
        $blockIds = (array)$this->getRequest()->getParam('block_ids');
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        $status = (int)$this->getRequest()->getParam('status');

        try {
            foreach ($blockIds as $blockId) {
                switch ($status) {
                    case 1:
                        Mage::getModel('cms/block')->load($blockId)->setData('is_active', 1)->save();
                        break;
                    case 2:
                        Mage::getModel('cms/block')->load($blockId)->setData('is_active', 0)->save();
                        break;
                }
            }            

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) have been updated.', count($blockIds))
            );
        }
        catch (Mage_Core_Model_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()
                ->addException($e, $this->__('An error occurred while updating the block(s) status.'));
        }

        $this->_redirectUrl(Mage::helper('adminhtml')->getUrl('adminhtml/cms_block/index'));
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('cms/block');
    }

    /**
     * inline ajax block title edit/save
     */
    public function saveBlockTitleAction()
    {
        $request = $this->getRequest();
        $editorId = $request->getParam('editorId');
        $value = $request->getParam('value');
        $blockId = $editorId;
        if (!$editorId) {
            echo $this->__('Unable to Save.');
            return;
        }
        if (!$value) {
            echo $this->__('Value can not be empty.');
            return;
        }

        $blockModel = Mage::getModel('cms/block')->load($blockId);
        try {
            $blockModel->setData('title', trim($value))->save();
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        echo $blockModel->getData('title');

    }

    /**
     * inline ajax block url edit/save
     */
    public function saveBlockIdentifierAction()
    {
        $request = $this->getRequest();
        $editorId = $request->getParam('editorId');
        $value = $request->getParam('value');
        $blockId = $editorId;
        if (!$editorId) {
            echo $this->__('Unable to Save.');
            return;
        }
        if (!$value) {
            echo $this->__('Value can not be empty.');
            return;
        }

        $blockModel = Mage::getModel('cms/block')->load($blockId);
        try {
            $blockModel->setData('identifier', trim($value))->save();
        } catch (Exception $e) {
            echo '<span style="color:red;">'.$e->getMessage().'</span>';
        }
        echo $blockModel->getData('identifier');

    }
}
