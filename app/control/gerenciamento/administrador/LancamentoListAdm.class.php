<?php
/**
 * LancamentoListAdm Listing
 * @author  <your name here>
 */
class LancamentoListAdm extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $deleteButton;

    public $saldoPesquisa;
    
    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_search_Lancamento');
        $this->form->class = 'tform'; // change CSS class
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form = new BootstrapFormWrapper($this->form);
        $this->form->setFormTitle('Lancamento');

        // create the form fields
        $data_lancamento = new TDate('data_lancamento');
        $data_final = new TDate('data_final');
        $this->saldoPesquisa = new TEntry('saldo');
        
                // add the fields
        $this->form->addQuickField('Data inicial', $data_lancamento,  200 );
        $this->form->addQuickField('Data final', $data_final,  200 );
        $this->form->addQuickField('Cliente', TDBComboClientes::getTDBComboClientesPorGrupo(3),  200 );
        $this->form->addQuickField('Tipo', TComboTipos::getTComboTipos('list_tipo'),  200 );
        $this->form->addQuickField('Saldo da pesquisa', $this->saldoPesquisa,  100 );
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('Lancamento_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        $this->form->addQuickAction(_t('New'),  new TAction(array('LancamentoFormAdm', 'montaForm')), 'bs:plus-sign green');
       
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        // creates the datagrid columns
        $column_check = new TDataGridColumn('check', '', 'center');
        $column_id_lancamento = new TDataGridColumn('id', 'ID', 'left');
        $column_data_lancamento = new TDataGridColumn('data_lancamento', 'Data Lancamento', 'left');
        $column_valor = new TDataGridColumn('valor', 'Valor', 'right');
        $column_descricao = new TDataGridColumn('descricao', 'Descricao', 'left');
        $column_cliente_id = new TDataGridColumn('cliente_id', 'Cliente', 'right');

        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_check);
        $this->datagrid->addColumn($column_id_lancamento);
        $this->datagrid->addColumn($column_data_lancamento);
        $this->datagrid->addColumn($column_valor);
        $this->datagrid->addColumn($column_descricao);
        $this->datagrid->addColumn($column_cliente_id);

        // define the transformer method over image
        $column_data_lancamento->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/Y');
        });
        
        $column_descricao->setTransformer(function ($value, $object, $row){
                $value = trim($value) ? $value : ' ';
                return $value;
            });
        
        TTransaction::open('app');
        $column_cliente_id->setTransformer(function($value, $object, $row){
            $Cliente = new SystemUser($value);
            return $Cliente->name;
        });
        TTransaction::close();

        // define the transformer method over image
        $column_valor->setTransformer( function($value, $object, $row) {
            $cor = ($value > 0) ? 'green' :'red'; 
            return '<span style="color:'.$cor.'">R$ ' . number_format($value, 2, ',', '.').'</span>';
        });

        // creates the datagrid column actions
        $order_valor = new TAction(array($this, 'onReload'));
        $order_valor->setParameter('order', 'valor');
        $column_valor->setAction($order_valor);
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('LancamentoFormAdmEdit', 'onEdit'));
        $action_edit->setUseButton(TRUE);
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('fa:pencil-square-o blue fa-lg');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        
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
        
        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());
        $this->datagrid->disableDefaultClick();
        
        // put datagrid inside a form
        $this->formgrid = new TForm;
        $this->formgrid->add($this->datagrid);
        
        // creates the delete collection button
        $this->deleteButton = new TButton('delete_collection');
        $this->deleteButton->setAction(new TAction(array($this, 'onDeleteCollection')), AdiantiCoreTranslator::translate('Delete selected'));
        $this->deleteButton->setImage('fa:remove red');
        $this->formgrid->addField($this->deleteButton);

        $gridpack = new TVBox;
        $gridpack->style = 'width: 100%';
        $gridpack->add($this->formgrid);

        $gridpack->add($this->deleteButton)->style = 'background:whiteSmoke;border:1px solid #cccccc; padding: 3px;padding: 5px;';
        $this->transformCallback = array($this, 'onBeforeLoad');

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add(TPanelGroup::pack('Pesquisa lançamentos', $this->form));
        $container->add($gridpack);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
     
    /**
     * Register the filter in the session
     */
    public function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue('LancamentoListAdm_filter_data_lancamento',   NULL);
        TSession::setValue('LancamentoListAdm_filter_data_final',   NULL);
        TSession::setValue('LancamentoListAdm_filter_cliente_id',   NULL);
        TSession::setValue('LancamentoListAdm_filter_list_tipo',   NULL);

        if (isset($data->data_lancamento) AND ($data->data_lancamento)) {
            $filter = new TFilter('data_lancamento', '>=', "$data->data_lancamento"); // create the filter
            TSession::setValue('LancamentoListAdm_filter_data_lancamento',   $filter); // stores the filter in the session
        }

        if (isset($data->data_final) AND ($data->data_final)) {
            $filter = new TFilter('data_lancamento', '<=', "$data->data_final"); // create the filter
            TSession::setValue('LancamentoListAdm_filter_data_final',   $filter); // stores the filter in the session
        }

        if (isset($data->list_tipo) AND ($data->list_tipo)) {
            $operador = $data->list_tipo === '-1' ? '<=' : '>=';
            $filter = new TFilter('valor', $operador, "0"); // create the filter
            TSession::setValue('LancamentoListAdm_filter_list_tipo',   $filter); // stores the filter in the session
        }

        if (isset($data->cliente_id) AND ($data->cliente_id)) {
            $filter = new TFilter('cliente_id', '=', "{$data->cliente_id}"); // create the filter
            TSession::setValue('LancamentoListAdm_filter_cliente_id',   $filter); // stores the filter in the session
        }
        
        // fill the form with data again
        $this->form->setData($data);
        
        // keep the search data in the session
        TSession::setValue('Lancamento_filter_data', $data);
        
        $param=array();
        $param['offset']    =0;
        $param['first_page']=1;
        $this->onReload($param);
    }
    
    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'db'
            TTransaction::open('app');
            
            // creates a repository for Lancamento
            $repository = new TRepository('Lancamento');
            $limit = 100;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'asc';
            }
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            

            if (TSession::getValue('LancamentoListAdm_filter_data_lancamento')) {
                $criteria->add(TSession::getValue('LancamentoListAdm_filter_data_lancamento')); // add the session filter
            }

            if (TSession::getValue('LancamentoListAdm_filter_data_final')) {
                $criteria->add(TSession::getValue('LancamentoListAdm_filter_data_final')); // add the session filter
            }

            if (TSession::getValue('LancamentoListAdm_filter_cliente_id')) {
                $criteria->add(TSession::getValue('LancamentoListAdm_filter_cliente_id')); // add the session filter
            }

            if (TSession::getValue('LancamentoListAdm_filter_list_tipo')) {
                $criteria->add(TSession::getValue('LancamentoListAdm_filter_list_tipo')); // add the session filter
            }
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);
            
            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $totalPesquisa = 0;
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object) {
                    // add the object inside the datagrid
                    $totalPesquisa += $object->valor;
                    $this->datagrid->addItem($object);
                }
            }
            
            $cor = ($totalPesquisa < 0 ) ? 'red' : 'green'; 
            $this->saldoPesquisa->style = "background-color: white; text-align:right; font-weight:bold; font-size: 18px; color:$cor;";            
            $this->saldoPesquisa->setValue(FuncoesAuxiliares::formata_valor_monetario($totalPesquisa));
            $this->saldoPesquisa->setEditable(false);
            
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            // close the transaction
            TTransaction::close();
            $this->loaded = true;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * Ask before deletion
     */
    public function onDelete($param)
    {
        // define the delete action
        $action = new TAction(array($this, 'Delete'));
        $action->setParameters($param); // pass the key parameter ahead
        
        // shows a dialog to the user
        new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
    }
    
    /**
     * Delete a record
     */
    public function Delete($param)
    {
        try
        {
            $key=$param['key']; // get the parameter $key
            TTransaction::open('app'); // open a transaction with database
            $object = new Lancamento($key, FALSE); // instantiates the Active Record
            $object->delete(); // deletes the object from the database
            TTransaction::close(); // close the transaction
            $this->onReload( $param ); // reload the listing
            new TMessage('info', AdiantiCoreTranslator::translate('Record deleted')); // success message
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Ask before delete record collection
     */
    public function onDeleteCollection( $param )
    {
        $data = $this->formgrid->getData(); // get selected records from datagrid
        $this->formgrid->setData($data); // keep form filled
        
        if ($data)
        {
            $selected = array();
            
            // get the record id's
            foreach ($data as $index => $check)
            {
                if ($check == 'on')
                {
                    $selected[] = substr($index,5);
                }
            }
            
            if ($selected)
            {
                // encode record id's as json
                $param['selected'] = json_encode($selected);
                
                // define the delete action
                $action = new TAction(array($this, 'deleteCollection'));
                $action->setParameters($param); // pass the key parameter ahead
                
                // shows a dialog to the user
                new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);
            }
        }
    }
    
    /**
     * method deleteCollection()
     * Delete many records
     */
    public function deleteCollection($param)
    {
        // decode json with record id's
        $selected = json_decode($param['selected']);
        
        try
        {
            TTransaction::open('app');
            if ($selected)
            {
                // delete each record from collection
                foreach ($selected as $id)
                {
                    $object = new Lancamento;
                    $object->delete( $id );
                }
                $posAction = new TAction(array($this, 'onReload'));
                $posAction->setParameters( $param );
                new TMessage('info', AdiantiCoreTranslator::translate('Records deleted'), $posAction);
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }


    /**
     * Transform datagrid objects
     * Create the checkbutton as datagrid element
     */
    public function onBeforeLoad($objects, $param)
    {
        // update the action parameters to pass the current page to action
        // without this, the action will only work for the first page
        $deleteAction = $this->deleteButton->getAction();
        $deleteAction->setParameters($param); // important!
        
        $gridfields = array( $this->deleteButton );
        
        foreach ($objects as $object)
        {
            $object->check = new TCheckButton('check' . $object->id);
            $object->check->setIndexValue('on');
            $gridfields[] = $object->check; // important
        }
        
        $this->formgrid->setFields($gridfields);
    }

    
    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }
}
