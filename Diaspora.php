<?php
/*
 * ----------------------------------------------------------------------------
 * "THE VODKA-WARE LICENSE" (Revision 42):
 * <tim@datenkonten.me> wrote this file.  As long as you retain this notice you
 * can do whatever you want with this stuff. If we meet some day, and you think
 * this stuff is worth it, you can buy me a vodka in return.     Tim Schumacher
 * ----------------------------------------------------------------------------
 */

class Diaspora
{
    private $cookie = '';
    private $pod = '';
    /** @var GuzzleHttp\Cookie\CookieJar  */
    private $cookie_jar = null;
    private $client = null;
    private $csfr = '';

    function __construct($pod, $verify = true)
    {
        $this->pod = $pod;
        $this->cookie_jar = new GuzzleHttp\Cookie\CookieJar();
        $this->client = new GuzzleHttp\Client([
            'base_url' => sprintf('https://%s/', $pod),
            'defaults' => [
                'verify' => true,
                'debug' => false,
                'cookies' => $this->cookie_jar,
            ],
        ]);
    }

    public function signIn($username, $password)
    {
        $post_data = [
            'authenticity_token' => '',
            'commit' => 'Sign in',
            'user' => [
                'username' => $username,
                'remember_me' => 1,
                'password' => $password,
            ],
            'utf8' => '✓',
        ];

        // first obtain a valid authenticity token
        /** @var \GuzzleHttp\Message\Response $response */
        $response = $this->client->get('/users/sign_in');

        $body = $response->getBody();

        $dom = new DOMDocument;
        @$dom->loadHTML($body);
        $metas = $dom->getElementsByTagName('input');
        foreach ($metas as $meta) {
            /** @var DOMElement $meta */
            if ($meta->getAttribute('name') == 'authenticity_token') {
                $post_data['authenticity_token'] = $meta->getAttribute('value');
            }
        }

        // Now login!
        $response = $this->client->post('/users/sign_in', [
            'body' => $post_data
        ]);

        $dom = new DOMDocument;
        @$dom->loadHTML($body);
        $metas = $dom->getElementsByTagName('meta');
        foreach ($metas as $meta) {
            /** @var DOMElement $meta */
            if ($meta->getAttribute('name') == 'csrf-token') {
                $this->csfr = $meta->getAttribute('content');
            }
        }


    }

    public function post($aspect, $text)
    {
        $post_data = [
            'aspect_ids' => $aspect,
            'location_coords' => '',
            'poll_question' => '',
            'poll_answers' => [
                "",
                "",
            ],
            'status_message' => [
                'text' => $text,
            ]
        ];


        /** @var \GuzzleHttp\Message\ResponseInterface $response */
        $response = $this->client->post('/status_messages', [
            'body' => json_encode($post_data),
            'headers' => [
                'X-CSRF-Token' => $this->csfr,
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Requested-With' => 'XMLHttpRequest',
            ],
        ]);

        //var_dump([$response->getStatusCode(),$response->getBody()->getContents()]);
    }
} 