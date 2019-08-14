<?php

class Cpcoder_Cms_Block_Adminhtml_Cms_Page_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public $isModule;
    public $isPageConfig;
    public $isPageMass;
    public $isPageEditAction;
    public $isPageEditInline;
    

    public function __construct()
    {
        parent::__construct();
        $this->setId('cmsPageGrid');
        $this->setDefaultSort('identifier');
        $this->setDefaultDir('ASC');
        $this->isModule = Mage::getStoreConfig('cpcoder_cms/general_config/enabled');
        $this->isPageConfig = Mage::getStoreConfig('cpcoder_cms/page_config/enabled');
        $this->isPageMass = Mage::getStoreConfig('cpcoder_cms/page_config/page_mass_action');
        $this->isPageEditAction = Mage::getStoreConfig('cpcoder_cms/page_config/page_edit_action');
        $this->isPageEditInline = Mage::getStoreConfig('cpcoder_cms/page_config/page_edit_inline');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cms/page')->getCollection();
        /* @var $collection Mage_Cms_Model_Mysql4_Page_Collection */
        $collection->setFirstStoreFlag(true);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $baseUrl = $this->getUrl();

        if($this->isModule && $this->isPageConfig){
            $this->addColumn('page_id', array(
                'header'    => Mage::helper('cms')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'page_id',
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
            'header'    => Mage::helper('cms')->__('URL Key'),
            'align'     => 'left',
            'index'     => 'identifier',
            'column_css_class' => 'url_td'
        ));



        $this->addColumn('root_template', array(
            'header'    => Mage::helper('cms')->__('Layout'),
            'index'     => 'root_template',
            'type'      => 'options',
            'options'   => Mage::getSingleton('page/source_layout')->getOptions(),
        ));

        /**
         * Check is single store mode
         */
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
            'options'   => Mage::getSingleton('cms/page')->getAvailableStatuses()
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

        $this->addColumn('page_actions', array(
            'header'    => Mage::helper('cms')->__('Action'),
            'width'     => 100,
            'sortable'  => false,
            'filter'    => false,
            'renderer'  => 'cpcoder_cms/adminhtml_cms_page_grid_renderer_action',
        ));

        if($this->isModule && $this->isPageConfig && $this->isPageEditInline){
            $this->setAdditionalJavaScript($this->getScripts());
        }
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        if($this->isModule && $this->isPageConfig && $this->isPageMass){
            $this->setMassactionIdField('page_id');
        }
        $this->getMassactionBlock()->setFormFieldName('page_ids');
        //$this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('delete', array(
        'label'=> Mage::helper('cpcoder_cms')->__('Delete'),
        'url'  => $this->getUrl('cpcoder_cms/adminhtml_cms_page/massDelete', array('' => '')),
        'confirm' => Mage::helper('cpcoder_cms')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('cms/page')->getAvailableStatuses();
        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('cpcoder_cms')->__('Change status'),
             'url'  => $this->getUrl('cpcoder_cms/adminhtml_cms_page/massStatus', array('_current'=>true)),
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

        $columns = array
        (
            0 => array
                (
                    'label' => '',
                    'value' => ''
                ),

            1 => 'Empty',
            2 => '1 column',
            3 => '2 columns with left bar',
            4 => '2 columns with right bar',
            5 => '3 columns'
        );
        $this->getMassactionBlock()->addItem('column', array(
             'label'=> Mage::helper('cpcoder_cms')->__('Change column'),
             'url'  => $this->getUrl('cpcoder_cms/adminhtml_cms_page/massColumn', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'column',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('cpcoder_cms')->__('Column'),
                         'values' => $columns
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
        return $this->getUrl('*/*/edit', array('page_id' => $row->getId()));
    }

    /**
     * @return string
     */
    public function getScripts()
    {

        $pageTitle = $this->getUrl('cpcoder_cms/adminhtml_cms_page/savePageTitle');
        $pageUrl = $this->getUrl('cpcoder_cms/adminhtml_cms_page/savePageUrl');
        $js
            = '
        function getPageTitle(e)
        {
            return "' . $pageTitle . 'page_id/"+getId(e);
        }
        function getPageUrl(e)
        {
            return "' . $pageUrl . 'page_id/"+getId(e);
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
                new Ajax.InPlaceEditor(el.down('span'), getPageTitle(el),{formId:idx,okText: 'Save',cancelText: 'Cancel'} );
            });
            $$('.url_td').each(function(el){
                if(el.down('span')){return ;}
                idx = getId(el);
                el.update('<span id='+idx+'>'+el.innerHTML.trim()+'</span>');
                new Ajax.InPlaceEditor(el.down('span'), getPageUrl(el),{formId:idx,okText: 'Save',cancelText: 'Cancel'} );
            });

EOF;
        $js .='});';
        return $js;
    }
}
