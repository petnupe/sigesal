<?php
/**
 * LancamentoSinteticoPorClienteReport Report
 * @author  <your name here>
 */
class LancamentoSinteticoPorClienteReport extends TPage
{
    protected $form; // form
    protected $notebook;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new TQuickForm('form_Lancamento_report');
        $this->form->class = 'tform'; // change CSS class
        
        $this->form->style = 'display: table;width:100%'; // change style
        
        // define the form title
        $this->form->setFormTitle('Lancamento Report');

        // create the form fields
        $data_lancamento = new TDate('data_lancamento');
        $data_final   = new TDate('data_final');
        $output_type  = new TRadioGroup('output_type');

        // add the fields
        $this->form->addQuickField('Data inicial', $data_lancamento,  100 );
        $this->form->addQuickField('Data final', $data_final,  100 );
        $this->form->addQuickField('Output', $output_type,  100 , new TRequiredValidator);
        
        $output_type->addItems(array('html'=>'HTML', 'pdf'=>'PDF', 'rtf'=>'RTF'));;
        $output_type->setValue('pdf');
        $output_type->setLayout('horizontal');
        
        // add the action button
        $this->form->addQuickAction(_t('Generate'), new TAction(array($this, 'onGenerate')), 'fa:cog blue');
        
        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 90%';
        // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        
        parent::add($container);
    }

    function onGenerate() {
        
        try {
            TTransaction::open('app');
            $con = TTransaction::get();
            $formdata = $this->form->getData();
            
            $q ="select sum(l.valor) as total, u.name as nome from lancamento as l inner join system_user as u on u.id = l.cliente_id where l.data_lancamento between '".$formdata->data_lancamento."' and '".$formdata->data_final."' group by u.id order by name";
            $result = $con->query($q);
            $objects = null;
            $total = 0;

            foreach ($result as $linha) {
                $object = new StdClass;    
                $object->name = $linha['nome'];
                $object->total = $linha['total'];
                $objects[] = $object;
                
                $total += $linha['total'];
            }

            $format  = $formdata->output_type;
            
            if ($objects) {
                $widths = array(350, 150);
                
                switch ($format) {
                    case 'html':
                        $tr = new TTableWriterHTML($widths);
                        break;
                    case 'pdf':
                        $tr = new TTableWriterPDF($widths);
                        break;
                    case 'rtf':
                        if (!class_exists('PHPRtfLite_Autoloader')) {
                            PHPRtfLite::registerAutoloader();
                        }
                        $tr = new TTableWriterRTF($widths);
                        break;
                }
                
                // create the document styles
                $tr->addStyle('title', 'Arial', '10', 'B',   '#ffffff', '#9898EA');
                $tr->addStyle('datap', 'Arial', '10', '',    '#000000', '#EEEEEE');
                $tr->addStyle('datai', 'Arial', '10', '',    '#000000', '#ffffff');
                $tr->addStyle('header', 'Arial', '16', '',   '#ffffff', '#494D90');
                $tr->addStyle('footer', 'Times', '10', 'I',  '#000000', '#B1B1EA');
                
                // add a header row
                $tr->addRow();
                $tr->addCell("Lancamentos (".join(' e ', [$formdata->data_lancamento, $formdata->data_final]).")", 'center', 'header', 4);
                
                // add titles row
                $tr->addRow();
                $tr->addCell('Cliente', 'left', 'title');
                $tr->addCell('Total', 'right', 'title');
                
                // controls the background filling
                $colour= FALSE;
                
                // data rows
                foreach ($objects as $object)
                {
                    $style = $colour ? 'datap' : 'datai';
                    $tr->addRow();
                    $tr->addCell($object->name, 'left', $style);
                    $tr->addCell(FuncoesAuxiliares::formata_valor_monetario($object->total), 'right', $style);
                    $colour = !$colour;
                }
                
                // footer row
                
                $tr->addRow();
                $tr->addCell('Total: ' . FuncoesAuxiliares::formata_valor_monetario($total), 'right', 'footer', 5);
                
                $tr->addRow();
                $tr->addCell(date('Y-m-d h:i:s'), 'center', 'footer', 4);
                // stores the file
                if (!file_exists("app/output/Lancamento.{$format}") OR is_writable("app/output/Lancamento.{$format}")) {
                    $tr->save("app/output/Lancamento.{$format}");
                } else {
                    throw new Exception(_t('Permission denied') . ': ' . "app/output/Lancamento.{$format}");
                }
                
                // open the report file
                parent::openFile("app/output/Lancamento.{$format}");
                
                // shows the success message
                new TMessage('info', _t('Report generated. Please, enable popups.'));
            } else {
                new TMessage('error', 'No records found');
            }
    
            // fill the form with the active record data
            $this->form->setData($formdata);
            
            // close the transaction
            TTransaction::close();
        } catch (Exception $e) {
            // shows the exception error message
            new TMessage('error', '<b>Error</b> ' . $e->getMessage());
            TTransaction::rollback();
        }
    }
}
