<?php

namespace App\Core;

use \GuzzleHttp\Client;
/* use \GuzzleHttp; */
use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use Exception;
use Throwable;

class Application
{
    public static string $ROOT_DIR;
    public static Application $app;
    private static $client;

    private static function init()
    {
        self::$client = new Client([
            'timeout'  => 9.0,
            'allow_redirects' => true,
            'headers' => ['Accept' => 'application/json']
        ]);
    }

    public function guzzleGet($url)
    {
        $post_client = new Client([
            'timeout'  => 2.0,
            'allow_redirects' => true
        ]);

        $return_data = "";

        $response = $post_client->request(
            'GET',
            $url,
            [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => 'qzb8erc8t7d6cc3cdq3qpeds865byq',
                    'Content-Type' => 'application/json'
                ]
            ]
        );

        echo $response->getBody();

        return $this->parsePosts($response->getBody());
    }

    private function parsePosts($json): array
    {
        echo $json;
        $jsonIterator = new RecursiveIteratorIterator(
            new RecursiveArrayIterator($json),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $posts = array();

        echo json_encode($jsonIterator);

        foreach ($jsonIterator as $key => $val) {
            if (is_array($val)) {
                if ($key === 'l') { // "l" => posts
                    $posts = $val;
                    echo json_encode($posts);
                }
                if ($key === 'bd') { // "bd" => category arrays d1-d16
                    $categories = $val;
                }
                if ($key === 'bm') { // "bm" => titles and category strings m1-m74
                    $titles = $val;
                }
                if ($key === 'br') { // "br" => images u, r1-r16
                    $images = $val;
                }
                if ($key === 'ba') { // "ba" => mrf pairs - mrf[x][y0] = a
                    $pairs = $val;
                }
                if ($key === 'bl') { // "bl" => tags - u, l1-l8
                    $tags = $val;
                }
            }
        }

        $updated_posts = array();

        foreach ($posts as $post) {
            foreach ($images as $key => $value) {
                if ($post['r'] === $key) { // add images to posts "r" => "r1"-"r16"
                    $post['r'] = $value;
                }
            }

            foreach ($categories as $key => $value) { // add category arrays to posts "d" => "d1"-"d16"
                if ($post['d'] === $key) {
                    $post['d'] = $value;
                }
            }

            foreach ($titles as $key => $value) {
                if ($post['mr'] === $key) {
                    $post['mr'] = $value;
                }
            }

            for ($i = 1; $i < 5; $i++) {
                foreach ($titles as $key => $value) {
                    if ($key === $post['d'][$i]['v']) {
                        $post['d'][$i]['v'] = $value;
                    }
                }
                foreach ($tags as $key => $value) {
                    if ($key === $post['d'][$i]['l']) {
                        $post['d'][$i]['l'] = $value;
                    }
                }
            }

            array_push($updated_posts, $post);
        }

        return $updated_posts;
    }

    public function __construct($rootPath)
    {
        self::$ROOT_DIR = $rootPath;

        self::$app = $this;
    }
}
