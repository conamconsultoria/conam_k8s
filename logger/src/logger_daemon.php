<?php
$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_database = getenv('DB_DATABASE');
$db_user = getenv('DB_USER');
$db_password = getenv('DB_PASSWORD');

$broker_host = getenv('KAFKA_BROKER_HOST');
$broker_port = getenv('KAFKA_BROKER_PORT');

// Aguardar todo mundo levantar
sleep(20);

$conn = pg_pconnect("host={$db_host} port={$db_port} dbname={$db_database} user={$db_user} password={$db_password}");
if (!$conn) {
    echo "An error occurred.\n";
    exit;
}

$sql_create = "
    CREATE TABLE IF NOT EXISTS log (id SERIAL PRIMARY KEY, data DATE, log TEXT);
";
pg_query($conn, $sql_create);

$conf = new RdKafka\Conf();
$conf->set('group.id', 'myConsumerGroup');

// Initial list of Kafka brokers
$conf->set('metadata.broker.list', "{$broker_host}:$broker_port");

// Set where to start consuming messages when there is no initial offset in
// offset store or the desired offset is out of range.
// 'earliest': start from the beginning
$conf->set('auto.offset.reset', 'earliest');

// Emit EOF event when reaching the end of a partition
$conf->set('enable.partition.eof', 'true');

$consumer = new RdKafka\KafkaConsumer($conf);

// Subscribe to topic 'test'
$consumer->subscribe(['logger']);

while (true) {
    $message = $consumer->consume(120*1000);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            var_dump($message);
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "No more messages; will wait for more\n";
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "Timed out\n";
            break;
        default:
            throw new \Exception($message->errstr(), $message->err);
            break;
    }
}