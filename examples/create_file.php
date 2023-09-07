<?php declare(strict_types=1);

use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;

require_once  __DIR__ .'/../vendor/autoload.php';

$file = fopen(__DIR__.'/../var/testfile.txt', 'wb');

$writer = static function (string $record) use ($file) {
    fwrite($file, $record."\n");
};

$chars = str_split('qwertyuiopasdfghjklzxcvbnm');

$count = 0;
$progressBar = static function () use (&$count) {
    if (++$count === 5000) {
        $count = 0;
        echo '.';
    }
};

Stream::from(Producers::sequentialInt(1, 1, 5_000_000))
    ->map(function (int $id) use ($chars) {
        $scoring = mt_rand(100, 10_000) / 100.0;
        $credits = mt_rand(0, 1_000_000);
        
        if (mt_rand(0,1) === 1) {
            $facebookId = md5($id.$scoring);
        } else {
            $facebookId = null;
        }
        
        shuffle($chars);
        
        return [
            'id' => $id,
            'name' => implode(array_slice($chars, mt_rand(3, 20))),
            'age' => mt_rand(15, 65),
            'isVerified' => mt_rand(0, 1) !== 1,
            'facebookId' => $facebookId,
            'hash' => sha1($id.$scoring.$credits),
            'credits' => $credits,
            'scoring' => $scoring,
        ];
    })
    ->map(Mappers::jsonEncode())
    ->call($writer)
    ->call($progressBar)
    ->run();

fclose($file);

echo PHP_EOL;