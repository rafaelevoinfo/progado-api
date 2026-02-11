<?php
require_once 'Utils.php';
require_once 'Conexao.php';

class PacoteAtualizacao implements JsonSerializable
{
    public $Versao;
    public $Url;
    public $DataRelease;
    public $ReleaseNotes;
    public $Descricao;

    public function jsonSerialize():mixed 
    {
        return $this;
    }

}

class Atualizacao
{
    private $Conexao;

    public function __construct()
    {
        $this->Conexao = new Conexao();
        if (!$this->Conexao->conectar()) {
            throw new Exception('Não foi possível se conectar ao banco de dados');
        }
    }

    public function __destruct()
    {
        $this->Conexao->desconectar();
    }

    public function ppuNotificarAtualizacao($ipVersao, $ipSerial)
    {
        $vaVersao = explode(".", $ipVersao);
        if (count($vaVersao) < 4) {
            throw new Exception('Versão inválida');
        }
        if ($ipSerial == '') {
            throw new Exception('Serial não informado');
        }
        // Pegando o codigo da atualizacao
        $vaSQL = "Select ATUALIZACAO.ID
				    from ATUALIZACAO
                  where ATUALIZACAO.MAJOR = $vaVersao[0] and
                        ATUALIZACAO.MINOR = $vaVersao[1] and
                        ATUALIZACAO.`RELEASE` = $vaVersao[2] and
                        ATUALIZACAO.BUILD = $vaVersao[3]";
        $vaAtualizacoes = $this->Conexao->executarQuery($vaSQL);
        if (isset($vaAtualizacoes)) {
            $vaAtualizacao = $vaAtualizacoes->fetch_assoc();
            $vaSQL = "Select LICENCA.ID_CLIENTE
				    		from LICENCA
                  		  where LICENCA.SERIAL = '$ipSerial'";

            $vaClientes = $this->Conexao->executarQuery($vaSQL);
            if (isset($vaClientes)) {
                $vaCliente = $vaClientes->fetch_assoc();
                $vaIdAtualizacao = $vaAtualizacao['ID'];
                $vaIdCliente = $vaCliente['ID_CLIENTE'];

                $vaSQL = "Insert into ATUALIZACAO_CLIENTE (ATUALIZACAO_CLIENTE.ID_CLIENTE,
														   ATUALIZACAO_CLIENTE.ID_ATUALIZACAO,
                                                           ATUALIZACAO_CLIENTE.DATA)
						   VALUES ($vaIdCliente,$vaIdAtualizacao,CURRENT_TIMESTAMP)";
                if (!$this->Conexao->executarInsertOrUpdate($vaSQL)) {
                    throw new Exception('Não foi possível registrar a notificação de atualizacao');
                }
            } else {
                throw new Exception('Nenhum cliente encontrado para o serial informado.');
            }
        } else {
            throw new Exception('Nenhuma atualização encontrada para a versão informada.');
        }
    }

    public function fpuBuscarVersaoNova($ipVersaoAtual)
    {
        $vaResult = '';
        $vaVersao = explode(".", $ipVersaoAtual);
        if (count($vaVersao) < 4) {
            throw new Exception('Versão inválida');
        }

        // Ordeno por ordem crescente para ficar mais facil de gerar o arquivo SQL
        $vaSQL = "SELECT ATUALIZACAO.ID,
	                     ATUALIZACAO.MAJOR,
                         ATUALIZACAO.MINOR,
                         ATUALIZACAO.`RELEASE`,
						 ATUALIZACAO.BUILD,
						 ATUALIZACAO.DATA,
                         ATUALIZACAO.RELEASE_NOTES,
						 ATUALIZACAO.DESCRICAO,
						 ATUALIZACAO.NOME_EXECUTAVEL,
						 ATUALIZACAO.`SQL`
				  FROM ATUALIZACAO
                  WHERE ($vaVersao[0] < ATUALIZACAO.MAJOR) or
                        (($vaVersao[0] = ATUALIZACAO.MAJOR) and
                         ($vaVersao[1] < ATUALIZACAO.MINOR)) or
                        (($vaVersao[0] = ATUALIZACAO.MAJOR) and
                         ($vaVersao[1] = ATUALIZACAO.MINOR) and
                         ($vaVersao[2] < ATUALIZACAO.`RELEASE`)) or
                        (($vaVersao[0] = ATUALIZACAO.MAJOR) and
                         ($vaVersao[1] = ATUALIZACAO.MINOR) and
                         ($vaVersao[2] = ATUALIZACAO.`RELEASE`) and
                         ($vaVersao[3] < ATUALIZACAO.BUILD))
                  ORDER BY ATUALIZACAO.MAJOR,
                         ATUALIZACAO.MINOR,
                         ATUALIZACAO.`RELEASE`,
						 ATUALIZACAO.BUILD";
        $vaAtualizacoes = $this->Conexao->executarQuery($vaSQL);
        if (isset($vaAtualizacoes)) {
            try {
                $vaResult = $this->fpvGerarArquivoAtualizacao($vaVersao, $vaAtualizacoes);
            } finally {
                $vaAtualizacoes->close();
            }

        }

        return $vaResult;

    }

    private function fpvGerarArquivoAtualizacao($ipVersaoAtual, $ipAtualizacoes)
    {
        $vaSql = '';
        $vaExecutavel = '';
        $vaNomeSistema = 'ProGado';
        if (($ipVersaoAtual[0] == 1) && ($ipVersaoAtual[1] == 0) && ($ipVersaoAtual[2] <= 1) && ($ipVersaoAtual[3] <= 1)) {
			$vaNomeSistema = 'ZooTec';
        }

        while ($vaAtualizacao = $ipAtualizacoes->fetch_assoc()) {
            if (isset($vaAtualizacao['SQL']) && ($vaAtualizacao['SQL'] != '')) {
                $vaSql = $vaSql . $vaAtualizacao['SQL'] . PHP_EOL;
            }

            $vaExecutavel = $vaAtualizacao['NOME_EXECUTAVEL'];
            $vaNovaVersao = $vaAtualizacao["MAJOR"] . $vaAtualizacao["MINOR"] . $vaAtualizacao["RELEASE"] . $vaAtualizacao["BUILD"];
        }

        $vaVersaoAtual = $ipVersaoAtual[0] . $ipVersaoAtual[1] . $ipVersaoAtual[2] . $ipVersaoAtual[3];
        $vaNomeArquivo = WebService::coPathRelativoAtualizacoes . $vaNomeSistema . $vaVersaoAtual . '_' . $vaNovaVersao . '.zip';
        // se o arquivo ja existir nao posso apaga-lo pq pode estar sendo baixado por outro cliente. Portanto, se for necessario mexer no banco
        // ou se altera o arquivo manualmente ou gera outra versao.
        if (!file_exists($vaNomeArquivo)) {
            // vamos usar o executavel da ultima versao
            $vaExecutavel = WebService::coPathRelativoAtualizacoes . $vaExecutavel;

            if (file_exists($vaExecutavel)) {
                $vaFileSQL = '';
                if ($vaSql != '') {
                    $vaFileSQL = WebService::coPathRelativoAtualizacoes . uniqid($vaNomeSistema) . '.sql';
                    file_put_contents($vaFileSQL, $vaSql, LOCK_EX);
                }

                try {
                    $vaArquivos = null;
                    if ($vaFileSQL != '') {
                        $vaArquivos = array(
                            $vaFileSQL => "$vaNomeSistema.sql",
                            $vaExecutavel => "$vaNomeSistema.zip",
                        );
                    } else {
                        $vaArquivos = array(
                            $vaExecutavel => "$vaNomeSistema.zip",
                        );
                    }

                    if (Utils::create_zip($vaArquivos, $vaNomeArquivo, true)) {
                        $vaPacote = $this->fpvGerarPacoteAtualizacao($vaNomeArquivo, $ipAtualizacoes);
                    } else {
                        throw new Exception('Não foi possível gerar o arquivo de atualização.');
                    }
                } finally {
                    if (file_exists($vaFileSQL)) {
                        unlink($vaFileSQL);
                    }
                }
            } else {
                throw new Exception('Executável da nova versão não encontrado.');
            }
        } else { // Arquivo já existe, basta retornar sua localizacao
            $vaPacote = $this->fpvGerarPacoteAtualizacao($vaNomeArquivo, $ipAtualizacoes);
        }

        if ($vaPacote != null) {
            return json_encode($vaPacote);
        } else {
            throw new Exception('Não foi possível criar o pacote de atualização.');
        }
    }

    private function fpvGerarPacoteAtualizacao($ipNomeArquivo, $ipAtualizacoes)
    {        
        // Posiciona no ultimo registro pois esta ordenado de forma crescente
        $ipAtualizacoes->data_seek($ipAtualizacoes->num_rows - 1);
        while ($vaAtualizacao = $ipAtualizacoes->fetch_assoc()) {            
            $vaPacote = new PacoteAtualizacao();
            $vaPacote->Versao = $vaAtualizacao["MAJOR"] . '.' . $vaAtualizacao["MINOR"] . '.' . $vaAtualizacao["RELEASE"] . '.' . $vaAtualizacao["BUILD"];
            $vaPacote->Url = 'http://pro-gado.com/API/' . $ipNomeArquivo;
            $vaPacote->Descricao = $vaAtualizacao["DESCRICAO"];
            $vaPacote->DataRelease = $vaAtualizacao["DATA"];

            return $vaPacote;
        }
    }
}
