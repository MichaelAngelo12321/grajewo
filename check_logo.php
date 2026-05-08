<?php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$pdoLegacy = new PDO('mysql:host=127.0.0.1;port=3306;dbname=legacy_db', 'root', 'password');
$res = $pdoLegacy->query("SELECT * FROM upload_file WHERE name LIKE '%logo%'")->fetchAll(PDO::FETCH_ASSOC);
foreach($res as $r) {
    echo "{$r['name']} - {$r['url']}\n";
}
