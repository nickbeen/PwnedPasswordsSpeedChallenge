#!/usr/bin/php
<?php

// @todo guzzle async
// @todo enable/disable/skip/clear cache
// @todo accept input/output file

namespace HaveIBeenPwned\PwnedPasswordsSpeedChallenge;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;

require __DIR__ . '/../vendor/autoload.php';

foreach ($argv as $arg) {
    switch (true) {
        case $arg === '--clear-cache':
            exit('clearing cache');
        case $arg === '--help':
            Help::usage();
            exit;
        case $arg === '--skip-cache':
            exit('skipping cache');
    }
}

$hibp = new HaveIBeenPwned();

echo "Running PHP " . phpversion() . "\n";

$passwords = file('C:\Users\Nick\PhpStormProjects\PwnedPasswordsSpeedChallenge\php\passwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

/*
$stack = HandlerStack::create();
$stack->push(
new CacheMiddleware(
    new GreedyCacheStrategy(
        new FlysystemStorage(
            new Local("/tmp/site")
        ), 180
        )
    )
);
*/

$client = new Client([
        'base_uri' => 'https://api.pwnedpasswords.com/range/'
]);

$passwordMap = [];

$requests = function () use ($client, $passwords, $hibp) {
    foreach ($passwords as $password) {
        $passwordMap[] = $password;
        $sha1 = strtoupper(sha1($password));
        $sha1_fragment = substr($sha1, 0, 5);

        yield function () use ($client, $sha1_fragment, $hibp) {
            return $client->getAsync($sha1_fragment, [
                'on_stats' => function (TransferStats $transferStats) use ($hibp) {
                    $hibp->transfer_time += $transferStats->getTransferTime();
                }
            ]);
        };

    }
};

$pool = new Pool(
    $client,
    $requests(), [
        'concurrency' => 10,
        'fulfilled' => function (ResponseInterface $response, $index) use ($passwordMap, $hibp) {
            $sha1 = strtoupper(sha1('123456'));
            $sha1_fragment = substr($sha1, 0, 5);

            $found = false;
            ++$hibp->checked_passwords;

            if ($response->hasHeader('CF-Cache-Status')) {
                ++$hibp->requests_to_api;
            } else {
                ++$hibp->requests_to_origin;
            }

            $body = $response->getBody()->getContents();

            foreach (explode(PHP_EOL, $body) as $item) {
                $line = explode(":", $item);

                if ($sha1_fragment . $line[0] === $sha1) {
                    echo sprintf('Found %s %d times', '123456', $line[1]) . "\n";

                    $hibp->results[] = [$passwordMap, $line[1]];

                    $found = true;
                }
            }

            if ($found === false) {
                echo sprintf('Password %s not found in HaveIBeenPwned', '123456') . "\n";
            }
        },
        'rejected' => function (RequestException $reason) {
            throw new \Exception($reason->getMessage());
        }
    ]
);

$promise = $pool->promise();
$promise->wait();

/*
foreach ($passwords as $password) {
    $sha1 = strtoupper(sha1($password));
    $sha1_fragment = substr($sha1, 0, 5);

    $found = false;
    ++$hibp->checked_passwords;

    try {
        $response = $client->get($sha1_fragment, [
            'on_stats' => function (TransferStats $transferStats) use ($hibp) {
                $hibp->transfer_time += $transferStats->getTransferTime();
            }
        ]);

        if ($response->hasHeader('CF-Cache-Status')) {
            ++$hibp->requests_to_api;
        } else {
            ++$hibp->requests_to_origin;
        }

        $body = $response->getBody()->getContents();

        foreach (explode(PHP_EOL, $body) as $item) {
            $line = explode(":", $item);

            if ($sha1_fragment . $line[0] === $sha1) {
                echo sprintf('Found %s %d times', $password, $line[1]) . "\n";

                $hibp->results[] = [$password, $line[1]];

                $found = true;
            }
        }

        if ($found === false) {
            echo sprintf('Password %s not found in HaveIBeenPwned', $password) . "\n";
        }
    } catch (GuzzleException $e) {
        echo $e;
    }
}
*/

echo sprintf('Finished processing %d passwords in %hms (%h passwords per second)', $hibp->checked_passwords, $hibp->getTransferTime(), $hibp->getCheckedPasswordsPerSecond()) . "\n";
echo sprintf('We made %d Cloudflare requests to the api (avg. response time: %hms)', $hibp->requests_to_api, $hibp->getAverageApiResponseTime()) , "\n";
echo sprintf('Of those, Cloudflare had already cached %h requests and made %h requests to the HaveIBeenPwned origin server.', $hibp->getTotalRequests(), $hibp->requests_to_origin);
print_r($hibp->results);
// @todo filename optional and settable
$csv = fopen('file.csv', 'w');

foreach ($hibp->results as $result) {
    //echo "\n\n" . $result . "\n\n";
    fputcsv($csv, $result);
}

fclose($csv);
