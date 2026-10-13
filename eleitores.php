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

    $mensagem = "";
    $erro = "";


    /*
     * CADASTRO DO ELEITOR
     */

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $nome = trim($_POST["nome"] ?? "");
        $cpf = trim($_POST["cpf"] ?? "");


        /*
         * Validação
         */

        if ($nome === "") {

            $mensagem = "Informe o nome do eleitor.";
            $tipoMensagem = "erro";

        } elseif ($cpf === "") {

            $mensagem = "Informe o CPF do eleitor.";
            $tipoMensagem = "erro";

        } else {

            /*
             * Remove caracteres do CPF
             */

            $cpfLimpo = preg_replace(
                "/[^0-9]/",
                "",
                $cpf
            );


            /*
             * Verifica se possui 11 números
             */

            if (strlen($cpfLimpo) !== 11) {

                $mensagem =
                    "Informe um CPF válido com 11 números.";

                $tipoMensagem = "erro";

            } else {

                /*
                 * Formata o CPF
                 */

                $cpfFormatado =
                    substr($cpfLimpo, 0, 3) . "." .
                    substr($cpfLimpo, 3, 3) . "." .
                    substr($cpfLimpo, 6, 3) . "-" .
                    substr($cpfLimpo, 9, 2);


                /*
                 * Verifica se CPF já está cadastrado
                 */

                $sqlVerifica = "
                    SELECT Id
                    FROM Eleitores
                    WHERE CPF = :cpf
                ";

                $consultaVerifica =
                    $conexao->prepare($sqlVerifica);

                $consultaVerifica->bindValue(
                    ":cpf",
                    $cpfFormatado,
                    PDO::PARAM_STR
                );

                $consultaVerifica->execute();


                if ($consultaVerifica->fetch()) {

                    $mensagem =
                        "Este CPF já está cadastrado.";

                    $tipoMensagem = "erro";

                } else {

                    /*
                     * Insere eleitor
                     */

                    $sql = "
                        INSERT INTO Eleitores
                        (
                            Nome,
                            CPF
                        )
                        VALUES
                        (
                            :nome,
                            :cpf
                        )
                    ";

                    $consulta =
                        $conexao->prepare($sql);

                    $consulta->bindValue(
                        ":nome",
                        $nome,
                        PDO::PARAM_STR
                    );

                    $consulta->bindValue(
                        ":cpf",
                        $cpfFormatado,
                        PDO::PARAM_STR
                    );

                    $consulta->execute();


                    $mensagem =
                        "Eleitor cadastrado com sucesso!";

                    $tipoMensagem = "sucesso";

                    /*
                     * Limpa os campos depois do cadastro
                     */

                    $nome = "";
                    $cpf = "";
                }
            }
        }
    }


    /*
     * BUSCA OS ELEITORES CADASTRADOS
     */

    $sqlLista = "
        SELECT
            Id,
            Nome,
            CPF,
            DataCadastro,
            Votou
        FROM Eleitores
        ORDER BY Id DESC
    ";

    $consultaLista =
        $conexao->query($sqlLista);

    $eleitores =
        $consultaLista->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $erro) {

    die(
        "Erro na conexão com o banco: " .
        htmlspecialchars($erro->getMessage())
    );

}

?>


<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Cadastro de Eleitores</title>


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


        .topo {
            background: #172033;
            color: white;
            padding: 25px;
            text-align: center;
        }

        .topo h1 {
            margin: 0 0 8px;
        }

        .topo p {
            margin: 0;
            color: #cbd3df;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .topo {
    background: #172033;
    color: white;
    padding: 20px 30px 0;
    box-shadow: 0 3px 12px rgba(0,0,0,0.12);
}

.topo-conteudo {
    max-width: 1100px;
    margin: 0 auto;
}

.card {
            background: white;
            border-radius: 14px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin-top: 0;
            color: #172033;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .campo {
            display: flex;
            flex-direction: column;
        }

        .campo label {
            font-weight: bold;
            margin-bottom: 7px;
        }

        .campo input {
            padding: 12px;
            border: 1px solid #ccd2da;
            border-radius: 8px;
            font-size: 15px;
        }

        .campo input:focus {
            outline: none;
            border-color: #3949ab;
        }

        .foto {
            grid-column: span 2;
        }

        .botao {
            margin-top: 20px;
            padding: 13px 25px;
            border: none;
            border-radius: 8px;
            background: #3949ab;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .botao:hover {
            background: #303f9f;
        }

        .mensagem {
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #dff5e5;
            color: #176b2c;
        }

        .erro {
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #fde2e2;
            color: #a51d1d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #172033;
            color: white;
            padding: 13px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background: #f8f9fb;
        }

        .foto-mini {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }
        /* CABEÇALHO */

        .cabecalho {

            background: white;

            padding: 25px;

            border-radius: 18px;

            text-align: center;

            margin-bottom: 25px;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,0.08);

        }


        .cabecalho h1 {

            margin: 0;

            font-size: 30px;

            color: #1f2937;

        }


        .cabecalho p {

            margin: 8px 0 0;

            color: #6b7280;

        }


        /* FORMULÁRIO */

        .card {

            background: white;

            padding: 30px;

            border-radius: 18px;

            margin-bottom: 25px;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,0.08);

        }


        .card h2 {

            margin-top: 0;

            color: #1f2937;

        }


        .formulario {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 20px;

        }


        .campo {

            display: flex;

            flex-direction: column;

        }


        .campo label {

            margin-bottom: 7px;

            font-weight: bold;

            color: #374151;

        }


        .campo input {

            padding: 13px;

            border:
                1px solid #d1d5db;

            border-radius: 9px;

            font-size: 16px;

            outline: none;

        }


        .campo input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,0.12);

        }


        .botao-cadastrar {

            grid-column: 1 / -1;

            padding: 14px;

            border: none;

            border-radius: 10px;

            background: #2563eb;

            color: white;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

        }


        .botao-cadastrar:hover {

            background: #1d4ed8;

        }


        /* MENSAGENS */

        .mensagem {

            padding: 14px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: bold;

        }


        .sucesso {

            background: #d1fae5;

            color: #065f46;

            border:
                1px solid #a7f3d0;

        }


        .erro {

            background: #fee2e2;

            color: #991b1b;

            border:
                1px solid #fecaca;

        }


        /* TABELA */

        .tabela-container {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

        }


        th {

            background: #1f2937;

            color: white;

            padding: 14px;

            text-align: left;

        }


        td {

            padding: 14px;

            border-bottom:
                1px solid #e5e7eb;

        }


        tr:hover {

            background: #f9fafb;

        }


        .status {

            display: inline-block;

            padding: 6px 10px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: bold;

        }


        .nao-votou {

            background: #dbeafe;

            color: #1d4ed8;

        }


        .ja-votou {

            background: #dcfce7;

            color: #166534;

        }


        /* BOTÕES */

        .acoes {

            display: flex;

            gap: 10px;

            margin-top: 25px;

            justify-content: center;

            flex-wrap: wrap;

        }


        .botao {

            display: inline-block;

            padding: 13px 22px;

            border-radius: 10px;

            text-decoration: none;

            color: white;

            font-weight: bold;

        }


        .urna {

            background: #2563eb;

        }


        .resultado {

            background: #374151;

        }

/* =========================
   MENU
========================= */

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
    background: white;
    color: #172033;
}


        /* RESPONSIVO */

        @media (max-width: 700px) {


            .topo {
        padding: 18px 15px 0;
    }

    .titulo h1 {
        font-size: 23px;
    }

    .menu {
        flex-direction: column;
        gap: 3px;
    }

    .menu a {
        text-align: center;
        border-radius: 7px;
    }


        }

    </style>

</head>


<body>


<div class="topo">

<h1>Cadastro de Eleitor</h1>

<p>Cadastre os Eleitore que participarão da votação</p>
        
<br><br>

<div class="topo-conteudo">

    <nav class="menu">

        <a href="Cad_Candidato.php">
            Cadastro Candidato
        </a>

        <a href="eleitores.php" class="ativo"   >
            Cadastro Eleitor
        </a>

        <a href="Votar.php">
            Votar
        </a>

        <a href="Apuração.php">
            Apuração
        </a>

    </nav>

</div>

</div>

<div class="container">

    <?php if ($mensagem): ?>

        <div class="mensagem">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>


    <?php if ($erro): ?>

        <div class="erro">
            <?= htmlspecialchars($erro) ?>
        </div>

    <?php endif; ?>


    <!-- FORMULÁRIO -->

    <div class="card">

        <h2>
            Novo eleitor
        </h2>


        <?php if ($mensagem !== ""): ?>

            <div class="mensagem <?= $tipoMensagem ?>">

                <?= htmlspecialchars($mensagem) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="formulario"
        >


            <div class="campo">

                <label for="nome">
                    Nome completo
                </label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="100"
                    value="<?= htmlspecialchars($nome ?? "") ?>"
                    placeholder="Digite o nome completo"
                    required
                >

            </div>


            <div class="campo">

                <label for="cpf">
                    CPF
                </label>

                <input
                    type="text"
                    id="cpf"
                    name="cpf"
                    maxlength="14"
                    value="<?= htmlspecialchars($cpf ?? "") ?>"
                    placeholder="000.000.000-00"
                    required
                >

            </div>


            <button
                type="submit"
                class="botao"
            >
                Cadastrar Eleitor
            </button>


        </form>

    </div>


    <!-- LISTA -->

    <div class="card">

        <h2>
            Eleitores cadastrados
        </h2>


        <?php if (count($eleitores) > 0): ?>


            <div class="tabela-container">


                <table>


                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Nome
                            </th>

                            <th>
                                CPF
                            </th>

                            <th>
                                Cadastro
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach ($eleitores as $eleitor): ?>


                            <tr>

                                <td>

                                    <?= (int)$eleitor["Id"] ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $eleitor["Nome"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $eleitor["CPF"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime(
                                            $eleitor["DataCadastro"]
                                        )
                                    ) ?>

                                </td>


                                <td>


                                    <?php if (
                                        (int)$eleitor["Votou"] === 1
                                    ): ?>

                                        <span
                                            class="status ja-votou"
                                        >

                                            ✓ Já votou

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="status nao-votou"
                                        >

                                            ○ Não votou

                                        </span>

                                    <?php endif; ?>


                                </td>

                            </tr>


                        <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        <?php else: ?>


            <p>
                Nenhum eleitor cadastrado ainda.
            </p>


        <?php endif; ?>


    </div>


    <!-- NAVEGAÇÃO -->

    <div class="acoes">


        <a
            href="urna.php"
            class="botao urna"
        >

            🗳️ IR PARA URNA

        </a>


        <a
            href="resultado.php"
            class="botao resultado"
        >

            📊 VER RESULTADO

        </a>


    </div>


</div>


<script>

    /*
     * Máscara simples para CPF
     */

    const campoCPF =
        document.getElementById("cpf");


    campoCPF.addEventListener(
        "input",
        function () {

            let valor =
                this.value.replace(/\D/g, "");


            valor =
                valor.substring(0, 11);


            if (valor.length > 9) {

                valor =
                    valor.replace(
                        /(\d{3})(\d{3})(\d{3})(\d{1,2})/,
                        "$1.$2.$3-$4"
                    );

            } else if (valor.length > 6) {

                valor =
                    valor.replace(
                        /(\d{3})(\d{3})(\d{1,3})/,
                        "$1.$2.$3"
                    );

            } else if (valor.length > 3) {

                valor =
                    valor.replace(
                        /(\d{3})(\d{1,3})/,
                        "$1.$2"
                    );

            }


            this.value = valor;

        }
    );

</script>


</body>

</html>