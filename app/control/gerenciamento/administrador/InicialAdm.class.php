<?php

class InicialAdm extends TPage
{
    public $dados = null;
    public $dias = null;
    
    function __construct() {
        parent::__construct();
       
        $this->getValoresDia();
        $panelPrincipal = new TPanelGroup('Bem-vindo!');
        $panelEC = new TPanel('esq');
        $panelEC->add($this->getChartConsumo());
        $TH = new THBox();
        $TH->add($panelEC);
        $panelPrincipal->add($TH);
        
        $link = '<a href="./index.php?class=LancamentoListAdm#method=onSearch&data_lancamento='.date('Y-m-01').'&data_final='.date('Y-m-d').'">Clique aqui para detalhamento !</a>';
        $panelPrincipal->addFooter($link);
        parent::add( $panelPrincipal );
    }
    
    function getChartConsumo() {
        $chart = new TBarChart(new TPChartDesigner);
        $chart->setTitle('Consumo no mês ' . date('M/Y'), null, null);
        $chart->setSize(700, 350);
        $chart->setXLabels($this->dias);
        $chart->setYLabel('Valor');
        $fileName = 'app/output/barchart.png';
        $chart->setOutputPath($fileName);
        $chart->addData('Compras', $this->dados['compra']);
        $chart->addData('Vendas', $this->dados['venda']);
        $chart->generate();
        return new TImage($fileName);
    }
    
    private function getValoresDia() {
    
        TTransaction::open('app');
        $con = TTransaction::get();
        $this->dados = array('compra', 'venda');
        $q = "select distinct(data_lancamento) as data, substring(cast(data_lancamento as text), 9, 2) as mes_dia, sum(valor) as total from lancamento where valor > 0 and cast(data_lancamento as text) like '".date('Y-m')."%' group by data_lancamento order by data_lancamento asc";
        $result = $con->query($q);
       
        $count = 1;
       
        foreach($result as $linha) {
            $this->dados['compra'][] = $linha['total'];
            $dias[$linha['mes_dia']] = $count++;       
        }
        
        $q = "select distinct(data_lancamento) as data, substring(cast(data_lancamento as text), 9, 2) as mes_dia, sum(valor) * (-1) as total from lancamento where valor < 0 and cast(data_lancamento as text) like '".date('Y-m')."%' group by data_lancamento order by data_lancamento asc";
        $result = $con->query($q);

        foreach($result as $linha) {
            $this->dados['venda'][] = $linha['total'];
            $dias[$linha['mes_dia']] = $count++;       
        }
        
        $this->dias = array_flip($dias);
        sort($this->dias);
        
        TTransaction::close();
    
    }
}
