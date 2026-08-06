<?php

namespace Drupal\webshare\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webshare\PlatformManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for deleting social media platforms.
 */
class PlatformDeleteForm extends ConfirmFormBase {

  /**
   * The platform manager.
   *
   * @var \Drupal\webshare\PlatformManager
   */
  protected $platformManager;

  /**
   * The platform to delete.
   *
   * @var object
   */
  protected $platform;

  /**
   * Constructs a new PlatformDeleteForm object.
   *
   * @param \Drupal\webshare\PlatformManager $platform_manager
   *   The platform manager.
   */
  public function __construct(PlatformManager $platform_manager) {
    $this->platformManager = $platform_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('webshare.platform_manager')
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
      $this->platform = $this->platformManager->loadPlatform($platform_id);
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
    // Deletes through the same path as the deleteSocialPlatform config
    // action, cache invalidation included.
    $this->platformManager->deletePlatform($this->platform->platform_id);

    $this->messenger()->addMessage($this->t(
        'Platform %name has been deleted.',
        ['%name' => $this->platform->name]
    ));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
