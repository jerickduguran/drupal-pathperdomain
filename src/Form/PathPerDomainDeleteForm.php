<?php

namespace Drupal\pathperdomain\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a pathperdomain entity.
 *
 * @ingroup pathperdomain
 */
class PathPerDomainDeleteForm extends ContentEntityDeleteForm {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete entity %id?', array('%id' => $this->entity->id()));
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the domain path list.
   */
  public function getCancelUrl() {
    return new Url('entity.pathperdomain.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * Delete the entity and log the event. logger() replaces the watchdog.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $this->logger('pathperdomain')->notice('deleted %id.',
      [
        '%id' => $this->entity->id(),
      ]);
    // Redirect to domain path list after delete.
    $form_state->setRedirect('entity.pathperdomain.collection');
  }

}
