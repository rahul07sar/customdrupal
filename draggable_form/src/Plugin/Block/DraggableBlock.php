<?php

/**
 * @File
 * Contains Drupal\draggable_form\Plugin\Block\DraggableBlock.
 */

namespace Drupal\draggable_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Draggable form inside a block.
 * 
 * @Block(
 *    id = "draggable_block",
 *    admin_label = @Translation("Draggable Block")
 * )
 */
class DraggableBlock extends BlockBase {
    
    /**
     * {@inheritdoc}
     */
    public function build () {

        $form = \Drupal::formbuilder()-> getForm('Drupal\draggable_form\Form\DraggableForm');
        return $form;
    }
}


