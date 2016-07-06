<?php
/**
 * ProdutoListAdm Listing
 * @author  <your name here>
 */
class ProdutoListAdm extends TStandardList
{
    protected $form;     // registration form
    protected $datagrid; // listing
    protected $pageNavigation;
    protected $formgrid;
    protected $deleteButton;
    protected $transformCallback;
    
    /**
     * Page constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase('app');            // defines the database
        parent::setActiveRecord('Produto');   // defines the active record
        parent::setDefaultOrder('id', 'asc');         // defines the default order
        // parent::setCriteria($criteria) // define a standard filter

        parent::addFilterField('id', '=', 'id'); // filterField, operator, formField
        parent::addFilterField('nome', 'like', 'nome'); // filterField, operator, formField
        
        // creates the form
        $this->form = new TQuickForm('form_search_Produto');
        $this->form->class = 'tform'; // change CSS class
        $this->form = new BootstrapFormWrapper($this->form);
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Produto');
        

        // create the form fields
        $id = new TEntry('id');
        $nome = new TEntry('nome');


        // add the fields
        $this->form->addQuickField('Id', $id,  200 );
        $this->form->addQuickField('Nome', $nome,  200 );

        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('Produto_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('ProdutoFormAdm', 'onEdit')), 'bs:plus-sign green');
        
        // creates a DataGrid
        $this->datagrid = new TDataGrid;
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->datagrid->class = 'table-bordered';
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        // $this->datagrid->datatable = 'true';
        // $this->datagrid->enablePopover('Popover', 'Hi <b> {name} </b>');
        

        // creates the datagrid columns
        $column_id = new TDataGridColumn('id', 'Id', 'right');
        $column_nome = new TDataGridColumn('nome', 'Nome', 'left');
        $column_valor = new TDataGridColumn('valor', 'Valor', 'left');


        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_valor);


        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        
        $order_valor = new TAction(array($this, 'onReload'));
        $order_valor->setParameter('order', 'valor');
        $column_valor->setAction($order_valor);
        


        // inline editing
        $nome_edit = new TDataGridAction(array($this, 'onInlineEdit'));
        $nome_edit->setField('id');
        $column_nome->setEditAction($nome_edit);
        
        $valor_edit = new TDataGridAction(array($this, 'onInlineEdit'));
        $valor_edit->setField('id');
        $column_valor->setEditAction($valor_edit);
        

        // define the transformer method over image
        $column_valor->setTransformer( function($value, $object, $row) {
            return 'R$ ' . number_format($value, 2, ',', '.');
        });


        
        // create EDIT action
        /*
        $action_edit = new TDataGridAction(array('ProdutoFormAdm', 'onEdit'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        */
        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setUseButton(TRUE);
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('fa:trash-o red fa-lg');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);
        
        // create the datagrid model
        $this->datagrid->createModel();
        
        // create the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        


        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add(TPanelGroup::pack('Title', $this->form));
        $container->add($this->datagrid);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    

}
