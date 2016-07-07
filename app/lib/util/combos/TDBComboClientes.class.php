<?php

class TDBComboClientes {

    public static function getTDBComboClientesPorGrupo($grupo) {
		
		$grupo = trim($grupo) ? $grupo : '3';
		
		TTransaction::open('app');
		$criteria = new TCriteria;
		$criteria->add(new TFilter('system_group_id', '=', $grupo));
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
}
