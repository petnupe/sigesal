<?php

class LancamentoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $formgrid;
    private $loaded;
    private $panelTotal;
    
    public function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_search_Lancamento');
        $this->form->class = 'tform'; // change CSS class
        $this->form = new BootstrapFormWrapper($this->form);
        $this->form->style = 'display: table;width:100%'; // change style
        $this->form->setFormTitle('Lancamento');
        
        $this->panelTotal = new TVBox;
        
        // create the form fields
        $data_lancamento = new TDate('data_lancamento');
        $data_final = new TDate('data_final');

        // add the fields
        $this->form->addQuickField('Data inicial', $data_lancamento,  200 );
        $this->form->addQuickField('Data final', $data_final,  200 );
        
        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue('Lancamento_filter_data') );
        
        // add the search form actions
        $this->form->addQuickAction(_t('Find'), new TAction(array($this, 'onSearch')), 'fa:search');
        
        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        // creates the datagrid columns
        $column_data_lancamento = new TDataGridColumn('data_lancamento', 'Dt. Lct.', 'left');
        $column_valor = new TDataGridColumn('valor', 'Valor', 'right');
        $column_descricao = new TDataGridColumn('descricao', 'Descrição', 'left');
        $column_id = new TDataGridColumn('id', 'Cód lançamento', 'rigth');
        // add the columns to the DataGrid
        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_data_lancamento);
        $this->datagrid->addColumn($column_valor);
        $this->datagrid->addColumn($column_descricao);

        // creates the datagrid column actions
        $order_data_lancamento = new TAction(array($this, 'onReload'));
        $order_data_lancamento->setParameter('order', 'data_lancamento');
        $column_data_lancamento->setAction($order_data_lancamento);
        
        $order_valor = new TAction(array($this, 'onReload'));
        $order_valor->setParameter('order', 'valor');
        $column_valor->setAction($order_valor);

        // define the transformer method over image
        $column_data_lancamento->setTransformer( function($value, $object, $row) {
            $date = new DateTime($value);
            return $date->format('d/m/y');
        });

        // define the transformer method over image
            $column_valor->setTransformer( function($value, $object, $row) {
            $cor = ($value > 0) ? 'green' : 'red';
            return '<span style="color:'.$cor.'"><nobr>R$ ' . number_format($value, 2, ',', '.'). '</nobr></span>';
        });
        // create the datagrid model
        $this->datagrid->createModel();

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        $container->add(TPanelGroup::pack('Lançamentos por período', $this->form));
        $container->add($this->datagrid);
        $container->add($this->panelTotal);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    
    public function onSearch()
    {
        // get the search form data
        $data = $this->form->getData();
        
        // clear session filters
        TSession::setValue('LancamentoList_filter_data_lancamento',   NULL);
        TSession::setValue('LancamentoList_filter_data_final',   NULL);

        if (isset($data->data_lancamento) AND ($data->data_lancamento)) {
            $filter = new TFilter('data_lancamento', '>=', "$data->data_lancamento"); // create the filter
            TSession::setValue('LancamentoList_filter_data_lancamento',   $filter); // stores the filter in the session
        }

        if (isset($data->data_final) AND ($data->data_final)) {
            $filter = new TFilter('data_lancamento', '<=', "$data->data_final"); // create the filter
            TSession::setValue('LancamentoList_filter_data_final',   $filter); // stores the filter in the session
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
     
    public function onClear() {
        TSession::setValue('Lancamento_filter_data', null);
        $this->onSearch();        
    }
    
    public function onReload($param = NULL) {
        try
        {
            // open a transaction with database 'db'
            TTransaction::open('app');
            
            // creates a repository for ]
            $repository = new TRepository('Lancamento');
            $limit = 100;
            // creates a criteria
            $criteria = new TCriteria;
            
            // default order
            if (empty($param['order']))
            {
                $param['order'] = 'id';
                $param['direction'] = 'desc';
            }
            
            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $limit);
            

            if (TSession::getValue('LancamentoList_filter_data_lancamento')) {
                $criteria->add(TSession::getValue('LancamentoList_filter_data_lancamento')); // add the session filter
            }

            if (TSession::getValue('LancamentoList_filter_data_final')) {
                $criteria->add(TSession::getValue('LancamentoList_filter_data_final')); // add the session filter
            }

            $criteria->add(new TFilter('cliente_id', '=', TSession::getValue('userid')));
            
            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            if (is_callable($this->transformCallback))
            {
                call_user_func($this->transformCallback, $objects, $param);
            }
            
            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    // add the object inside the datagrid
                    $total += $object->valor;
                    $this->datagrid->addItem($object);
                }
            }
          
            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);
            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($limit); // limit
            
            $cor = ($total < 0 ) ? 'red' : 'green'; 
            $this->panelTotal->style = "font-weight:bold; font-size: 14px; margin-left: 10%; color:$cor;";            
            $this->panelTotal->add('O saldo da pesquisa é: ' . FuncoesAuxiliares::formata_valor_monetario($total));
            
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