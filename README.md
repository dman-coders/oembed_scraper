# oEmbed scraper

An oembed endpoint that just does its best to give you something useful from
any URL.

It's an aggregator, in a similar vein to

* http://embed.ly/
* http://iframely.com/

But it's a small webservice you can run locally using your own resources.

The scraper endpoint here is  not much more than a layer in front of

* https://github.com/oscarotero/Embed

which is the library that does the semantic scraping and resolves both
opengraph and Dublin Core metadata into common fields.

This endpoint then regurgitates that info in oEmbed JSON format for you.

URLS that are not identified as being a recognised type (photo or video)
will be returned as type = 'link'

## Configuration

This requires no configuration out of the box.

To work, the hosting server must of course have outgoing access to the internet.

### Security restrictions: allowed_clients

As a security measure, when distributed,
_this tool accepts requests from its own host only._
This is to prevent accidentally flooding or security issues.

You should edit the whilelist of allowed_clients if you need it to be more
 publically accessible. This must be a list of IPs, not domain names.
 If your hosting arrangements make it difficult for your server to read itself,
 this could be a problem.

It is probably easier to remove this restriction altogether,
 (set allowed_clients to null)
 and then control access to this tool using standard webserver rules,
 such as htaccess restrictions.

### Security restrictions: allowed_targets

There is currently no restriction on the external sites that this utility may
make requests to.

## Usage

### As an endpoint - for consumers

An oEmbed provider is usually expected to be listed as a configuration option
 for oEmbed consumer utilities,
 such as [Media: oEmbed](https://www.drupal.org/project/media_oembed)

You would find a way to "Add new provider", and then enter the URL to this
 utility as its endpoint.
 It should be possible to use this as a "catch-all" or wildcard provider,
 as it is *very* broad about what it accepts.

#### Walkthrough for Drupal Media integration

First, install this script on the same server you want to be running Drupal on,
and ensure it's responding OK.

Next, on your Drupal7 site,
* Download and enable a bunch of modules

      drush en media media_gallery media_oembed

* Add a new File type (`/admin/structure/file-types/add`), called '**Link**'
* Set its Mimetype to `text/oembed`. This will mean that content retrieved
  from the oembed scraper will have a pseudo-media type to be associated with.
* Configure this scraper as a new oEmbed provider at
  `/admin/config/media/media-oembed/add`
* Call it **oEmbed scraper**
* Set its "Endpoint" as _the URL for this script on your server_
* Set its "Schemes" to include "*".
* Due to issue https://www.drupal.org/node/2700841, you should also add a few hundred characters of dummy text to the "Schemes" texfield!
  This will make this catch-all pattern only happen last :-/ .
  Alternatively, you can just disable all the other schemes.
  This oEmbed scraper should try to do just as good a job, YMMV.

At this point, you should be able to add a new media file from the 'Web' tab
of file management `/file/add/web`

Paste in an URL from a trustworthy source
(a website that was built with semantics).
You should at least end up with a 'Link' file entity with a correct title
 scraped from the web. In other cases, you should hope for a YouTube video
 or something.


### Directly - for implimentors

This attempts to implement the minimal parts of [the oEmbed specification](http://oembed.com/#section2)
Coverage is by no means complete.

Make a GET request to URL that you have made oembed_scraper.php available at.
Provide the url parameter in the GET string.

eg:

    http://localhost/oembed_scraper/oembed_scraper.php?url=https://www.beehive.govt.nz/release/appointment-chief-parliamentary-counsel

This will return JSON like:

    {
      "type": "link",
      "url": "http:\/\/www.beehive.govt.nz\/release\/appointment-chief-parliamentary-counsel",
      "title": "Appointment of Chief Parliamentary Counsel",
      "description": "Prime Minister John Key today announced the appointment of Fiona Leonard as Chief Parliamentary Counsel for a term of five years.",
      "author_name": null,
      "author_url": null,
      "html": "<div class='oembed_scraper'><h3>Appointment of Chief Parliamentary Counsel<\/h3><div>Prime Minister John Key today announced the appointment of Fiona Leonard as Chief Parliamentary Counsel for a term of five years.<\/div><\/div>",
      "image": "http:\/\/beehive.govt.nz\/sites\/all\/themes\/bh\/img\/coat-of-arms-colour.png",
      "thumbnail_width": 291,
      "thumbnail_height": 294,
      "provider_name": "The Beehive",
      "provider_url": "https:\/\/govt.nz",
      "provider_icon": "https:\/\/www.beehive.govt.nz\/sites\/all\/files\/bh_favicon.ico",
      "width": null,
      "height": null,
      "thumbnail_url": null,
      "version": "1.0"
    }

URLEncoding the provided URL is recommended, but often optional.

Additional parameters from [the oEmbed specification](http://oembed.com/#section2)
include
* maxwidth int
* maxheight int
* format [json|xml|text]

format=text is not part of the spec, and is provided for debug purposes.
format=xml is currently unimplimented.

## Dependencies

The dependencies are pulled in via composer.

Run

    composer update

upon first install.