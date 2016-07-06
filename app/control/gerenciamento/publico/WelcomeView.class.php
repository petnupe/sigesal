<?php
/**
 * WelcomeView
 *
 * @version    1.0
 * @package    samples
 * @subpackage tutor
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2012 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class WelcomeView extends TPage
{
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();
        
        TPage::include_css('app/resources/styles.css');
        $html2 = new THtmlRenderer('app/resources/bemvindo.html');

        // replace the main section variables
        $html2->enableSection('main', array());
        $panel2 = new TPanelGroup('Bem-vindo(a)!');
        $panel2->add($html2);
        $t = new THBox;
        $saldoAtual = $this->getValorTotal();
        $cor = ($saldoAtual < 0) ? 'red' : 'green';
        $t->style="font-size: 22px; font-weight:bold;";
        $t->add("<br />Seu saldo atual é: <span style=\"color:$cor;\">" . FuncoesAuxiliares::formata_valor_monetario($saldoAtual). "</span>");
        $panel2->add($t);
        $panel2->addFooter('<a href="./index.php?class=LancamentoList#method=onClear">Clique aqui para detalhamento de seu saldo!</a>');

        // add the template to the page
        parent::add( $panel2 );
    }

    private function getValorTotal() {
        try {
        
        TTransaction::open('app');
            $Cliente = new SystemUser(TSession::getValue('userid'));
            $repo = new TRepository('Lancamento');
            $criteria = new TCriteria();
            $criteria->add(new TFilter('cliente_id', '=', $Cliente->id));
            
            $lancamentos = $repo->load($criteria);
            $total = 0;
            
            foreach ($lancamentos as $Lancamento) {
                $total += $Lancamento->valor;
            }
            
        TTransaction::close();
        
        } catch(Exception $e) {
            new TMessage('error', $e->getMessage());
        }
        return $total;
    }
}
