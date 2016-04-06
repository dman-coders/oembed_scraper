<?php
/**
 * @file
 * Endpoint provider that returns oEmbed JSON from arbitrary URLs.
 *
 * This is not much more than a layer in front of
 * https://github.com/oscarotero/Embed
 * which does the semantic scraping and resolves both opengraph and
 * Dublin Core metadata into common fields.
 *
 * Plus a hundred other resolvers, handlers and providers.
 *
 * I regurgitate these fields in OpenGraph format.
 */

/**
 * Call this page directly with parameters per the opengraph spec:
 * Required:
 *   'url'
 * Optional:
 *   'maxwidth'
 *   'maxheight'
 *   'format'
 *
 * To prevent overloading and all manner of things that could go wrong,
 * we accept incoming requests FROM OUR OWN DOMAIN ONLY.
 * This provides a tiny oEmbed provider, running locally, that
 * provides info about remote resources.
 *
 * Plugging in to the oEmbed ecosystem by just providing another endpoint
 * that we consume ourself is the most-decoupled, least-code way forward today.
 *
 * Does require that the hosting server can see itself.
 */

// Configurable
///////////////

/**
 * Clients IPs that are allowed to make requests of this service.
 *
 * Set this to empty to remove all restrictions, though you should
 * replace this security layer with your own access controls. See README.
 *
 * If your server talks to itself using a different IP, add it here.
 */
$allowed_clients = array(
  $_SERVER['REMOTE_ADDR'],
);



// Begin preparation
////////////////////

$params = $_REQUEST;

// Check incoming client is allowed
if (!empty($allowed_clients)) {
  $client = $_SERVER['REMOTE_ADDR'];
  if (! in_array($client, $allowed_clients)) {
    http_response_code(403);
    print "Requests to this service from $client are currently disallowed";
    exit();
  }
}

// Validate request.
if (empty($params['url'])) {
  // Bad request, required argument not given.
  http_response_code(400);
  print 'No URL provided. You should pass the ?url= parameter in this request. Hint, you can also set <a href="?format=text">?format=text</a> for debugging.';
  exit();
}
$url = $params['url'];
$url_parts = parse_url($url);
// More validation needed? Sanitize expected input at least.
$maxwidth = @(int)$params['maxwidth'];
$maxheight = @(int)$params['maxheight'];
if (!empty($params['format']) && in_array($params['format'], array('json', 'xml', 'text'))) {
  $format = $params['format'];
}
else {
  $format = 'json';
}

$defaults = array(
  "provider_name" => "oembed scraper",
  "provider_url" => "http://standards.net.nz/oembed_scraper",
  'width' => NULL,
  'height' => NULL,
  'thumbnail_url' => NULL,
  'thumbnail_width' => NULL,
  'thumbnail_height' => NULL,
  'version' => '1.0',
);

// Begin request.
/////////////////
// @see https://github.com/oscarotero/Embed
include('vendor/autoload.php');
use Embed\Embed;

/**
 * @var \Embed\Adapters\AdapterInterface $info
 */
$info = Embed::create($url);

// Start building the oEmbed data struct.
$oembed = array(
  "type" => "link",
  "url" => $info->url,
  "title" => $info->title,
  "description" => $info->description,
  "author_name" => $info->authorName,
  "author_url" => $info->authorUrl,
);

// A successful semantic scrape from elsewhere MAY provide 'code' field
// containing markup to use. EG YouTube.
if (!empty("" . $info->code)) {
  $oembed['html'] = $info->code;
}
else {
  // Otherwise,
  // Produce some adequate HTML, if none is provided.
  $teaser = "<div class='oembed_scraper'>";
  $teaser .= "<h3>" . $oembed['title'] . '</h3>';
  $teaser .= "<div>" . $oembed['description'] . '</div>';
  $teaser .= "</div>";
  $oembed['html'] = $teaser;
}

$fields = array(
  'image' => 'image',
  'imageWidth' => 'thumbnail_width',
  'imageHeight' => 'thumbnail_height',
  'providerName' => 'provider_name',
  'providerUrl' => 'provider_url',
  'providerIcon' => 'provider_icon',
);
foreach ($fields as $source => $destination) {
  // empty() on an attribute failed here !? Wacky. Must be a magic getter or something.
  if (!empty("" . $info->{$source})) {
    $oembed[$destination] = $info->{$source};
  }
}


$oembed += $defaults;

switch($format) {
  case 'json' :
    header('Content-type: application/json');
    echo json_encode($oembed);
    exit;
  case 'text' :
    print '<pre>';
    print_r($oembed);
    print '</pre>';
    print '<hr/>';
    # print '<pre>';
    # Alternate ways to extract markup for oembeds.
    # print_r($info->getCode());
    # print_r($info->getProvider('oembed')->bag->get('html'));
    exit;
  case 'xml' :
    print '<pre>';
    print "XML format TODO";
    // @see array2XML or something?
    exit;
}

