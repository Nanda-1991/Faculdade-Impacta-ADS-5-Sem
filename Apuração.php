<?php

$servidor = "localhost";
$banco = "EstudoWeb";
$usuario = "";
$senha = "";

try {

    $conexao = new PDO(
        "sqlsrv:Server=$servidor;Database=$banco;TrustServerCertificate=true",
        $usuario,
        $senha
    );

    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT
            u.Id AS CandidatoId,
            u.Matricula AS MatriculaCandidato,
            u.Nome AS NomeCandidato,
            COUNT(v.Id) AS TotalVotos
        FROM Usuarios u
        LEFT JOIN Votos v
            ON v.Matricula_Candidato = u.Matricula
        GROUP BY
            u.Id,
            u.Matricula,
            u.Nome
        ORDER BY
            COUNT(v.Id) DESC,
            u.Nome ASC
    ";

    $consulta = $conexao->query($sql);
    $candidatos = $consulta->fetchAll(PDO::FETCH_ASSOC);

    $sqlFotos = "SELECT Id, Foto FROM Usuarios";
    $consultaFotos = $conexao->query($sqlFotos);
    $fotosPorId = [];

    while ($row = $consultaFotos->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($row["Foto"])) {
            $fotosPorId[$row["Id"]] = "data:image/jpeg;base64," . base64_encode($row["Foto"]);
        } else {
            $fotosPorId[$row["Id"]] = "";
        }
    }

    foreach ($candidatos as &$cand) {
        $cand["FotoBase64"] = $fotosPorId[$cand["CandidatoId"]] ?? "";
    }
    unset($cand);

    $totalVotos = 0;
    foreach ($candidatos as $candidato) {
        $totalVotos += (int)$candidato["TotalVotos"];
    }

    $maiorNumeroVotos = 0;
    if (count($candidatos) > 0) {
        $maiorNumeroVotos = (int)$candidatos[0]["TotalVotos"];
    }

    $primeirosColocados = [];
    foreach ($candidatos as $candidato) {
        if (
            $maiorNumeroVotos > 0 &&
            (int)$candidato["TotalVotos"] === $maiorNumeroVotos
        ) {
            $primeirosColocados[] = $candidato;
        }
    }

    $empate = count($primeirosColocados) > 1;

} catch (PDOException $erro) {
    die("Erro ao consultar os resultados: " . $erro->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apuração dos Votos</title>

    <style>
        * { box-sizing: border-box; }
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
           CONTEÚDO APURAÇÃO
        ========================= */
        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px 30px;
        }

        /* RESUMO */
        .resumo {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .card-resumo {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 6px 18px rgba(0,0,0,0.07);
        }
        .card-resumo .numero { font-size: 30px; font-weight: bold; color: #3949ab; }
        .card-resumo .titulo { color: #6b7280; margin-top: 5px; }

        /* AREA DOS PRIMEIROS COLOCADOS */
        .vencedor {
            background: linear-gradient(135deg, #fff7cc, #fff1a8);
            border: 2px solid #e5c100;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.10);
        }
        .trofeu { font-size: 55px; }
        .titulo-vencedor { font-size: 20px; font-weight: bold; color: #856404; margin-bottom: 25px; }
        .lista-vencedores {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 35px;
            flex-wrap: wrap;
        }
        .card-vencedor {
            background: rgba(255,255,255,0.65);
            border-radius: 18px;
            padding: 20px;
            min-width: 220px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .foto-vencedor {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 5px 18px rgba(0,0,0,0.20);
            background: #eeeeee;
        }
        .nome-vencedor { font-size: 24px; font-weight: bold; margin-top: 15px; color: #333; }
        .matricula-vencedor { font-size: 14px; color: #666; margin-top: 4px; }
        .votos-vencedor { font-size: 18px; margin-top: 8px; color: #555; }

        /* EMPATE */
        .empate {
            background: #fff3cd;
            border: 1px solid #ffe69c;
            color: #664d03;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        /* RANKING */
        .ranking {
            background: white;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .ranking h2 { margin-top: 0; margin-bottom: 20px; color: #172033; }
        .candidato {
            display: grid;
            grid-template-columns: 70px 80px 1fr 130px;
            align-items: center;
            gap: 15px;
            padding: 15px 10px;
            border-bottom: 1px solid #eeeeee;
        }
        .candidato:last-child { border-bottom: none; }
        .posicao { font-size: 25px; font-weight: bold; text-align: center; }
        .foto-candidato {
            width: 65px;
            height: 65px;
            border-radius: 50%;
            object-fit: cover;
            background: #eeeeee;
            border: 3px solid #e5e7eb;
        }
        .nome { font-size: 18px; font-weight: bold; color: #333; }
        .matricula-tag {
            font-size: 13px;
            font-weight: normal;
            color: #6b7280;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 6px;
            margin-left: 6px;
        }
        .barra-container {
            margin-top: 8px;
            width: 100%;
            height: 12px;
            background: #e5e7eb;
            border-radius: 20px;
            overflow: hidden;
        }
        .barra { height: 100%; background: #3949ab; border-radius: 20px; }
        .dados { text-align: right; }
        .quantidade-votos { font-size: 20px; font-weight: bold; }
        .porcentagem { color: #6b7280; font-size: 14px; margin-top: 4px; }
        .sem-votos { text-align: center; padding: 40px; color: #777; font-size: 18px; }

        /* BOTÃO */
        .acoes { text-align: center; margin-top: 25px; }
        .botao {
            display: inline-block;
            padding: 14px 25px;
            border-radius: 10px;
            background: #3949ab;
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s;
        }
        .botao:hover { background: #303f9f; }

        /* RESPONSIVO */
        @media (max-width: 700px) {
            .topo { padding: 18px 15px 0; }
            .menu { flex-direction: column; gap: 3px; }
            .menu a { text-align: center; border-radius: 7px; }
            .resumo { grid-template-columns: 1fr; }
            .lista-vencedores { flex-direction: column; align-items: center; }
            .candidato { grid-template-columns: 45px 60px 1fr; }
            .dados { grid-column: 3; text-align: left; }
            .foto-candidato { width: 55px; height: 55px; }
            .nome { font-size: 16px; }
        }
    </style>
</head>
<body>

<div class="topo">
    <h1>Apuração dos Votos</h1>
    <p>Acompanhe o resultado da votação em tempo real</p>

    <br><br>

    <div class="topo-conteudo">
        <nav class="menu">
            <a href="Cad_Candidato.php">Cadastro Candidato</a>
            <a href="eleitores.php">Cadastro Eleitor</a>
            <a href="Urna.php">Votar</a>
            <a href="Apuração.php" class="ativo">Apuração</a>
        </nav>
    </div>
</div>

<div class="container">

    <?php if (count($candidatos) > 0): ?>

        <!-- RESUMO -->
        <div class="resumo">
            <div class="card-resumo">
                <div class="numero"><?= $totalVotos ?></div>
                <div class="titulo">Total de votos</div>
            </div>

            <div class="card-resumo">
                <div class="numero"><?= count($candidatos) ?></div>
                <div class="titulo">Candidatos</div>
            </div>

            <div class="card-resumo">
                <div class="numero">
                    <?php
                    if ($totalVotos > 0) {
                        echo number_format(($maiorNumeroVotos / $totalVotos) * 100, 1, ',', '.');
                    } else {
                        echo "0,0";
                    }
                    ?>%
                </div>
                <div class="titulo">Percentual da primeira colocação</div>
            </div>
        </div>

        <!-- PRIMEIRA COLOCAÇÃO -->
        <?php if ($maiorNumeroVotos > 0): ?>
            <div class="vencedor">
                <div class="trofeu">🏆</div>

                <div class="titulo-vencedor">
                    <?php if ($empate): ?>
                        EMPATE NA PRIMEIRA COLOCAÇÃO
                    <?php else: ?>
                        CANDIDATO VENCEDOR
                    <?php endif; ?>
                </div>

                <?php if ($empate): ?>
                    <div class="empate">
                        ⚠️ <?= count($primeirosColocados) ?> candidatos empataram com
                        <?= $maiorNumeroVotos ?> <?= $maiorNumeroVotos == 1 ? "voto" : "votos" ?>.
                    </div>
                <?php endif; ?>

                <!-- TODOS OS PRIMEIROS COLOCADOS -->
                <div class="lista-vencedores">
                    <?php foreach ($primeirosColocados as $primeiro): ?>
                        <?php
                        $votosPrimeiro = (int)$primeiro["TotalVotos"];
                        $porcentagemPrimeiro = $totalVotos > 0 ? ($votosPrimeiro / $totalVotos) * 100 : 0;
                        ?>
                        <div class="card-vencedor">
                            <div style="font-size: 35px;">🥇</div>

                            <!-- FOTO -->
                            <?php if (!empty($primeiro["FotoBase64"])): ?>
                                <img
                                    class="foto-vencedor"
                                    src="<?= $primeiro["FotoBase64"] ?>"
                                    alt="Foto de <?= htmlspecialchars($primeiro["NomeCandidato"]) ?>"
                                >
                            <?php endif; ?>

                            <!-- NOME -->
                            <div class="nome-vencedor">
                                <?= htmlspecialchars($primeiro["NomeCandidato"]) ?>
                            </div>

                            <div class="matricula-vencedor">
                                Nº <?= htmlspecialchars($primeiro["MatriculaCandidato"]) ?>
                            </div>

                            <!-- VOTOS -->
                            <div class="votos-vencedor">
                                <strong>
                                    <?= $votosPrimeiro ?>
                                    <?= $votosPrimeiro == 1 ? "voto" : "votos" ?>
                                </strong>
                                <br>
                                <?= number_format($porcentagemPrimeiro, 1, ',', '.') ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- RANKING COMPLETO -->
        <div class="ranking">
            <h2>📊 Ranking dos candidatos</h2>

            <?php foreach ($candidatos as $posicao => $candidato): ?>
                <?php
                $votos = (int)$candidato["TotalVotos"];
                $porcentagem = $totalVotos > 0 ? ($votos / $totalVotos) * 100 : 0;
                ?>

                <div class="candidato">
                    <!-- POSIÇÃO -->
                    <div class="posicao">
                        <?php if ($votos > 0 && $votos === $maiorNumeroVotos): ?>
                            🥇
                        <?php elseif ($posicao === 1 && $votos > 0): ?>
                            🥈
                        <?php elseif ($posicao === 2 && $votos > 0): ?>
                            🥉
                        <?php else: ?>
                            <?= $posicao + 1 ?>º
                        <?php endif; ?>
                    </div>

                    <!-- FOTO -->
                    <div>
                        <?php if (!empty($candidato["FotoBase64"])): ?>
                            <img
                                class="foto-candidato"
                                src="<?= $candidato["FotoBase64"] ?>"
                                alt="Foto de <?= htmlspecialchars($candidato["NomeCandidato"]) ?>"
                            >
                        <?php endif; ?>
                    </div>

                    <!-- NOME + BARRA -->
                    <div>
                        <div class="nome">
                            <?= htmlspecialchars($candidato["NomeCandidato"]) ?>
                            <span class="matricula-tag">Nº <?= htmlspecialchars($candidato["MatriculaCandidato"]) ?></span>
                        </div>

                        <div class="barra-container">
                            <div class="barra" style="width: <?= $porcentagem ?>%;"></div>
                        </div>
                    </div>

                    <!-- VOTOS -->
                    <div class="dados">
                        <div class="quantidade-votos">
                            <?= $votos ?> <?= $votos == 1 ? "voto" : "votos" ?>
                        </div>
                        <div class="porcentagem">
                            <?= number_format($porcentagem, 1, ',', '.') ?>%
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="ranking">
            <div class="sem-votos">Nenhum candidato cadastrado.</div>
        </div>
    <?php endif; ?>

    <!-- BOTÃO -->
    <div class="acoes">
        <a href="Urna.php" class="botao">🗳️ VOLTAR PARA A URNA</a>
    </div>

</div>

</body>
</html>
