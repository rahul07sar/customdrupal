<?php

namespace Drupal\draggable_form\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * A draggable form.
 *
 * @ingroup draggable_form
 */
class DraggableForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $render;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container
      ->get('database'), $container
      ->get('renderer'));
  }

  /**
   * Construct the draggable form.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Render\RendererInterface $render
   *   The renderer.
   */
  public function __construct(Connection $database, RendererInterface $render) {
    $this->database = $database;
    $this->render = $render;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'draggable_form';
  }

  /**
   * Build the parent-child form.
   *
   * @param array $form
   *   Render array representing from.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table-row'] = [
      '#type' => 'table',
      '#header' => [
        $this
          ->t('Name'),
        $this
          ->t('Description'),
        $this
          ->t('Weight'),
        $this
          ->t('Parent'),
      ],
      '#empty' => $this
        ->t('Sorry, There are no items to display!'),
   
      '#tabledrag' => [
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'row-pid',
          'source' => 'row-id',
          'hidden' => TRUE,
          /* hides the WEIGHT & PARENT tree columns below */
          'limit' => FALSE,
        ],
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'row-weight',
        ],
      ],
    ];

    $results = self::getData();
    foreach ($results as $row) {

      // TableDrag: Mark the table row as draggable.
      $form['table-row'][$row->id]['#attributes']['class'][] = 'draggable';

      // Indent item on load.
      if (isset($row->depth) && $row->depth > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $row->depth,
        ];
      }

      // Some table columns containing raw markup.
      $form['table-row'][$row->id]['name'] = [
        '#markup' => $row->name,
        '#prefix' => !empty($indentation) ? $this->render
          ->render($indentation) : '',
      ];
      $form['table-row'][$row->id]['description'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $row->description,
      ];

      // This is hidden from #tabledrag array (above).
      // TableDrag: Weight column element.
      $form['table-row'][$row->id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this
          ->t('Weight for ID @id', [
          '@id' => $row->id,
        ]),
        '#title_display' => 'invisible',
        '#default_value' => $row->weight,
        // Classifing the weight element for #tabledrag.
        '#attributes' => [
          'class' => [
            'row-weight',
          ],
        ],
      ];
      $form['table-row'][$row->id]['parent']['id'] = [
        '#parents' => [
          'table-row',
          $row->id,
          'id',
        ],
        '#type' => 'hidden',
        '#value' => $row->id,
        '#attributes' => [
          'class' => [
            'row-id',
          ],
        ],
      ];
      $form['table-row'][$row->id]['parent']['pid'] = [
        '#parents' => [
          'table-row',
          $row->id,
          'pid',
        ],
        '#type' => 'number',
        '#size' => 3,
        '#min' => 0,
        '#title' => $this
          ->t('Parent ID'),
        '#default_value' => $row->pid,
        '#attributes' => [
          'class' => [
            'row-pid',
          ],
        ],
      ];
    }
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Save All Changes'),
    ];
    return $form;
  }

  /**
   * Form submission handler for the 'Return to' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state
      ->setRedirect('draggable_form.description');
  }

  /**
   * Submit handler for the form.
   *
   * Updates the 'weight' column for each element in our table, taking into
   * account that item's new order after the drag and drop actions have been
   * performed.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $submissions = $form_state
      ->getValue('table-row');
    foreach ($submissions as $id => $item) {
      $this->database
        ->update('draggable_form')
        ->fields([
        'weight' => $item['weight'],
        'pid' => $item['pid'],
        'description' => $item['description'],
      ])
        ->condition('id', $id, '=')
        ->execute();
    }
  }

  /**
   * @return array
   *   An associative array storing our ordered tree structure.
   */
  public function getData() {

    // Get all 'root node' items (items with no parents), sorted by weight.
    $root_items = $this->database
      ->select('draggable_form', 't')
      ->fields('t')
      ->condition('pid', '0', '=')
      ->condition('id', 11, '<')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();

    // Initialize a variable to store our ordered tree structure.
    $tree = [];

    // Depth will be incremented in our getTree()
    // function for the first parent item, so we start it at -1.
    $depth = -1;

    // Loop through the root item, and add their trees to the array.
    foreach ($root_items as $root_item) {
      $this
        ->getTree($root_item, $tree, $depth);
    }
    return $tree;
  }

  /**
   * Recursively adds $item to $item_tree, ordered by parent/child/weight.
   *
   * @param mixed $item
   *   The item.
   * @param array $tree
   *   The item tree.
   * @param int $depth
   *   The depth of the item.
   */
  public function getTree($item, array &$tree = [], &$depth = 0) {

    // Increase our $depth value by one.
    $depth++;

    // Set the current tree 'depth' for this item, used to calculate
    // indentation.
    $item->depth = $depth;

    // Add the item to the tree.
    $tree[$item->id] = $item;

    // Retrieve each of the children belonging to this nested demo.
    $children = $this->database
      ->select('draggable_form', 't')
      ->fields('t')
      ->condition('pid', $item->id, '=')
      ->condition('id', 11, '<')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();
    foreach ($children as $child) {

      if (!in_array($child->id, array_keys($tree))) {
        
        $this
          ->getTree($child, $tree, $depth);
      }
    }

    $depth--;
  }
}
