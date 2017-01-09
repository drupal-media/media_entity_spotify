<?php

namespace Drupal\media_entity_spotify\Tests;

use Drupal\media_entity\Tests\MediaTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for Spotify embed formatter.
 *
 * @group media_entity_spotify
 */
class SpotifyEmbedFormatterTest extends WebTestBase {

  use MediaTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'media_entity_spotify',
    'media_entity',
    'node',
    'field_ui',
    'views_ui',
    'block',
    'link',
  );

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $bundle;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    parent::setUp();

    // Create a test spotify media bundle.
    $bundle['bundle'] = 'spotify';
    $this->bundle = $this->drupalCreateMediaBundle($bundle, 'spotify');

    // Create an admin user with permissions to administer and create media.
    $this->adminUser = $this->drupalCreateUser([
      'administer media',
      'administer media bundles',
      'administer media fields',
      'administer media form display',
      'administer media display',
      // Media entity permissions.
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
      // Other permissions.
      'administer views',
    ]);

    // Login the user.
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests adding and editing a spotify embed formatter.
   */
  public function testSpotifyEmbedFormatter() {
    // Assert that the media bundle has the expected values before proceeding.
    $this->drupalGet('admin/structure/media/manage/' . $this->bundle->id());
    $this->assertFieldByName('label', $this->bundle->label());
    $this->assertFieldByName('type', 'spotify');

    // Add a Url field to the bundle.
    $this->drupalGet('admin/structure/media/manage/' . $this->bundle->id() . '/fields/add-field');
    $config = [
      'new_storage_type' => 'link',
      'label' => 'Url',
      'field_name' => 'media_url',
    ];
    $this->drupalPostForm(NULL, $config, t('Save and continue'));

    // Check that the settings has been saved.
    $this->assertText('These settings apply to the ' . $config['label'] . ' field everywhere it is used.');

    // Set the field instance settings.
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->assertText('Updated field ' . $config['label'] . ' field settings.');

    $edit = [
      'settings[link_type]' => 16,
      'settings[title]' => 0,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));

    // Check if the field has been saved successfully.
    $this->assertText('Saved ' . $config['label'] . ' configuration.');
    $this->assertText('field_media_url');

    // Set the field_media_url format to spotify_embed.
    $this->drupalGet('admin/structure/media/manage/' . $this->bundle->id() . '/display');
    $edit = [
      'fields[field_media_url][label]' => 'above',
      'fields[field_media_url][type]' => 'spotify_embed',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Your settings have been saved.');

    // Set the source_url_field.
    $this->drupalGet('admin/structure/media/manage/' . $this->bundle->id());
    $edit = [
      'type_configuration[spotify][source_url_field]' => 'field_media_url'
    ];
    $this->drupalPostForm(NULL, $edit, t('Save media bundle'));

    // Test Spotify urls.
    foreach ($this->getSpotifyUrls() as $type => $url) {
      // Create a spotify media entity.
      $this->drupalGet('media/add/' . $this->bundle->id());

      // Asset that the field_media_url is found.
      $this->assertFieldByName('field_media_url[0][uri]');

      $edit = [
        'name[0][value]' => $type,
        'field_media_url[0][uri]' => $url,
      ];
      $this->drupalPostForm(NULL, $edit, t('Save and publish'));

      // Asset that spotify entity has been created.
      $this->assertText('Url');

      // Assert that the formatter exists on this page.
      $this->assertFieldByXPath('//iframe');
    }
  }

  /**
   * Returns an array of spotify urls.
   *
   * @return array
   */
  protected function getSpotifyUrls() {
    return [
      'track' => 'https://open.spotify.com/track/298gs9ATwr2rD9tGYJKlQR',
      'album' => 'https://play.spotify.com/album/2ODvWsOgouMbaA5xf0RkJe',
      'playlist' => 'https://open.spotify.com/user/spotify/playlist/3rgsDhGHZxZ9sB9DQWQfuf',
    ];
  }
}
