<?php

namespace Drupal\nesar_recipe_product\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
/**
 * Provides a 'Related Product' block.
 *
 * @Block(
 *   id = "nesar_recipe_product",
 *   admin_label = @Translation("Nesar Recipe Product"),
 *   category = @Translation("Nesar Recipe Product"),
 *   module = "nesar_recipe_product",
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class NesarRecipeProductBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * File url generator object.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;


  /**
   * Constructs a new NesarRelatedRecipeBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   File url generator object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get node from contextual values.
    $node = $this->getContextValue('node');
    $product_details = [];
    if (!empty($node)) {
      $product = $node->field_srh_recipe_product->referencedEntities();
      $product = reset($product);
      $image = $product->get('field_product_type_image_s')->first();
      $img_uri = get_file_uri_path($image->get('target_id')->getString());

      $product_details = [
        'url' => $product->toUrl()->toString(),
        'image_url' => ImageStyle::load('home_stage_375_657')->buildUrl($img_uri),
        'image_alt' => !empty($image->getValue()['alt']) ? $image->getValue()['alt'] : t('Product image'),
      ];
    }

    // Get recipe product final array.
    return [
      '#theme' => 'nesar_recipe_product_block',
      '#product' => $product_details,
      '#description' => $node->field_srh_recipe_product_des->value,
    ];
  }

}
