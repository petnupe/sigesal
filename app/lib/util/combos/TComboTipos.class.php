<?php

class TComboTipos {
    public static $tipos = ['-1' => 'Compra', '1' => 'Pagamento'];

    public static function getTComboTipos($name = null, $selected = null) {
        $name = !$name ? 'tipo' : $name;
        $combo = new TCombo($name);
        $combo->addItems(TComboTipos::$tipos);
        $combo->setValue($selected);
        return $combo;
    }
} 