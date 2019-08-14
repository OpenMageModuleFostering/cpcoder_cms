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
 * Adminhtml cms blocks grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Cpcoder_Cms_Block_Adminhtml_Cms_Block_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public $isModule;
    public $isBlockConfig;
    public $isBlockMass;
    public $isBlockEditAction;
    public $isBlockEditInline;

    public function __construct()
    {
        parent::__construct();
        $this->setId('cmsBlockGrid');
        $this->setDefaultSort('block_identifier');
        $this->setDefaultDir('ASC');
        $this->isModule = Mage::getStoreConfig('cpcoder_cms/general_config/enabled');
        $this->isBlockConfig = Mage::getStoreConfig('cpcoder_cms/block_config/enabled');
        $this->isBlockMass = Mage::getStoreConfig('cpcoder_cms/block_config/block_mass_action');
        $this->isBlockEditAction = Mage::getStoreConfig('cpcoder_cms/block_config/block_edit_action');
        $this->isBlockEditInline = Mage::getStoreConfig('cpcoder_cms/block_config/block_edit_inline');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cms/block')->getCollection();
        /* @var $collection Mage_Cms_Model_Mysql4_Block_Collection */
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();
        if($this->isModule && $this->isBlockConfig){
            $this->addColumn('block_id', array(
                'header'    => Mage::helper('cms')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'block_id',
                'column_css_class' => 'row_id'
            ));
        }

        $this->addColumn('title', array(
            'header'    => Mage::helper('cms')->__('Title'),
            'align'     => 'left',
            'index'     => 'title',
            'column_css_class' => 'title_td'
        ));

        $this->addColumn('identifier', array(
            'header'    => Mage::helper('cms')->__('Identifier'),
            'align'     => 'left',
            'index'     => 'identifier',
            'column_css_class' => 'identifier_td'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'        => Mage::helper('cms')->__('Store View'),
                'index'         => 'store_id',
                'type'          => 'store',
                'store_all'     => true,
                'store_view'    => true,
                'sortable'      => false,
                'filter_condition_callback'
                                => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('is_active', array(
            'header'    => Mage::helper('cms')->__('Status'),
            'index'     => 'is_active',
            'type'      => 'options',
            'options'   => array(
                0 => Mage::helper('cms')->__('Disabled'),
                1 => Mage::helper('cms')->__('Enabled')
            ),
        ));

        $this->addColumn('creation_time', array(
            'header'    => Mage::helper('cms')->__('Date Created'),
            'index'     => 'creation_time',
            'type'      => 'datetime',
        ));

        $this->addColumn('update_time', array(
            'header'    => Mage::helper('cms')->__('Last Modified'),
            'index'     => 'update_time',
            'type'      => 'datetime',
        ));

        if($this->isModule && $this->isBlockConfig){            
            $this->addColumn('block_actions', array(
                'header'    => Mage::helper('cms')->__('Action'),
                'width'     => 100,
                'sortable'  => false,
                'filter'    => false,
                'renderer'  => 'cpcoder_cms/adminhtml_cms_block_grid_renderer_action',
            ));
        }
        if($this->isModule && $this->isBlockConfig && $this->isBlockEditInline){
            $this->setAdditionalJavaScript($this->getScripts());
        }
        return parent::_prepareColumns();
    }

    /* mass action for static blocks */
    protected function _prepareMassaction()
    {
        if($this->isModule && $this->isBlockConfig && $this->isBlockMass){
            $this->setMassactionIdField('block_id');
        }

        $this->getMassactionBlock()->setFormFieldName('block_ids');
        //$this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('delete', array(
        'label'=> Mage::helper('cpcoder_cms')->__('Delete'),
        'url'  => $this->getUrl('cpcoder_cms/adminhtml_cms_block/massDelete', array('' => '')),
        'confirm' => Mage::helper('cpcoder_cms')->__('Are you sure?')
        ));

        // $statuses = Mage::getSingleton('cms/block');
        $statuses = Mage::getSingleton('cms/page')->getAvailableStatuses();
        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('cpcoder_cms')->__('Change status'),
             'url'  => $this->getUrl('cpcoder_cms/adminhtml_cms_block/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('cpcoder_cms')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));

        return $this;
    }

    protected function _afterLoadCollection()
    {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }

    /**
     * Row click url
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('block_id' => $row->getId()));
    }

    /**
     * @return string
     */
    public function getScripts()
    {

        $blockTitle = $this->getUrl('cpcoder_cms/adminhtml_cms_block/saveBlockTitle');
        $blockIdentifier = $this->getUrl('cpcoder_cms/adminhtml_cms_block/saveBlockIdentifier');
        $js
            = '
        function getBlockTitle(e)
        {
            return "' . $blockTitle . 'block_id/"+getId(e);
        }
        function getBlockIdentifier(e)
        {
            return "' . $blockIdentifier . 'block_id/"+getId(e);
        }
        function getId(e)
        {
            id = e.up("tr").down("td.row_id").innerHTML;
            return id.trim();
        }
        ';
        $js
            .= <<<EOF
        document.observe('dom:loaded', function() {
            $$('.title_td').each(function(el){
                if(el.down('span')){return ;}
                idx = getId(el);
                el.update('<span id='+idx+'>'+el.innerHTML.trim()+'</span>');
                new Ajax.InPlaceEditor(el.down('span'), getBlockTitle(el),{formId:idx,okText: 'Save',cancelText: 'Cancel'} );
            });
            $$('.identifier_td').each(function(el){
                if(el.down('span')){return ;}
                idx = getId(el);
                el.update('<span id='+idx+'>'+el.innerHTML.trim()+'</span>');
                new Ajax.InPlaceEditor(el.down('span'), getBlockIdentifier(el),{formId:idx,okText: 'Save',cancelText: 'Cancel'} );
            });

EOF;
        $js .='});';
        return $js;
    }

}
