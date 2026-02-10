<?php

class Conexao {
	
	private $Host;
	
	private $User;
	
	private $Password;
	
	private $Banco;
	
	private $Con;
	
	public function __construct($ipHost='', $ipUser='', $ipPass='', $ipBanco = '') {
		if ($ipHost == '') {
			$this->Host = 'db';
		} else {
			$this->Host = $ipHost;
		}
		if ($ipUser == '') {
			$this->User = getenv('MYSQL_USER_PROGADO') ?: 'progado';
		} else {
			$this->User = $ipUser;
		}
		
		if ($ipPass == '') {
			$this->Password = getenv('MYSQL_PASSWORD_PROGADO');
		} else {
			$this->Password = $ipPass;
		}
		
		if ($ipBanco == '') {
			$this->Banco = 'progado';
		} else {
			$this->Banco = $ipBanco;
		}
		
	}
	

	public function conectar() {
		$this->Con = new mysqli ( $this->Host, $this->User, $this->Password, $this->Banco );
		// Check connection
		if ($this->Con->connect_error) {
			die ( "Connection failed: " . $this->Con->connect_error );
		} else {
			return true;
		}
		return false;
	}
	
	public function executarQuery($ipSQL) {
		$vaResult = $this->Con->query ( $ipSQL );
		
		if ((isset ( $vaResult )) && ($vaResult->num_rows > 0)) {
			return $vaResult;
		} else {
			return null;
		}
	}
	
	public function executarInsertOrUpdate($ipSql) {
		return $this->Con->query ( $ipSql );
	}
	
	public function desconectar() {
		$this->Con->close ();
	}
}

