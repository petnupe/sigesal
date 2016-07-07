<?php
/**
 * LancamentoFormAdm Form
 * @author  <your name here>
 */

ini_set('display_errors', 1);

class LancamentoFormAdm extends TPage
{
	protected $form; // form

	public function __construct( $param )
	{
		parent::__construct();
        $this->montaForm();
		$container = new TVBox;
		$container->style = 'width: 90%';
		// $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
		$container->add(TPanelGroup::pack('Lançamentos para clientes', $this->form));

		parent::add($container);
	}
	
	public function montaForm() {
	// creates the form
		$this->form = new TQuickForm('form_Lancamento');
		$this->form->class = 'tform'; // change CSS class
		
		$this->form->setFormTitle('Lancamento');
		
		// create the form fields
		
		$data_lancamento = new TDate('data_lancamento');
		$valorBase = new TEntry('valor_base');
		$valorBase->setValue($this->getValorBase());
		$quantidade = new TSpinner('quantidade');
		$quantidade->setRange(1, 50, 1);
		$quantidade->setExitAction(new TAction(array($this, 'atualizaTotal')));
		$valor = new TEntry('valor');
		$valor->setValue($valorBase->getValue());
		$descricao = new TEntry('descricao');
		
		$tipo = TComboTipos::getTComboTipos(-1);
        
		// add the fields

		$this->form->addQuickField('Cliente', $this->getTDBComboClientes(),  300 , new TRequiredValidator);
		$this->form->addQuickField('Data lancamento', $data_lancamento,  100 , new TRequiredValidator);
		$this->form->addQuickField('Valor base', $valorBase,  100);
		$this->form->addQuickField('Quantidade', $quantidade,  50);
		$this->form->addQuickField('Tipo', $tipo,  100 , new TRequiredValidator);
		$this->form->addQuickField('Valor total', $valor,  100 , new TRequiredValidator);
		$this->form->addQuickField('Descricao', $descricao, 500);
		$valor->setEditable(false);

		// create the form actions
		$this->form->addQuickAction(_t('Save'), new TAction(array($this, 'onSave')), 'fa:floppy-o');
		$this->form->addQuickAction(_t('New'),  new TAction(array($this, 'onClear')), 'bs:plus-sign green');
	}

	/**
     * Save form data
     * @param $param Request
     */
	public static function atualizaTotal($param) {
		$obj = new stdClass();
		$obj->valor = $param['valor_base'] * $param['quantidade'];
		TForm::sendData('form_Lancamento', $obj);
	}
	private function getTDBComboClientes() {

		TTransaction::open('app');
		$criteria = new TCriteria;
		$criteria->add(new TFilter('system_group_id', '=', '3'));
		$repo = new TRepository('SystemUserGroup');
		$codigos = $repo->load($criteria);
		TTransaction::close();
		$codUsers = array();
		foreach ($codigos as $codigo) {
			$codUsers[] = $codigo->system_user_id;
		}

		$criteria2 = new TCriteria;
		$criteria2->add(new TFilter('id', 'in', $codUsers));
		return new TDBCombo('cliente_id', 'app', 'SystemUser', 'id', 'name', 'name', $criteria2);
	}

	public function getValorBase() {

		try {
			TTransaction::open('app');
			$Produto = new Produto(1);
			TTransaction::close();
			return $Produto->valor;
		}catch (SQLException $e) {
			new TMessage('error', $e->getMessage());
		}
	}

	public function onSave( $param )
	{
		try
		{
			TTransaction::open('app'); // open a transaction

			/**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/

			$this->form->validate(); // validate form data
			$object = new Lancamento;  // create an empty object
			$data = $this->form->getData(); // get form data as array
            
            // Seta o valor conforme tipo e armazena valor original
			$valorOriginal = $data->valor;
			$data->valor = ($data->valor * $data->tipo);

			$object->fromArray( (array) $data); // load the object with data
			$object->store(); // save the object

            // Atribui o valor original ao form para caso de novo lançamento.
            $data->valor = $valorOriginal;

			// get the generated id
			$data->id = $object->id;
			$data->id = null;

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
		//$this->form->clear();
		$this->montaForm();
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
				TTransaction::open('app'); // open a transaction
				$object = new Lancamento($key); // instantiates the Active Record
				$this->form->setData($object); // fill the form
				TTransaction::close(); // close the transaction
			}
			else
			{
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
