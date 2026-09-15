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

    // =========================================================
    // EXIBIR FOTO
    // =========================================================

    if (isset($_GET["foto"])) {

        $id = filter_input(INPUT_GET, "foto", FILTER_VALIDATE_INT);

        if ($id) {

            $sql = "SELECT Foto FROM Usuarios WHERE Id = :id";

            $consulta = $conexao->prepare($sql);
            $consulta->bindValue(":id", $id, PDO::PARAM_INT);
            $consulta->execute();

            $foto = $consulta->fetchColumn();

            if ($foto !== false && $foto !== null) {

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $tipo = $finfo->buffer($foto);

                header("Content-Type: " . $tipo);
                echo $foto;
                exit;
            }
        }

        http_response_code(404);
        exit("Foto não encontrada.");
    }


    // =========================================================
    // CADASTRO DO CANDIDATO
    // =========================================================

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $matricula = trim($_POST["matricula"] ?? "");
        $cpf = trim($_POST["cpf"] ?? "");

        // Remove caracteres do CPF
        $cpfNumeros = preg_replace("/\D/", "", $cpf);


        // -----------------------------------------------------
        // VALIDAÇÕES
        // -----------------------------------------------------

        if ($nome === "" || $email === "" || $matricula === "" || $cpf === "") {

            $erro = "Preencha todos os campos obrigatórios.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $erro = "Digite um e-mail válido.";

        } elseif (strlen($cpfNumeros) !== 11) {

            $erro = "O CPF deve possuir 11 números.";

        } else {

            // Formata CPF
            $cpfFormatado = preg_replace(
                "/(\d{3})(\d{3})(\d{3})(\d{2})/",
                "$1.$2.$3-$4",
                $cpfNumeros
            );


            // -------------------------------------------------
            // VERIFICA MATRÍCULA DUPLICADA
            // -------------------------------------------------

            $sql = "
                SELECT COUNT(*)
                FROM Usuarios
                WHERE Matricula = :matricula
            ";

            $consulta = $conexao->prepare($sql);
            $consulta->bindValue(
                ":matricula",
                $matricula,
                PDO::PARAM_STR
            );
            $consulta->execute();

            if ($consulta->fetchColumn() > 0) {

                $erro = "Esta matrícula já está cadastrada.";

            } else {

                // ---------------------------------------------
                // VERIFICA CPF DUPLICADO
                // ---------------------------------------------

                $sql = "
                    SELECT COUNT(*)
                    FROM Usuarios
                    WHERE CPF = :cpf
                ";

                $consulta = $conexao->prepare($sql);
                $consulta->bindValue(
                    ":cpf",
                    $cpfFormatado,
                    PDO::PARAM_STR
                );
                $consulta->execute();

                if ($consulta->fetchColumn() > 0) {

                    $erro = "Este CPF já está cadastrado.";

                } else {

                    // -----------------------------------------
                    // FOTO
                    // -----------------------------------------

                    $foto = null;

                    if (
                        isset($_FILES["foto"]) &&
                        $_FILES["foto"]["error"] !== UPLOAD_ERR_NO_FILE
                    ) {

                        if ($_FILES["foto"]["error"] !== UPLOAD_ERR_OK) {

                            $erro = "Erro ao enviar a foto.";

                        } elseif ($_FILES["foto"]["size"] > 2 * 1024 * 1024) {

                            $erro = "A foto deve ter no máximo 2 MB.";

                        } else {

                            $finfo = new finfo(FILEINFO_MIME_TYPE);
                            $tipo = $finfo->file($_FILES["foto"]["tmp_name"]);

                            $tiposPermitidos = [
                                "image/jpeg",
                                "image/png",
                                "image/gif",
                                "image/webp"
                            ];

                            if (!in_array($tipo, $tiposPermitidos, true)) {

                                $erro = "Formato de imagem não permitido.";

                            } else {

                                $foto = file_get_contents(
                                    $_FILES["foto"]["tmp_name"]
                                );
                            }
                        }
                    }


                    // -----------------------------------------
                    // INSERT
                    // -----------------------------------------

                    if ($erro === "") {

                        try {

                            $sql = "
                                INSERT INTO Usuarios
                                (
                                    Nome,
                                    Email,
                                    Matricula,
                                    CPF,
                                    Foto
                                )
                                VALUES
                                (
                                    :nome,
                                    :email,
                                    :matricula,
                                    :cpf,
                                    :foto
                                )
                            ";

                            $consulta = $conexao->prepare($sql);

                            $consulta->bindValue(
                                ":nome",
                                $nome,
                                PDO::PARAM_STR
                            );

                            $consulta->bindValue(
                                ":email",
                                $email,
                                PDO::PARAM_STR
                            );

                            $consulta->bindValue(
                                ":matricula",
                                $matricula,
                                PDO::PARAM_STR
                            );

                            $consulta->bindValue(
                                ":cpf",
                                $cpfFormatado,
                                PDO::PARAM_STR
                            );

                            if ($foto !== null) {

                                $consulta->bindParam(
                                    ":foto",
                                    $foto,
                                    PDO::PARAM_LOB,
                                    0,
                                    PDO::SQLSRV_ENCODING_BINARY
                                );

                            } else {

                                $consulta->bindValue(
                                    ":foto",
                                    null,
                                    PDO::PARAM_NULL
                                );
                            }

                            $consulta->execute();

                            $mensagem = "Candidato cadastrado com sucesso!";

                            // Limpa os campos
                            $nome = "";
                            $email = "";
                            $matricula = "";
                            $cpf = "";

                        } catch (PDOException $e) {

                            $erro = "Erro ao salvar no banco: " .
                                $e->getMessage();
                        }
                    }
                }
            }
        }
    }


    // =========================================================
    // LISTA DE CANDIDATOS
    // =========================================================

    $sql = "
        SELECT
            Id,
            Nome,
            Email,
            Matricula,
            CPF
        FROM Usuarios
        ORDER BY Id DESC
    ";

    $consulta = $conexao->query($sql);

    $candidatos = $consulta->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {

    die("Erro na conexão: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Cadastro de Candidatos</title>

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

        .sem-foto {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #777;
        }

        .cpf {
            white-space: nowrap;
        }

        @media (max-width: 700px) {

            .grid {
                grid-template-columns: 1fr;
            }

            .foto {
                grid-column: span 1;
            }

            .card {
                padding: 18px;
                overflow-x: auto;
            }

            table {
                min-width: 750px;
            }
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

.titulo {
    text-align: center;
    padding-bottom: 20px;
}

.titulo h1 {
    margin: 0 0 6px;
    font-size: 28px;
}

.titulo p {
    margin: 0;
    color: #cbd3df;
    font-size: 14px;
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

/* =========================
   RESPONSIVO
========================= */

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

    <h1>Cadastro de Candidatos</h1>

    <p>Cadastre os candidatos que participarão da votação</p>

    <br><br>

    <div class="topo-conteudo">

        <nav class="menu">

            <a href="Cad_Candidato.php" class="ativo">
                Cadastro Candidato
            </a>

            <a href="Cad_Eleitor.php">
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


    <div class="card">

        <h2>Novo candidato</h2>

        <form
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="grid">

                <div class="campo">

                    <label for="nome">
                        Nome *
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="100"
                        value="<?= htmlspecialchars($nome ?? '') ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="email">
                        E-mail *
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="matricula">
                        Matrícula *
                    </label>

                    <input
                        type="text"
                        id="matricula"
                        name="matricula"
                        maxlength="20"
                        value="<?= htmlspecialchars($matricula ?? '') ?>"
                        placeholder="Ex.: 0001"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="cpf">
                        CPF *
                    </label>

                    <input
                        type="text"
                        id="cpf"
                        name="cpf"
                        maxlength="14"
                        value="<?= htmlspecialchars($cpf ?? '') ?>"
                        placeholder="000.000.000-00"
                        required
                    >

                </div>


                <div class="campo foto">

                    <label for="foto">
                        Foto do candidato
                    </label>

                    <input
                        type="file"
                        id="foto"
                        name="foto"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                    >

                    <small>
                        JPG, PNG — máximo 2 MB
                    </small>

                </div>

            </div>


            <button
                type="submit"
                class="botao"
            >
                CADASTRAR CANDIDATO
            </button>

        </form>

    </div>


    <div class="card">

        <h2>Candidatos cadastrados</h2>

        <table>

            <thead>

                <tr>

                    <th>Foto</th>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Matrícula</th>
                    <th>CPF</th>
                    <th>E-mail</th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($candidatos as $candidato): ?>

                <tr>

                    <td>

                        <?php
                        $temFoto = false;

                        $sqlFoto = "
                            SELECT Foto
                            FROM Usuarios
                            WHERE Id = :id
                        ";

                        $consultaFoto = $conexao->prepare($sqlFoto);

                        $consultaFoto->bindValue(
                            ":id",
                            $candidato["Id"],
                            PDO::PARAM_INT
                        );

                        $consultaFoto->execute();

                        $fotoCandidato = $consultaFoto->fetchColumn();

                        if ($fotoCandidato !== false && $fotoCandidato !== null) {
                            $temFoto = true;
                        }
                        ?>


                        <?php if ($temFoto): ?>

                            <img
                                class="foto-mini"
                                src="Cad_Candidato.php?foto=<?= (int)$candidato["Id"] ?>"
                                alt="Foto de <?= htmlspecialchars($candidato["Nome"]) ?>"
                            >

                        <?php else: ?>

                            <div class="sem-foto">
                                Sem foto
                            </div>

                        <?php endif; ?>

                    </td>


                    <td>
                        <?= (int)$candidato["Id"] ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($candidato["Nome"]) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($candidato["Matricula"]) ?>
                    </td>


                    <td class="cpf">
                        <?= htmlspecialchars($candidato["CPF"]) ?>
                    </td>


                    <td>
                        <?= htmlspecialchars($candidato["Email"]) ?>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

<small>
    <p align = center> Criado por Fernanda Sanchez </p>
</small>

<script>

    // Máscara de CPF

    const cpf = document.getElementById("cpf");

    cpf.addEventListener("input", function () {

        let valor = this.value.replace(/\D/g, "");

        valor = valor.substring(0, 11);

        if (valor.length > 9) {

            valor = valor.replace(
                /(\d{3})(\d{3})(\d{3})(\d{1,2})/,
                "$1.$2.$3-$4"
            );

        } else if (valor.length > 6) {

            valor = valor.replace(
                /(\d{3})(\d{3})(\d{1,3})/,
                "$1.$2.$3"
            );

        } else if (valor.length > 3) {

            valor = valor.replace(
                /(\d{3})(\d{1,3})/,
                "$1.$2"
            );
        }

        this.value = valor;

    });

</script>

</body>

</html>