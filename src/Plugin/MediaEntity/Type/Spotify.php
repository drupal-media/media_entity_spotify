<?php

namespace Drupal\media_entity_spotify\Plugin\MediaEntity\Type;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media_entity\Annotation\MediaType;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for Spotify.
 *
 * @MediaType(
 *   id = "spotify",
 *   label = @Translation("Spotify"),
 *   description = @Translation("Provides business logic and metadata for Spotify.")
 * )
 */
class Spotify extends MediaTypeBase {

  /**
   * @var array
   */
  protected $spotify;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $configFactory, ClientInterface $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $configFactory->get('media_entity.settings'));
    $this->configFactory = $configFactory;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_url_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $options = ['' => $this->t('Select')];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'string_long', 'link'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $disabled = !count($options);
    if ($disabled) {
      $options = ['' => $this->t('Add fields to the media bundle')];
    }

    $form['source_url_field'] = [
      '#type' => 'select',
      '#title' => t('Spotify URL source field'),
      '#description' => t('Select the field on the media entity that stores Spotify URL.'),
      '#default_value' => isset($this->configuration['source_url_field']) ? $this->configuration['source_url_field'] : '',
      '#options' => $options,
      '#disabled' => $disabled,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'uri' => $this->t('The uri'),
      'html' => $this->t('HTML embed code'),
      'thumbnail_uri' => t('URI of the thumbnail'),
    ];
  }

  /**
   * @inheritDoc
   */
  public function getField(MediaInterface $media, $name) {
    if (($url = $this->getMediaUrl($media)) && ($data = $this->oEmbed($url))) {
      switch ($name) {
        case 'html':
          return $data['html'];
        case 'thumbnail_uri':
          if (isset($data['thumbnail_url'])) {
            $destination = $this->configFactory->get('media_entity_spotify.settings')->get('thumbnail_destination');
            $local_uri = $destination . '/' . pathinfo($data['thumbnail_url'], PATHINFO_BASENAME);

            // Save the file if it does not exist.
            if (!file_exists($local_uri)) {
              file_prepare_directory($destination, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

              $image = file_get_contents($data['thumbnail_url']);
              file_unmanaged_save_data($image, $local_uri, FILE_EXISTS_REPLACE);

              return $local_uri;
            }
          }
          return FALSE;
        case 'uri':
          // Test for track.
          preg_match('/^https?:\/\/(?:open|play)\.spotify\.com\/track\/([\w\d]+)$/i', $url, $matches);
          if (count($matches)) {
            return 'spotify:track:' . $matches[1];
          }

          // Test for playlist
          preg_match('/^https?:\/\/(?:open|play)\.spotify\.com\/user\/([\w\d]+)\/playlist\/([\w\d]+)$/i', $url, $matches);
          if (count($matches)) {
            return 'spotify:user:' . $matches[1] . ':playlist:' . $matches[2];
          }

          // Test for album.
          preg_match('/^https?:\/\/(?:open|play)\.spotify\.com\/album\/([\w\d]+)$/i', $url, $matches);
          if (count($matches)) {
            return 'spotify:album:' . $matches[1];
          }

          return FALSE;
      }
    }

    return FALSE;
  }

  /**
   * Returns the url from the source_url_field.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *  The media entity.
   *
   * @return string|bool
   *  The track if from the source_url_field if found. False otherwise.
   */
  protected function getMediaUrl(MediaInterface $media) {
    if (isset($this->configuration['source_url_field'])) {
      $source_url_field = $this->configuration['source_url_field'];
      if ($media->hasField($source_url_field)) {
        if (!empty($media->{$source_url_field}->first())) {
          $property_name = $media->{$source_url_field}->first()
            ->mainPropertyName();
          return $media->{$source_url_field}->{$property_name};
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if ($thumbnail_image = $this->getField($media, 'thumbnail_uri')) {
      return $thumbnail_image;
    }

    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/spotify.png';
  }

  /**
   * Returns oembed data for a Spotify url.
   *
   * @param string $url
   *   The Spotify Url.
   *
   * @return array
   *  An array of oembed data.
   */
  protected function oEmbed($url) {
    $this->spotify = &drupal_static(__FUNCTION__);

    if (!isset($this->spotify)) {
      $url = 'https://embed.spotify.com/oembed/?url=' . $url;
      $response = $this->httpClient->get($url);
      $this->spotify = json_decode((string) $response->getBody(), TRUE);
    }

    return $this->spotify;
  }
}
