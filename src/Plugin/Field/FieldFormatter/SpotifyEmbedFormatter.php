<?php

namespace Drupal\media_entity_spotify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\media_entity\MediaTypeInterface;
use Drupal\media_entity_spotify\Plugin\MediaEntity\Type\Spotify;

/**
 * Plugin implementation of the 'spotify_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "spotify_embed",
 *   label = @Translation("Spotify embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class SpotifyEmbedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'theme' => 'dark',
        'view' => 'list',
        'width' => '300px',
        'height' => '380px',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['theme'] = [
      '#title' => $this->t('Theme'),
      '#type' => 'select',
      '#options' => [
        'dark' => $this->t('Dark'),
        'white' => $this->t('Light'),
      ],
      '#default_value' => $this->getSetting('theme'),
      '#description' => $this->t('The theme for the embedded player.'),
    ];

    $elements['view'] = [
      '#title' => $this->t('View'),
      '#type' => 'select',
      '#options' => [
        'list' => $this->t('List'),
        'coverart' => $this->t('Cover Art'),
      ],
      '#default_value' => $this->getSetting('view'),
      '#description' => $this->t('The view for the embedded player.'),
    ];

    $elements['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Width of embedded player.'),
    ];

    $elements['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $this->getSetting('height'),
      '#min' => 1,
      '#required' => TRUE,
      '#description' => $this->t('Height of embedded player.'),
    ];

    $elements['size_help'] = [
      '#markup' => $this->t('Suggested sizes for players: <strong>Large:</strong> 300x380px, <strong>Compact:</strong> 300x80px.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [
      $this->t('Theme: @theme', [
        '@theme' => $this->getSetting('theme'),
      ]),
      $this->t('View: @view', [
        '@view' => $this->getSetting('view'),
      ]),
      $this->t('Width: @width', [
        '@width' => $this->getSetting('width'),
      ]),
      $this->t('Height: @height', [
        '@height' => $this->getSetting('height'),
      ]),
    ];

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\media_entity\MediaInterface $media_entity */
    $media_entity = $items->getEntity();

    $element = [];
    if (($type = $media_entity->getType()) && $type instanceof Spotify) {
      /** @var MediaTypeInterface $item */
      foreach ($items as $delta => $item) {
        if ($uri = $type->getField($media_entity, 'uri')) {
          $element[$delta] = [
            '#theme' => 'media_spotify_embed',
            '#uri' => $uri,
            '#width' => $this->getSetting('width'),
            '#height' => $this->getSetting('height'),
            '#player_theme' => $this->getSetting('theme'),
            '#view' => $this->getSetting('view'),
          ];
        }
      }
    }

    return $element;
  }
}
