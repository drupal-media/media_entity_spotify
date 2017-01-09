<?php

/**
 * @file
 * Theme functions for the media_entity_spotify module.
 */
use Drupal\Core\Url;

/**
 * Prepares variables for media_entity_spotify templates.
 *
 * Default template: media-spotify-embed.html.twig.
 */
function template_preprocess_media_spotify_embed(&$variables) {
  // Build the embed url.
  $uri = 'https://embed.spotify.com/?uri=' . $variables['uri'];

  // Build the query.
  $query = [
    'theme' => $variables['player_theme'],
    'view' => $variables['view']
  ];

  $url = Url::fromUri($uri, ['query' => $query]);
  $variables['url'] = $url->toString();
}
