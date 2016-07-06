<?php

 class FuncoesAuxiliares {
    public static function formata_valor_monetario($valor) {
        return 'R$ '. number_format($valor, '2', ',','.');
    }
}
