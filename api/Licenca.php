<?php
require_once 'Conexao.php';

class TipoLicenca{
	const Normal = 0;
	const Demo = 1;
}

class InfoLicenca {
	public $NomeCliente;
	public $Serial;
	public $DataAquisicao;
	public $DataVencimento;
	public $DataAtual;
	public $Tipo;
	public $QtdeAnimais;
}

class Licenca {	
	private $IdLicenca;
	private $IdAtivacao;
	private $QtdeLicenca;
	private $QtdeAtivacao;
	private $Serial;
	private $ChavePC;
	private $Conexao;
	private $InfoLicenca;
	
	public function __construct($serial, $chavePc) {
		$this->Serial = $serial;
		$this->ChavePC = $chavePc;
		$this->Conexao = new Conexao ();
		$this->InfoLicenca = new InfoLicenca();
		if (! $this->Conexao->conectar ()) {
			throw new Exception ( 'Não foi possível conectar ao banco de dados.' );
		}
	}
	
	public function __destruct() {
		$this->Conexao->desconectar ();
	}
	
	private function carregarInformacoesLicenca($ipRow) {
		$this->IdLicenca = $ipRow ["ID_LICENCA"];
		$this->InfoLicenca->NomeCliente = $ipRow ["NOME"];
		$vaDataAquisicao = DateTime::createFromFormat ( 'Y-m-d', $ipRow ["DATA_AQUISICAO"]);
		$this->InfoLicenca->DataAquisicao = $vaDataAquisicao->Format('Y-m-d');
		$this->InfoLicenca->Tipo = $ipRow["TIPO"];
		if ($this->InfoLicenca->Tipo == TipoLicenca::Demo){			
			$vaDataAquisicao->add(new DateInterval('P30D'));//add 30 dias
			$this->InfoLicenca->DataVencimento = $vaDataAquisicao->Format('Y-m-d');
		}else{
			$this->InfoLicenca->DataVencimento = DateTime::createFromFormat ( 'Y-m-d', $ipRow ["DATA_VENCIMENTO"])->Format('Y-m-d');
		}
		$this->InfoLicenca->Serial = $ipRow ["SERIAL"];		
		$this->InfoLicenca->QtdeAnimais = $ipRow["QTDE_ANIMAIS"];
		$this->QtdeLicenca = $ipRow ["QTDE_LICENCA"];
		$this->IdAtivacao = $ipRow ["ID_ATIVACAO"];
		$this->QtdeAtivacao = $ipRow ["QTDE_ATIVACAO"];		
	}	
	
	public function licencaAtivada(){
		return isset($this->IdAtivacao);
	}
	
	public function buscarLicenca() {
		$vaResult = false;
		
		$vaSQL = "select CLIENTE.NOME,
                             LICENCA.ID as ID_LICENCA,
                             LICENCA.SERIAL,
                             LICENCA.DATA_AQUISICAO,
                             MENSALIDADE.DATA_VENCIMENTO,
                             LICENCA.QTDE_LICENCA,
							 ATIVACAO.ID AS ID_ATIVACAO,
							 LICENCA.TIPO, 
							 LICENCA.QTDE_ANIMAIS,
                             (select count(*) from ATIVACAO
                              where ATIVACAO.ID_LICENCA = LICENCA.ID AND
                                    ATIVACAO.DATA_DESATIVACAO IS NULL) AS QTDE_ATIVACAO
                  from LICENCA
                  inner join CLIENTE on (CLIENTE.Id = LICENCA.Id_cliente)
                  left join MENSALIDADE on (MENSALIDADE.Id_licenca = LICENCA.Id)
				  left join ATIVACAO on (ATIVACAO.ID_LICENCA = LICENCA.ID and ATIVACAO.CHAVE_PC = '$this->ChavePC' and ATIVACAO.DATA_DESATIVACAO IS NULL)
                  where LICENCA.Serial = '$this->Serial'
                  Order by MENSALIDADE.Data_Vencimento desc
                  LIMIT 1";
		$vaLicenca = $this->Conexao->executarQuery ( $vaSQL );
		if (isset ( $vaLicenca )) {
			$vaRow = $vaLicenca->fetch_assoc ();
			if (isset ( $vaRow )) {							
				$this->carregarInformacoesLicenca ( $vaRow );
				
				$vaResult = true;
			}
		}
		return $vaResult;
	}
	
	public function ativar() {
		$vaRetorno = false;
		if ($this->buscarLicenca ()) {
			// Vamos ver se ja foi ativado para esse PC						
			if (!isset($this->IdAtivacao)) {
				if ($this->QtdeAtivacao < $this->QtdeLicenca) {
					$vaSQL = 'Insert into ATIVACAO (ID_LICENCA, CHAVE_PC, DATA_ATIVACAO) 
							  values ('.$this->IdLicenca.', "'.$this->ChavePC.'", current_timestamp)';
					
					if (! $this->Conexao->executarInsertOrUpdate ( $vaSQL )) {
						throw new Exception ( "Não foi possível ativar o sistema." );
					}
				} else {
					throw new Exception ( "Quantidade máxima de ativações para o serial informado já foi atingida." );
				}
			}
			$vaRetorno = true;
		} else {
			throw new Exception ( "Licença não encontrada" );
		}
		
		return $vaRetorno;
	}
	
	public function desativar() {
		$vaRetorno = false;
		if (($this->buscarLicenca()) && (isset($this->IdAtivacao))) {
			$vaSQL = "update ATIVACAO set ATIVACAO.DATA_DESATIVACAO = current_timestamp where ATIVACAO.ID = $this->IdAtivacao";
			
			if (! $this->Conexao->executarInsertOrUpdate ( $vaSQL )) {
				throw new Exception ( "Não foi possível desativar o sistema." );
			}
			
			$vaRetorno = true;
		} else {
			throw new Exception ( "Nenhuma ativação foi encontrada para ser liberada." );
		}
		
		return $vaRetorno;
	}
	
	public function fpuGerarLicenca() {	
		$this->InfoLicenca->DataAtual = date('Y-m-d');
		/*$vaInfoLicenca->NomeCliente = $this->$InfoLicenca->NomeCliente;
		$vaInfoLicenca->Serial = $this->Serial;		
		$vaInfoLicenca->DataAquisicao = $this->DataAquisicao;
		$vaInfoLicenca->DataVencimento =$this->DataVencimento;//->format ( 'd/m/Y' ).';';
		$vaInfoLicenca->DataAtual = date('d/m/Y H:i:s');
		*/
		return json_encode($this->InfoLicenca);
	}

	public function gerarRetornoInformacoesLicenca() {
		echo $this->fpuGerarLicenca();
	}
}


?>