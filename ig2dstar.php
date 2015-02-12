<?php

require_once("vendor/autoload.php");
require_once("config.php");
require_once("Diaspora.php");

$client = new GuzzleHttp\Client();
/** @var \GuzzleHttp\Message\Response $response */
$response = $client->get('http://instagram.com/' . $config['instagram']['user']);

$body = $response->getBody();

$dom = new DOMDocument;
@$dom->loadHTML($body);
$scripts = $dom->getElementsByTagName('script');
$json = null;
foreach($scripts as $script) {
    /** @var DOMNode $script */
    if (strpos($script->textContent,'window._sharedData') !== false) {
        $json = str_replace('window._sharedData = ','',$script->textContent);
        $json = substr($json,0,strlen($json) - 1);
        $json = json_decode($json);
    }
}

if ($json instanceof stdClass) {
    $posted = unserialize(file_get_contents('posted'));
    if (!is_array($posted))
        $posted = [];
    list($user,$pod) = explode('@',$config['diaspora']['id']);
    $diasp = new Diaspora($pod,false);
    $diasp->signIn($user,$config['diaspora']['password']);
    $pictures = $json->entry_data->UserProfile[0]->userMedia;
    foreach($pictures as $picture) {
        if (in_array($picture->code,$posted))
            continue;
        $stuff = [$picture->link,$picture->caption->text,$picture->images->standard_resolution->url,$picture->location];
        $markdown = sprintf("![%s](%s)\n\n%s via [IG](%s)",$picture->caption->text,$picture->images->standard_resolution->url,$picture->caption->text,$picture->link);
        $diasp->post($config['diaspora']['aspect'],$markdown);
        echo "Successfully posted " . $picture->code . "\n";
        $posted[] = $picture->code;
    }
    file_put_contents('posted',serialize($posted));
}
