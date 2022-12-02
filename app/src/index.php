<?php
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_database = getenv('DB_DATABASE');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');

$redis_host = getenv('REDIS_HOST');
$redis_port = getenv('REDIS_PORT');

ini_set('session.save_handler', 'redis');
ini_set('session.save_path', "tcp://{$redis_host}:{$redis_port}");

session_start();
$logado = false;
$session_id = '';

$conn = pg_pconnect("host={$db_host} port={$db_port} dbname={$db_database} user={$db_user} password={$db_password}");
if (!$conn) {
    echo "An error occurred.\n";
    exit;
}

$broker_host = getenv('KAFKA_BROKER_HOST');
$broker_port = getenv('KAFKA_BROKER_PORT');

$conf = new RdKafka\Conf();
// $conf->set('log_level', (string) LOG_DEBUG);
// $conf->set('debug', 'all');
$rk = new RdKafka\Producer($conf);
$rk->addBrokers("{$broker_host}:{$broker_port}");

$topic = $rk->newTopic("logger");

if (isset($_SESSION["logado"]) && $_SESSION["logado"] == '1') {
    $logado = true;
    $session_id = $_SESSION["ID"];
} 

if(isset($_GET["login"])) {
    $logado = true;
    $session_id = uniqid();
    $_SESSION["logado"] = '1';
    $_SESSION["ID"] = $session_id;

    $log_object = new stdClass;
    $log_object->usuario = $session_id;
    $log_object->data = date("Y-m-d H:i:s");
    $log_object->acao = "login";
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($log_object));    

} elseif(isset($_GET["logout"])) {
    $log_object = new stdClass;
    $log_object->usuario = $session_id;
    $log_object->data = date("Y-m-d H:i:s");
    $log_object->acao = "logout";
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($log_object));

    $_SESSION["logado"] = null;
    unset($_SESSION["logado"]);
    $_SESSION["ID"] = null;
    unset($_SESSION["ID"]);
    session_destroy();
}

$conn = pg_pconnect("host={$db_host} port={$db_port} dbname={$db_database} user={$db_user} password={$db_password}");
if (!$conn) {
    echo "An error occurred.\n";
    exit;
}

$sql_create = "
    CREATE TABLE IF NOT EXISTS teste_valores (id SERIAL PRIMARY KEY, nome TEXT NOT NULL, valor TEXT, quantidade TEXT);
";
pg_query($conn, $sql_create);

if ($_POST["submit"] == 'incluir') {
    // $result = pg_query_params($dbconn, 'SELECT * FROM shops WHERE name = $1', array("Joe's Widgets"));
    $valores = [$_POST["nome"], $_POST["valor"], $_POST["quantidade"]];
    $result = pg_query_params('INSERT INTO teste_valores (nome, valor, quantidade) VALUES ($1, $2, $3)', $valores);

    $log_object = new stdClass;
    $log_object->usuario = $session_id;
    $log_object->data = date("Y-m-d H:i:s");
    $log_object->acao = "inclusao";
    $log_object->valores = $valores;
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($log_object));
} elseif (isset($_GET["apagar"]) && $_GET["apagar"] > 0) {
    // $result = pg_query_params($dbconn, 'SELECT * FROM shops WHERE name = $1', array("Joe's Widgets"));
    $log_object = new stdClass;
    $log_object->usuario = $session_id;
    $log_object->data = date("Y-m-d H:i:s");
    $log_object->acao = "exclusao";
    $log_object->id = $_GET["apagar"];

    $valores = [$_GET["apagar"]];
    $result = pg_query_params('DELETE FROM teste_valores WHERE id=$1', $valores);    
}

$rk->flush(100);

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CONAM AWS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/purecss@3.0.0/build/pure-min.css" integrity="sha384-X38yfunGUhNzHpBaEBsWLO+A0HDYOQi8ufWDkZ0k9e0eXz/tH3II7uKZ9msv++Ls" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/purecss@3.0.0/build/grids-responsive-min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="header">
    <div class="home-menu pure-menu pure-menu-horizontal pure-menu-fixed">
        <a class="pure-menu-heading" href="">Test App v1</a>

        <ul class="pure-menu-list">
            <li class="pure-menu-item"><?= ($logado ? "Session ID: {$session_id}" : "Não logado");?></a></li>
            <li class="pure-menu-item">
                <a href="index.php<?= ($logado ? "?logout=1" : "?login=1");?>" class="pure-menu-link">
                <?= ($logado ? "Logout" : "Login");?></a></li>
        </ul>
    </div>
</div>
<div class="content-wrapper">
    <div class="content">
        <h2 class="content-head is-center">Excepteur sint occaecat cupidatat.</h2>

        <div class="pure-g">
            <?php
            $sql = "SELECT * FROM teste_valores ORDER BY id;";
            $result = pg_query($conn, $sql);
            while($row = pg_fetch_row($result)) {
            ?>
            <div class="l-box pure-u-1 pure-u-md-1-2 pure-u-lg-1-4">

                <h3 class="content-subhead">
                    <i class="fa fa-rocket"></i>
                    <?=$row[1];?>
                </h3>
                <p>
                    Valor: <?=$row[2];?> Quantidade: <?=$row[3];?>
                </p>
                <p>
                    <a href="index.php?apagar=<?=$row[0];?>" class="pure-menu-link">Apagar</a>
                </p>
            </div>
            <?php
            }
            ?>
        </div>
    </div>    

    <div class="content">
        <h2 class="content-head is-center">Dolore magna aliqua. Uis aute irure.</h2>

        <div class="pure-g">
            <div class="l-box-lrg pure-u-1 pure-u-md-2-5">
                <form class="pure-form pure-form-stacked" method="post" action="index.php">
                    <fieldset>

                        <label for="nome">Nome</label>
                        <input id="nome" name="nome" type="text" placeholder="Nome" <?= ($logado ? "" : "disabled=disabled");?>>

                        <label for="valor">Valor</label>
                        <input id="valor" name="valor" type="text" placeholder="Valor" <?= ($logado ? "" : "disabled=disabled");?>>

                        <label for="quantidade">Quantidade</label>
                        <input id="quantidade" name="quantidade" type="text" placeholder="Quantidade" <?= ($logado ? "" : "disabled=disabled");?>>

                        <button type="submit" class="pure-button" name="submit" value="incluir" <?= ($logado ? "" : "disabled=disabled");?>>Incluir</button>
                    </fieldset>
                </form>
            </div>

            <div class="l-box-lrg pure-u-1 pure-u-md-3-5">
                <h4>Contact Us</h4>
                <p>
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                    tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
                    quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
                    consequat.
                </p>

                <h4>More Information</h4>
                <p>
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
                    tempor incididunt ut labore et dolore magna aliqua.
                </p>
            </div>
        </div>

    </div>

</div>

</body>
</html>