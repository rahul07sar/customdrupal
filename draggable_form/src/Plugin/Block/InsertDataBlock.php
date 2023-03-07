<?php

/**
 * @File
 * Contains Drupal\draggable_form\Plugin\Block\InsertDataBlock.
 */

namespace Drupal\draggable_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an Insert form inside a block.
 * 
 * @Block(
 *    id = "insert_data_block",
 *    admin_label = @Translation("Insert Data Block")
 * )
 */
class InsertDataBlock extends BlockBase {
    
    /**
     * {@inheritdoc}
     */
    public function build () {

        $form = \Drupal::formbuilder()-> getForm('Drupal\draggable_form\Form\InsertDataForm');
        return $form;
    }
}


