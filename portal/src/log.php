<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PORTAL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/purecss@3.0.0/build/pure-min.css" integrity="sha384-X38yfunGUhNzHpBaEBsWLO+A0HDYOQi8ufWDkZ0k9e0eXz/tH3II7uKZ9msv++Ls" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/purecss@3.0.0/build/grids-responsive-min.css">
</head>
<body>

<h1>LOGS</h1>
<?php
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_database = getenv('DB_DATABASE');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');

$conn = pg_pconnect("host={$db_host} port={$db_port} dbname={$db_database} user={$db_user} password={$db_password}");
if (!$conn) {
    echo "An error occurred.\n";
    exit;
}

$broker_host = getenv('KAFKA_BROKER_HOST');
$broker_port = getenv('KAFKA_BROKER_PORT');

$conf = new RdKafka\Conf();
$rk = new RdKafka\Producer($conf);
$rk->addBrokers("{$broker_host}:{$broker_port}");

$topic = $rk->newTopic("logger");
$log_object = new stdClass;
$log_object->data = date("Y-m-d H:i:s");
$log_object->acao = "log-view";
$topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode($log_object)); 

?>
    <ul>
        <?php
        $sql = "SELECT data, log FROM log ORDER BY data DESC LIMIT 50;";
        $result = pg_query($conn, $sql);
        while($row = pg_fetch_row($result)) {
        ?>
        <li><?=$row[0];?> <br> Log: <?=$row[1];?></li>
        <?php
        }
        ?>
    </ul>

</body>
</html>