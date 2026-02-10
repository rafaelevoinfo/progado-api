<?php
require_once 'WebService.php';
require_once 'Atualizacao.php';
require_once 'Licenca.php';

$vaDados = file_get_contents ( 'php://input' );
$vaJsonRequisicao = json_decode ($vaDados, true );

if (! isset ( $vaJsonRequisicao ["RECURSO"] )) {
	WebService::fpuGerarJsonResposta ( WebService::coErro, 'Requisição inválida', '' );
	exit ();
}
try {
	switch ($vaJsonRequisicao ["RECURSO"]) {
		case 'ATUALIZACAO' :
			if (isset ( $vaJsonRequisicao ["PARAMETROS"] [0] )) {
				$vaVersaoAtualizada = '';
				$vaSerial = '';
				
				foreach ( $vaJsonRequisicao ["PARAMETROS"] as $vaParametros ) {
					foreach ( $vaParametros as $vaOpcao => $vaValor ) {
						if ($vaOpcao == 'VERSAO') {
							$vaAtualizacao = new Atualizacao ();
							$vaJsonPacote = $vaAtualizacao->fpuBuscarVersaoNova ( $vaValor );
						
							if ($vaJsonPacote != '') {
								WebService::fpuGerarJsonResposta ( WebService::coSucesso, 'Nova versão encontrada.', $vaJsonPacote );
							} else {
								WebService::fpuGerarJsonResposta ( WebService::coSucesso, 'Nenhuma nova versão encontrada.', '' );
							}
							break; // nao preciso continuar
						} else if ($vaOpcao == 'VERSAO_ATUALIZADA') {
							$vaVersaoAtualizada = $vaValor;
						} else if ($vaOpcao == 'SERIAL') {
							$vaSerial = $vaValor;
						} else {
							throw new Exception ( 'Parâmetro inválido.' );
						}
					}
				}
				
				if ($vaVersaoAtualizada != '') {
					$vaAtualizacao = new Atualizacao ();
					$vaAtualizacao->ppuNotificarAtualizacao ( $vaVersaoAtualizada, $vaSerial );
					WebService::fpuGerarJsonResposta ( WebService::coSucesso, 'Noficação registrada', '' );
				}
			
			}
			break;
		case 'LICENCA' :
			$vaAcao = '';
			$vaChavePc = '';
			$vaSerial = '';
			foreach ( $vaJsonRequisicao ["PARAMETROS"] as $vaParametros ) {
				foreach ( $vaParametros as $vaOpcao => $vaValor ) {
					if ($vaOpcao == 'ACAO') {
						$vaAcao = $vaValor;
					} else if ($vaOpcao == 'CHAVE_PC') {
						$vaChavePc = $vaValor;
					} else if ($vaOpcao == 'SERIAL') {
						$vaSerial = $vaValor;
					} else {
						throw new Exception ( 'Parâmetro inválido.' );
					}
				}
			}
			
			$vaLicenca = new Licenca($vaSerial, $vaChavePc);
			switch ($vaAcao){
				case 'ATIVAR':
					if ($vaLicenca->ativar ()) {
						WebService::fpuGerarJsonResposta ( WebService::coSucesso, 'Licença Ativada', $vaLicenca->fpuGerarLicenca());
					}
					break;
				case 'DESATIVAR':
					if ($vaLicenca->desativar()) {
						WebService::fpuGerarJsonResposta ( WebService::coSucesso, 'Licença liberada', '');
					}
					break;
				case 'BUSCAR':
					if (($vaLicenca->buscarLicenca ()) && ($vaLicenca->licencaAtivada ())) {
						WebService::fpuGerarJsonResposta ( WebService::coSucesso, 'Licença encontrada', $vaLicenca->fpuGerarLicenca());
					} else {
						WebService::fpuGerarJsonResposta ( WebService::coErro, 'Não foi possível encontrar nehuma licença ativada para o serial e computador informado', '');					
					}
					break;
			}			
			break;
	}
} catch ( Exception $e ) {
	WebService::fpuGerarJsonResposta ( WebService::coErro, $e->getMessage (), null );
}
?>