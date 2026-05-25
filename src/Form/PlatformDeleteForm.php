<?php

namespace Drupal\webshare\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting social media platforms.
 */
class PlatformDeleteForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The platform to delete.
   *
   * @var object
   */
  protected $platform;

  /**
   * Constructs a new PlatformDeleteForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webshare_platform_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $platform_id = NULL) {
    if ($platform_id) {
      $this->platform = $this->database
        ->select('webshare_platforms', 'wp')
        ->fields('wp')
        ->condition('platform_id', $platform_id)
        ->execute()
        ->fetchObject();
    }

    if (!$this->platform) {
      $this->messenger()->addError($this->t('Platform not found.'));
      return $this->redirect('webshare.config_form');
    }

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t(
        'Are you sure you want to delete the %name platform?',
        ['%name' => $this->platform->name]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone. The platform will be permanently removed from your sharing options.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('webshare.config_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete Platform');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->database->delete('webshare_platforms')
      ->condition('platform_id', $this->platform->platform_id)
      ->execute();

    $this->messenger()->addMessage($this->t(
        'Platform %name has been deleted.',
        ['%name' => $this->platform->name]
    ));

    // Refresh every cached rendering of the share rail (anonymous page cache
    // included) now that a platform has been removed.
    \Drupal\Core\Cache\Cache::invalidateTags(['webshare_platforms']);

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
