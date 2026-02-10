<?php

class Requisicao {
	public $Recurso;
	public $Parametros;
	
	public function __construct($ipRecurso){
		$this->Recurso = $ipRecurso;
	}
}

