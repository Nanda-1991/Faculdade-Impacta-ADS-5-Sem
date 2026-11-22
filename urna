<?php

$servidor = "localhost";
$banco = "EstudoWeb";

try {
    $conexao = new PDO(
        "sqlsrv:Server=$servidor;Database=$banco;TrustServerCertificate=true",
        "",
        ""
    );
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}

$eleitor = null;
$candidato = null;
$mensagem = "";
$tipoMensagem = "";
$votoConfirmado = false;

function formatarCPF($cpf)
{
    $cpf = preg_replace('/\D/', '',$cpf);

    if (strlen($cpf) === 11) {
        return substr($cpf, 0, 3) . "." .
               substr($cpf, 3, 3) . "." .
               substr($cpf, 6, 3) . "-" .
               substr($cpf, 9, 2);
    }

    return $cpf;
}

if (isset($_POST["verificar_eleitor"])) {

    $cpf = preg_replace('/\D/', '',$_POST["cpf"] ?? '');

    if (strlen($cpf) !== 11) {$mensagem = "Digite um CPF válido.";
        $tipoMensagem = "erro";
    } else {
        $cpfFormatado = formatarCPF($cpf);

        $sql = "
            SELECT Id, Nome, CPF, Votou
            FROM Eleitores
            WHERE CPF = :cpf
        ";

        $consulta =$conexao->prepare($sql);$consulta->bindValue(":cpf", $cpfFormatado);$consulta->execute();

        $eleitorEncontrado =$consulta->fetch(PDO::FETCH_ASSOC);

        if (!$eleitorEncontrado) {$mensagem = "Eleitor não cadastrado.";
            $tipoMensagem = "erro";
        } elseif ((int)$eleitorEncontrado["Votou"] === 1) {
            $mensagem = "Este eleitor já realizou seu voto.";
            $tipoMensagem = "bloqueado";
        } else {
            $eleitor =$eleitorEncontrado;
        }
    }
}

if (isset($_GET["candidato"])) {

    header("Content-Type: application/json; charset=UTF-8");

    $matricula = trim($_GET["candidato"] ?? '');

    if ($matricula === '') {
        echo json_encode(["sucesso" => false]);
        exit;
    }

    $sql = "
        SELECT Id, Nome, Matricula, Foto
        FROM Usuarios
        WHERE Matricula = :matricula
    ";

    $consulta =$conexao->prepare($sql);$consulta->bindValue(":matricula", $matricula, PDO::PARAM_STR);$consulta->execute();

    $resultado =$consulta->fetch(PDO::FETCH_ASSOC);

    if (!$resultado) {
        echo json_encode(["sucesso" => false]);
    } else {
        $fotoBase64 = "";
        if (!empty($resultado["Foto"])) {
            $fotoBase64 = "data:image/jpeg;base64," . base64_encode($resultado["Foto"]);
        }

        echo json_encode([
            "sucesso" => true,
            "id" => $resultado["Id"],
            "matricula" => $resultado["Matricula"],
            "nome" => $resultado["Nome"],
            "foto" => $fotoBase64
        ]);
    }

    exit;
}

if (isset($_POST["confirmar_voto"])) {

    $eleitorId = filter_input(INPUT_POST, "eleitor_id", FILTER_VALIDATE_INT);
    $matriculaCandidato = trim($_POST["matricula_candidato"] ?? '');

    if (!$eleitorId || $matriculaCandidato === '') {$mensagem = "Dados da votação inválidos.";
        $tipoMensagem = "erro";
    } else {
        try {
            $conexao->beginTransaction();

            $sqlEleitor = "
                SELECT Id, Nome, CPF, Votou
                FROM Eleitores WITH (UPDLOCK, ROWLOCK)
                WHERE Id = :id
            ";
            $consultaEleitor =$conexao->prepare($sqlEleitor);$consultaEleitor->bindValue(":id", $eleitorId, PDO::PARAM_INT);$consultaEleitor->execute();
            $eleitorConfirmado =$consultaEleitor->fetch(PDO::FETCH_ASSOC);

            if (!$eleitorConfirmado) {
                throw new Exception("Eleitor não encontrado.");
            }

            if ((int)$eleitorConfirmado["Votou"] === 1) {
                throw new Exception("Este eleitor já realizou seu voto.");
            }

            $sqlCandidato = "
                SELECT Id, Nome, Matricula
                FROM Usuarios
                WHERE Matricula = :matricula
            ";
            $consultaCandidato =$conexao->prepare($sqlCandidato);$consultaCandidato->bindValue(":matricula", $matriculaCandidato, PDO::PARAM_STR);$consultaCandidato->execute();
            $candidatoConfirmado =$consultaCandidato->fetch(PDO::FETCH_ASSOC);

            if (!$candidatoConfirmado) {
                throw new Exception("Candidato não encontrado.");
            }

            $sqlVoto = "
                INSERT INTO Votos
                (
                    CPF_Eleitor,
                    Nome_Eleitor,
                    Matricula_Candidato
                )
                VALUES
                (
                    :cpfEleitor,
                    :nomeEleitor,
                    :matriculaCandidato
                )
            ";

            $consultaVoto = $conexao->prepare($sqlVoto);
            $consultaVoto->bindValue(":cpfEleitor", $eleitorConfirmado["CPF"], PDO::PARAM_STR);
            $consultaVoto->bindValue(":nomeEleitor", $eleitorConfirmado["Nome"], PDO::PARAM_STR);
            $consultaVoto->bindValue(":matriculaCandidato", $candidatoConfirmado["Matricula"], PDO::PARAM_STR);
            $consultaVoto->execute();

            $sqlAtualiza = "
                UPDATE Eleitores
                SET Votou = 1
                WHERE Id = :id
            ";
            $consultaAtualiza = $conexao->prepare($sqlAtualiza);
            $consultaAtualiza->bindValue(":id", $eleitorConfirmado["Id"], PDO::PARAM_INT);
            $consultaAtualiza->execute();

            $conexao->commit();

            $votoConfirmado = true;
            $eleitor =$eleitorConfirmado;
            $candidato =$candidatoConfirmado;

        } catch (Exception $erro) {
            if ($conexao->inTransaction()) {$conexao->rollBack();
            }
            $mensagem = $erro->getMessage();$tipoMensagem = "erro";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Urna Eletrônica</title>
<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
    color: #222;
}

/* =========================
   TOPO E MENU (PADRÃO)
========================= */
.topo {
    background: #172033;
    color: white;
    padding: 25px 30px 0;
    text-align: center;
    box-shadow: 0 3px 12px rgba(0,0,0,0.12);
}

.topo h1 {
    margin: 0 0 8px;
}

.topo p {
    margin: 0;
    color: #cbd3df;
}

.topo-conteudo {
    max-width: 1100px;
    margin: 0 auto;
}

.menu {
    display: flex;
    justify-content: center;
    gap: 5px;
    flex-wrap: wrap;
}

.menu a {
    color: #dce2eb;
    text-decoration: none;
    padding: 13px 20px;
    border-radius: 8px 8px 0 0;
    font-size: 14px;
    font-weight: bold;
    transition: 0.2s;
}

.menu a:hover {
    background: #27344d;
    color: white;
}

.menu a.ativo {
    background: #f4f6f9;
    color: #172033;
}

/* =========================
   CONTEÚDO DA URNA
========================= */
.container {
    max-width: 900px;
    margin: 35px auto;
    padding: 0 20px;
}

.urna {
    width: 100%;
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.conteudo {
    padding: 30px;
}

.mensagem {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}

.mensagem.erro {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.mensagem.bloqueado {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.identificacao {
    max-width: 500px;
    margin: 0 auto 10px;
    text-align: center;
}

.identificacao h2 {
    margin-top: 0;
    color: #172033;
}

.identificacao p {
    color: #666;
}

.campo {
    margin-bottom: 18px;
    text-align: left;
}

.campo label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
    color: #374151;
}

.campo input {
    width: 100%;
    padding: 14px;
    border-radius: 8px;
    border: 1px solid #ccd2da;
    font-size: 18px;
    outline: none;
}

.campo input:focus {
    border-color: #3949ab;
    box-shadow: 0 0 0 3px rgba(57,73,171,.12);
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 14px 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: .2s;
}

.btn:hover {
    opacity: .92;
}

.btn-verificar {
    width: 100%;
    background: #3949ab;
    color: white;
}

.eleitor-validado {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 25px;
    text-align: center;
    color: #065f46;
}

.eleitor-validado strong {
    font-size: 20px;
}

.painel {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 25px;
    align-items: stretch;
}

.tela {
    background: #f9fafb;
    border: 2px solid #172033;
    min-height: 350px;
    padding: 25px;
    border-radius: 10px;
    box-shadow: inset 0 0 15px rgba(0,0,0,.05);
}

.tela h2 {
    margin-top: 0;
    font-size: 18px;
    text-transform: uppercase;
    color: #172033;
    border-bottom: 1px solid #ddd;
    padding-bottom: 12px;
}

.info-candidato {
    margin-top: 25px;
    display: none;
}

.info-candidato.visivel {
    display: block;
}

.nome-candidato {
    font-size: 27px;
    font-weight: bold;
    margin-top: 15px;
    color: #111;
}

.numero-candidato {
    font-size: 16px;
    color: #666;
}

.foto {
    width: 150px;
    height: 180px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #bbb;
    margin-top: 15px;
}

.teclado {
    background: #172033;
    border-radius: 10px;
    padding: 20px;
}

.numero-input {
    width: 100%;
    padding: 16px;
    font-size: 26px;
    text-align: center;
    margin-bottom: 15px;
    border-radius: 6px;
    border: none;
    background: #eee;
}

.teclas {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.tecla {
    border: none;
    padding: 18px;
    font-size: 22px;
    font-weight: bold;
    border-radius: 5px;
    cursor: pointer;
    background: #f5f5f5;
}

.tecla:hover {
    background: #ddd;
}

.tecla-corrigir {
    background: #facc15;
    font-size: 14px;
}

.tecla-confirmar {
    background: #22c55e;
    color: white;
    font-size: 14px;
    grid-column: span 2;
}

.confirmado {
    text-align: center;
    padding: 50px 20px;
}

.confirmado .icone {
    font-size: 70px;
    margin-bottom: 20px;
    color: #166534;
}

.confirmado h2 {
    font-size: 32px;
    color: #166534;
    margin-bottom: 10px;
}

.confirmado p {
    font-size: 18px;
    color: #555;
}

.novo-voto {
    display: inline-block;
    margin-top: 25px;
    padding: 14px 25px;
    background: #172033;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
}

@media (max-width: 700px) {
    .topo {
        padding: 18px 15px 0;
    }
    .menu {
        flex-direction: column;
        gap: 3px;
    }
    .menu a {
        text-align: center;
        border-radius: 7px;
    }
    .painel {
        grid-template-columns: 1fr;
    }
    .conteudo {
        padding: 20px;
    }
}
</style>
</head>
<body>

<div class="topo">
    <h1>Urna Eletrônica</h1>
    <p>Identifique-se com seu CPF e registre seu voto</p>

    <br><br>

    <div class="topo-conteudo">
        <nav class="menu">
            <a href="Cad_Candidato.php">Cadastro Candidato</a>
            <a href="eleitores.php">Cadastro Eleitor</a>
            <a href="Urna.php" class="ativo">Votar</a>
            <a href="Apuração.php">Apuração</a>
        </nav>
    </div>
</div>

<div class="container">
    <div class="urna">
        <div class="conteudo">

            <?php if ($votoConfirmado): ?>
                <div class="confirmado">
                    <div class="icone">✓</div>
                    <h2>VOTO CONFIRMADO!</h2>
                    <p>Obrigado, <?= htmlspecialchars($eleitor["Nome"]) ?>.</p>
                    <p>Seu voto foi registrado com sucesso.</p>
                    <a href="Urna.php" class="novo-voto">NOVA VOTAÇÃO</a>
                </div>

            <?php elseif (!$eleitor): ?>

                <?php if ($mensagem): ?>
                    <div class="mensagem <?= $tipoMensagem ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <div class="identificacao">
                    <h2>Identificação do eleitor</h2>
                    <p>Informe seu CPF para iniciar a votação.</p>

                    <form method="POST">
                        <div class="campo">
                            <label for="cpf">CPF</label>
                            <input
                                type="text"
                                id="cpf"
                                name="cpf"
                                maxlength="14"
                                placeholder="000.000.000-00"
                                required
                                autofocus
                            >
                        </div>

                        <button type="submit" name="verificar_eleitor" class="btn btn-verificar">
                            VERIFICAR ELEITOR
                        </button>
                    </form>
                </div>

            <?php else: ?>

                <?php if ($mensagem): ?>
                    <div class="mensagem <?= $tipoMensagem ?>">
                        <?= htmlspecialchars($mensagem) ?>
                    </div>
                <?php endif; ?>

                <div class="eleitor-validado">
                    Eleitor identificado:
                    <strong><?= htmlspecialchars($eleitor["Nome"]) ?></strong>
                </div>

                <div class="painel">
                    <!-- TELA -->
                    <div class="tela">
                        <h2>Escolha seu candidato</h2>

                        <div class="campo">
                            <label for="candidatoId">Número (Matrícula) do candidato</label>
                            <input
                                type="text"
                                id="candidatoId"
                                class="numero-input"
                                placeholder="Ex: 0001"
                                readonly
                            >
                        </div>

                        <div id="infoCandidato" class="info-candidato">
                            <div class="numero-candidato">
                                CANDIDATO Nº <span id="numeroExibido"></span>
                            </div>
                            <div id="nomeCandidato" class="nome-candidato"></div>
                            <img id="fotoCandidato" class="foto" src="" alt="Foto do candidato">
                        </div>
                    </div>

                    <!-- TECLADO -->
                    <div class="teclado">
                        <div id="display" class="numero-input">—</div>

                        <div class="teclas">
                            <button type="button" class="tecla" onclick="digitar('1')">1</button>
                            <button type="button" class="tecla" onclick="digitar('2')">2</button>
                            <button type="button" class="tecla" onclick="digitar('3')">3</button>
                            <button type="button" class="tecla" onclick="digitar('4')">4</button>
                            <button type="button" class="tecla" onclick="digitar('5')">5</button>
                            <button type="button" class="tecla" onclick="digitar('6')">6</button>
                            <button type="button" class="tecla" onclick="digitar('7')">7</button>
                            <button type="button" class="tecla" onclick="digitar('8')">8</button>
                            <button type="button" class="tecla" onclick="digitar('9')">9</button>
                            <button type="button" class="tecla" onclick="digitar('0')">0</button>

                            <button type="button" class="tecla tecla-corrigir" onclick="corrigir()">CORRIGIR</button>
                            <button type="button" class="tecla tecla-confirmar" onclick="confirmarVoto()">CONFIRMAR</button>
                        </div>
                    </div>
                </div>

                <!-- FORMULÁRIO INVISÍVEL PARA GRAVAR O VOTO -->
                <form method="POST" id="formVoto">
                    <input type="hidden" name="eleitor_id" value="<?= (int)$eleitor["Id"] ?>">
                    <input type="hidden" name="matricula_candidato" id="candidatoHidden">
                    <input type="hidden" name="confirmar_voto" value="1">
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
let numero = "";
let candidatoAtual = null;

function digitar(valor) {
    if (numero.length >= 6) return;
    numero += valor;
    atualizarTela();
    buscarCandidato();
}

function corrigir() {
    numero = "";
    candidatoAtual = null;
    atualizarTela();
    document.getElementById("infoCandidato").classList.remove("visivel");
}

function atualizarTela() {
    document.getElementById("display").innerText = numero || "—";
    document.getElementById("candidatoId").value = numero;
}

let timer = null;

function buscarCandidato() {
    clearTimeout(timer);
    candidatoAtual = null;
    document.getElementById("infoCandidato").classList.remove("visivel");

    if (!numero) return;

    timer = setTimeout(() => {
        fetch("Urna.php?candidato=" + encodeURIComponent(numero))
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    candidatoAtual = data;
                    document.getElementById("numeroExibido").innerText = data.matricula;
                    document.getElementById("nomeCandidato").innerText = data.nome;

                    const imgFoto = document.getElementById("fotoCandidato");
                    if (data.foto) {
                        imgFoto.src = data.foto;
                        imgFoto.style.display = "block";
                    } else {
                        imgFoto.style.display = "none";
                    }

                    document.getElementById("infoCandidato").classList.add("visivel");
                } else {
                    candidatoAtual = null;
                }
            })
            .catch(() => {
                candidatoAtual = null;
            });
    }, 300);
}

function confirmarVoto() {
    if (!candidatoAtual) {
        alert("Digite uma matrícula de candidato válida.");
        return;
    }

    const nomeEleitor = <?= json_encode($eleitor["Nome"] ?? "") ?>;

    const mensagem =
        "Eleitor: " + nomeEleitor + "\n\n" +
        "Candidato: " + candidatoAtual.nome + "\n" +
        "Número: " + candidatoAtual.matricula + "\n\n" +
        "Confirma seu voto?";

    if (confirm(mensagem)) {
        document.getElementById("candidatoHidden").value = candidatoAtual.matricula;
        document.getElementById("formVoto").submit();
    }
}

const campoCPF = document.getElementById("cpf");
if (campoCPF) {
    campoCPF.addEventListener("input", function () {
        let cpf = this.value.replace(/\D/g, "").substring(0, 11);

        if (cpf.length > 9) {
            cpf = cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, "$1.$2.$3-$4");
        } else if (cpf.length > 6) {
            cpf = cpf.replace(/(\d{3})(\d{3})(\d+)/, "$1.$2.$3");
        } else if (cpf.length > 3) {
            cpf = cpf.replace(/(\d{3})(\d+)/, "$1.$2");
        }

        this.value = cpf;
    });
}
</script>

</body>
</html>
