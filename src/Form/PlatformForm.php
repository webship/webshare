<?php

namespace Drupal\webshare\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding/editing social media platforms.
 */
class PlatformForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new PlatformForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $database, FileSystemInterface $file_system, ModuleExtensionList $module_extension_list, TimeInterface $time) {
    $this->database = $database;
    $this->fileSystem = $file_system;
    $this->moduleExtensionList = $module_extension_list;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database'),
        $container->get('file_system'),
        $container->get('extension.list.module'),
        $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webshare_platform_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $platform_id = NULL) {
    $platform = NULL;
    if ($platform_id) {
      $platform = $this->database
        ->select('webshare_platforms', 'wp')
        ->fields('wp')
        ->condition('platform_id', $platform_id)
        ->execute()
        ->fetchObject();
    }

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Platform Name'),
      '#description' => $this->t('The display name for this platform.'),
      '#default_value' => $platform ? $platform->name : '',
      '#required' => TRUE,
    ];

    $form['platform_id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Platform ID'),
      '#description' => $this->t('A unique machine name for this platform.'),
      '#default_value' => $platform ? $platform->platform_id : '',
      '#disabled' => $platform ? TRUE : FALSE,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'platformExists'],
        'source' => ['name'],
      ],
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Platform Title'),
      '#description' => $this->t('Title shown on hover (tooltip).'),
      '#default_value' => $platform ? $platform->title : '',
      '#required' => TRUE,
    ];

    $form['url_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sharing URL Template'),
      '#description' => $this->t('URL template for sharing. Use [url] and [title] as placeholders. Leave empty for copy-to-clipboard functionality.'),
      '#default_value' => $platform ? $platform->url_template : '',
      '#maxlength' => 512,
    ];

    $form['icon'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Platform Icon'),
      '#description' => $this->t('Upload an icon for this platform. Any image size is supported. Supported formats: PNG, SVG, JPG.'),
      '#upload_location' => 'public://webshare/module_icons/',
      '#upload_validators' => [
        'FileExtension' => [
          'extensions' => 'png svg jpg jpeg',
        ],
      ],
    ];

    if ($platform && $platform->image) {
      $form['current_icon'] = [
        '#markup' => '<div><strong>' . $this->t('Current icon:') . '</strong><br>' .
        '<img src="/' . $platform->image . '" alt="' . $this->t($platform->name) . '" style="max-width: 32px; max-height: 32px;"></div>',
      ];
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Whether this platform should be available for sharing.'),
      '#default_value' => $platform ? $platform->enabled : 1,
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#description' => $this->t('Platforms with lower weights will appear first.'),
      '#default_value' => $platform ? $platform->weight : 0,
      '#delta' => 50,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $platform ? $this->t('Update Platform') : $this->t('Add Platform'),
      '#ajax' => [
        'callback' => '::ajaxSubmit',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::ajaxCancel',
      ],
      '#submit' => [],
    ];

    return $form;
  }

  /**
   * Check if platform exists.
   */
  public function platformExists($platform_id) {
    return $this->database
      ->select('webshare_platforms', 'wp')
      ->fields('wp', ['id'])
      ->condition('platform_id', $platform_id)
      ->countQuery()
      ->execute()
      ->fetchField() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    $platform_id = $values['platform_id'];

    // Check if this is a new platform and requires an icon
    $existing = $this->database
      ->select('webshare_platforms', 'wp')
      ->fields('wp')
      ->condition('platform_id', $platform_id)
      ->execute()
      ->fetchObject();

    if (!$existing && empty($values['icon'][0])) {
      $form_state->setError($form['icon'], $this->t('An icon is required for new platforms.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $platform_id = $values['platform_id'];

    // Handle file upload
    $image_path = '';
    if (!empty($values['icon'][0])) {
      try {
        $file = File::load($values['icon'][0]);
        if ($file) {
          $file->setPermanent();
          $file->save();
          $file_uri = $file->getFileUri();

          // Get the original filename and extension
          $filename = $file->getFilename();
          $module_path = $this->moduleExtensionList->getPath('webshare');
          $module_img_path = DRUPAL_ROOT . '/' . $module_path . '/img/' . $filename;

          // Copy file to module img folder
          $this->fileSystem->copy($file_uri, $module_img_path, FileExists::Replace);

          // Store relative path from module root
          $image_path = $module_path . '/img/' . $filename;
        }
      } catch (\Exception $e) {
        $form_state->setError($form['icon'], $this->t('Error processing uploaded file: @message', ['@message' => $e->getMessage()]));
        return;
      }
    }

    $existing = $this->database
      ->select('webshare_platforms', 'wp')
      ->fields('wp')
      ->condition('platform_id', $platform_id)
      ->execute()
      ->fetchObject();

    $time = $this->time->getRequestTime();

    if ($existing) {
      // Update existing platform
      $fields = [
        'name' => $values['name'],
        'title' => $values['title'],
        'url_template' => $values['url_template'] ?: '',
        'enabled' => (int) $values['enabled'],
        'weight' => (int) $values['weight'],
        'updated' => $time,
      ];

      if ($image_path) {
        $fields['image'] = $image_path;
      }

      $this->database->update('webshare_platforms')
        ->fields($fields)
        ->condition('platform_id', $platform_id)
        ->execute();

      $this->messenger()->addMessage($this->t('Platform %name has been updated.', ['%name' => $values['name']]));
    } else {
      // Insert new platform - validation already handled in validateForm()
      $this->database->insert('webshare_platforms')
        ->fields([
          'platform_id' => $platform_id,
          'name' => $values['name'],
          'title' => $values['title'],
          'url_template' => $values['url_template'] ?: '',
          'enabled' => (int) $values['enabled'],
          'image' => $image_path,
          'weight' => (int) $values['weight'],
          'is_custom' => 1,
          'created' => $time,
          'updated' => $time,
        ])
        ->execute();

      $this->messenger()->addMessage($this->t('Platform %name has been added.', ['%name' => $values['name']]));
    }
  }

  /**
   * Ajax submit callback.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#webshare-platform-form', $form));
    } else {
      $response->addCommand(new CloseDialogCommand());
      $response->addCommand(new RedirectCommand(Url::fromRoute('webshare.config_form')->toString()));
    }

    return $response;
  }

  /**
   * Ajax cancel callback.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }
}
