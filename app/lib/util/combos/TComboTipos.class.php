<?php

class TComboTipos {
    public static $tipos = ['-1' => 'Compra', '1' => 'Pagamento'];

    public static function getTComboTipos($selected = null) {
        $combo = new TCombo('tipo');
        $combo->addItems(TComboTipos::$tipos);
        //$combo->setValue($selected ? $selected : -1);
        return $combo;
    }
} 