<?php

class WebService {
	const coSucesso = 0;
	const coErro = 1;
	
	const coPathRelativoAtualizacoes = 'Atualizacoes/';
	
	public static function fpuGerarJsonResposta($ipStatus, $ipMsg, $ipDados) {
		header ( 'Content-Type: application/json; charset=UTF-8' );
		$vaRetorno = array (
				'status' => $ipStatus,
				'mensagem' => $ipMsg,
				'dados' => $ipDados
		);		
		//$vaJson = html_entity_decode(json_encode($vaRetorno, JSON_UNESCAPED_UNICODE),ENT_NOQUOTES,'UTF-8');	
		$vaJson = json_encode($vaRetorno,JSON_UNESCAPED_UNICODE);
		echo $vaJson;
	}
}

