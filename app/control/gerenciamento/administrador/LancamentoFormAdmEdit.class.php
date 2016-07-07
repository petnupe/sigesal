<?php
/**
 * LancamentoFormAdmEdit Form
 * @author  <your name here>
 */
class LancamentoFormAdmEdit extends TPage
{
    protected $form; // form
    
    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_Lancamento');
        $this->form->class = 'tform'; // change CSS class
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Lancamento');

        // create the form fields
        $id = new TEntry('id');
        $data_lancamento = new TDate('data_lancamento');
        $valor = new TEntry('valor');
        $descricao = new TEntry('descricao');
        $cliente = new TEntry('cliente');
        $cliente->setEditable(false);
        // add the fields
        $this->form->addQuickField('Cód.', $id,  50 );
        $this->form->addQuickField('Cliente', $cliente,  200);
        $this->form->addQuickField('Data', $data_lancamento,  100 , new TRequiredValidator);
        $this->form->addQuickField('Tipo', TComboTipos::getTComboTipos(),  100 , new TRequiredValidator);
        $this->form->addQuickField('Valor', $valor,  100 , new TRequiredValidator);
        $this->form->addQuickField('Descricao', $descricao,  200 );

        if (!empty($id))
        {
            $id->setEditable(FALSE);
        }
         
        // create the form actions
        $this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
        $this->form->addQuickAction(_t('New'),  new TAction(array('LancamentoFormAdm', 'montaForm')), 'bs:plus-sign green');
        $this->form->addQuickAction(_t('Back to the listing'),  new TAction(array('LancamentoListAdm', 'onSearch')), 'fa:table blue');        
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }

    /**
     * Save form data
     * @param $param Request
     */
    public function onSave( $param )
    {
        try
        {
            TTransaction::open('db'); // open a transaction
            $this->form->validate(); // validate form data
            $object = new Lancamento;  // create an empty object
            $data = $this->form->getData(); // get form data as array
            
            $valorOriginal = $data->valor;
            
            $data->valor = $data->valor * $data->tipo;
            $object->fromArray( (array) $data); // load the object with data
            $object->store(); // save the object
            
            $data->valor = $valorOriginal;
            // get the generated id
            $data->id = $object->id;
            
            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    
    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear();
    }
    
    /**
     * Load object to form data
     * @param $param Request
     */
    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open('db'); // open a transaction
                $object = new Lancamento($key); // instantiates the Active Record

                $User = new SystemUser($object->cliente_id);

                $valorOriginal = $object->valor;
                $object->valor *= ($object->valor < 0) ? -1 : 1;
                $object->cliente = $User->name;
                $object->tipo = $valorOriginal < 0 ? '-1' : '1';
                 
                $this->form->setData($object); // fill the form
                //$data = $this->form->getData();
                
                //$this->form->setData($data);
                TTransaction::close(); // close the transaction
            } else {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
}
