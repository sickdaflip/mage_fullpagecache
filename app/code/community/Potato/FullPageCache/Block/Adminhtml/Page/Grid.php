<?php

class Potato_FullPageCache_Block_Adminhtml_Page_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('po_fpcPageGrid');
        $this->setDefaultSort('views');
        $this->setDefaultDir('DESC');
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('po_fpc/popularity')
            ->getCollection()
            ->addCacheStatus()
        ;
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => $this->__('Store'),
                'index'     => 'store_id',
                'type'      => 'store',
                'store_view'   => true,
                'filter_index' => 'main_table.store_id',
                'display_deleted' => true,
            ));
        }
        $this->addColumn(
            'url',
            array(
                'header' => $this->__('Url'),
                'type'    => 'text',
                'index'   => 'url',
                'sortable' => true,
            )
        );
        $this->addColumn(
            'views',
            array(
                'header' => $this->__('Views'),
                'width'   => 50,
                'type'    => 'number',
                'index'   => 'views',
                'sortable' => true,
            )
        );
        $this->addColumn(
            'is_cached',
            array(
                'header' => $this->__('Is Cached'),
                'width'  => 50,
                'type'   => 'options',
                'options'  => array(
                    0 => $this->__('No'),
                    1 => $this->__('Yes')
                ),
                'index'        => 'is_cached',
                'filter_index' => new Zend_Db_Expr('IF (storage.request_url IS NOT NULL, 1, 0)'),
                'sortable'     => true,
                'frame_callback' => array($this, 'decorateStatus')
            )
        );
        $this->addColumn('updated_at', array(
            'header' => $this->__('Last Viewed At'),
            'index' => 'updated_at',
            'type' => 'datetime',
            'width' => '100px',
        ));
        return parent::_prepareColumns();
    }

    /**
     * Decorate status column values
     *
     * @param $value
     * @param $row
     * @param $column
     * @param $isExport
     *
     * @return string
     */
    public function decorateStatus($value, $row, $column, $isExport)
    {
        $result = '<span class="grid-severity-critical"><span>' . $value . '</span></span>';
        if ($row->getIsCached()) {
            $result = '<span class="grid-severity-notice"><span>' . $value . '</span></span>';
        }
        return $result;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> $this->__('Delete'),
            'url'  => $this->getUrl('*/*/deleteall'),
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        return '';
    }
}