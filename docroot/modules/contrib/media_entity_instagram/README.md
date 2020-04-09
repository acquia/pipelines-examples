## About Media entity

Media entity provides a 'base' entity for a media element. This is a very basic
entity which can reference to all kinds of media-objects (local files, YouTube
videos, tweets, CDN-files, ...). This entity only provides a relation between
Drupal (because it is an entity) and the resource. You can reference to this
entity within any other Drupal entity.

## About Media entity Instagram

This module provides Instagram integration for Media entity (i.e. media type provider
plugin).

### Without Instagram API
If you need just to embembed instagrams you can use this module without using Instagram's API. That will give you access to the shortcode field available from the url/embed code.

You will need to:

- Create a Media bundle with the type provider "Instagram".
- On that bundle create a field for the Instagram url/source (this should be a plain text or link field).
- Return to the bundle configuration and set "Field with source information" to use that field.

**IMPORTANT:** beware that there is limit on the number of request that can be made for free. [Read more](http://instagram.com/developer/endpoints/)


### With Instagram API
If you need to get other fields, you will need to use Instagram's API. To get this working follow the steps below:

- Download and enable [composer_manager](https://www.drupal.org/project/composer_manager). Also make sure you have [drush](https://github.com/drush-ops/drush) installed.
- Run the following commands from within your Drupal root directory to download the [library](https://github.com/galen/PHP-Instagram-API) that will handle the communication:

```
  // Rebuild the composer.json file with updated dependencies.
  $ drush composer-json-rebuild

  // Install the required packages.
  $ drush composer-manager install
```
- Create a instagram app on the instagram [developer site](http://instagram.com/developer/register/)
- Enable read access for your instagram app
- Grab your client ID from the instagram developer site
- In your Instagram bundle configuration set "Whether to use Instagram api to fetch instagrams or not" to "Yes"" and paste in the "Client ID"

**NOTE:** We are currently using a patched version of the library with the ability to get the media by shortcode. This is the pull request for it: https://github.com/galen/PHP-Instagram-API/pull/46/files

### Storing field values
If you want to store the fields that are retrieved from Instagram you should create appropriate fields on the created media bundle (id) and map this to the fields provided by Instagram.php.

**NOTE:** At the moment there is no GUI for that, so the only method of doing that for now is via CMI.

This would be an example of that (the field_map section):

```
langcode: en
status: true
dependencies:
  module:
    - media_entity_instagram
id: instagram
label: Instagram
description: 'Instagram photo/video to be used with content.'
type: instagram
type_configuration:
  source_field: link
  use_instagram_api: '1'
  client_id: YOUR_CLIENT_ID
field_map:
  id: instagram_id
  type: instagram_type
  thumbnail: instagram_thumbnail
  username: instagram_username
  caption: instagram_caption
  tags: instagram_tags
```

Project page: http://drupal.org/project/media_entity_instagram

Maintainers:
 - Janez Urevc (@slashrsm) drupal.org/user/744628
 - Malina Randrianavony (@designesse) www.drupal.org/user/854012

IRC channel: #drupal-media
