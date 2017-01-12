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

  // Spotify has only two recommended embedded player: large (height: 300px) and compact (height: 80px;).
  // Tracks in list mode always display with height 80px.
  if ($variables['view'] == 'list' && preg_match('/^spotify\:(track)/', $variables['uri'])) {
    $variables['height'] = '80px';
  }

  // Add url to template as string.
  $variables['url'] = $url->toString();
}
