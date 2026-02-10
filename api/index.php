<?php
require_once 'Licenca.php';

if (! isset ( $_GET ["ACAO"] )) {
	echo "Resultado=Ação não foi informada";
	exit ();
}

if (! isset ( $_GET ["SERIAL"] )) {
	echo "Resultado=Serial não foi informado";
	exit ();
}

if (! isset ( $_GET ["CHAVE_PC"] )) {
	echo "Resultado=Chave do computador não foi informada.";
	exit ();
}

try {
	$vaLicenca = new Licenca ( $_GET ["SERIAL"], $_GET ["CHAVE_PC"] );
	switch ($_GET ["ACAO"]) {
		case "ATIVAR" :
			if ($vaLicenca->ativar ()) {
				$vaLicenca->gerarRetornoInformacoesLicenca ();
			}
			break;
		case "DESATIVAR" :
			if ($vaLicenca->desativar()) {
				echo 'Resultado=OK';
			}
			break;
		case "BUSCAR" :
			if (($vaLicenca->buscarLicenca ()) && ($vaLicenca->licencaAtivada ())) {
				$vaLicenca->gerarRetornoInformacoesLicenca ();
			} else {
				echo 'Resultado=Não foi possível encontrar nehuma licença ativada para o serial e computador informado';
			}
			break;
	}
} catch ( Exception $e ) {
	echo $e->getMessage();
}

?>