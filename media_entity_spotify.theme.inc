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
  $url = Url::fromUri('https://embed.spotify.com/', ['query' => [
    'uri' => $variables['uri'],
    'theme' => $variables['player_theme'],
    'view' => $variables['view'],
  ]]);

  // Add url to template as string.
  $variables['url'] = $url->toString();
}
