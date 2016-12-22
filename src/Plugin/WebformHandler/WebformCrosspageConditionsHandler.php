<?php

namespace Drupal\webform_crosspage_conditions\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Fix conditions from other pages if present on current page.
 *
 * @WebformHandler(
 *   id = "multipage_conditional_fields",
 *   label = @Translation("Multistep conditions"),
 *   category = @Translation("Conditional Fields"),
 *   description = @Translation("Fixes js fields states for values from other page"),
 * )
 */
class WebformCrosspageConditionsHandler extends WebformHandlerBase {

  /**
   * Implements alterForm() method for fixing states from another pages.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // get current page.
    $current_page = $form['progress']['#current_page'];
    // get form elements.
    $elements = $webform_submission->getWebform()->getElementsDecoded();
    $current_page_elements = array();
    if (!empty($elements[$current_page])) {
      // get flattened the elements only from current page.
      $this->getElementsForCurrentPageFlattened($elements[$current_page], $current_page_elements);
      // fix the states of the elements
      if (!empty($form['elements'][$current_page])) {
        $this->fixStates($form['elements'][$current_page], $webform_submission, $current_page_elements);
      }
    }
  }

  /**
   * Get flattened form element for current page.
   *
   * @param $elements
   * @param array $current_page_elements
   */
  protected function getElementsForCurrentPageFlattened($elements, array &$current_page_elements) {
    foreach ($elements as $key => $element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }
      $current_page_elements[$key] = WebformElementHelper::getProperties($element);
      $this->getElementsForCurrentPageFlattened($element, $current_page_elements);
    }
  }

  /**
   * Recursively goes through all the elements to find the states and fix.
   *
   * @param array $elements
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   * @param $page_elements
   */
  protected function fixStates(array &$elements, WebformSubmissionInterface $webform_submission, $page_elements) {
    foreach ($elements as $key => $element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }
      if (!empty($element['#states'])) {
        $this->convertStates($elements[$key], $webform_submission, $page_elements);
      }
      $this->fixStates($elements[$key], $webform_submission, $page_elements);
    }
  }

  /**
   * Finds all the states and try to fix the ones from another page.
   * It doesn't change the ones that exists on the page.
   *
   * @param array $element
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   * @param $page_elements
   */
  protected function convertStates(array &$element, WebformSubmissionInterface $webform_submission, $page_elements) {
    $states = $element['#states'];
    // loop through the states.
    foreach ($states as $state => $conditions) {
      $applyCondition = [];
      // loop through all the conditions for the state.
      foreach ($conditions as $condition => $value) {
        if (!is_numeric($condition)) {
          // there is only one condition.
          $conditionalField = $condition;
          $conditionalFieldValue = $value;
        } elseif (is_array($value) && !isset($value['value'])) {
          // the condition is complex, we need to check all.
          $fieldNames = array_keys($value);
          $conditionalField = $fieldNames[0];
          $conditionalFieldValue = $value[$fieldNames[0]];
        } else {
          // this is the operator 'or' or 'and'.
          $applyCondition[] = $value;
          continue;
        }
        $matches = [];
        // find the name of the dependent field.
        preg_match('/.*name="(.*)"/', $conditionalField, $matches);
        if (!empty($matches[1])) {
          $field_name = $matches[1];
          // check if this field is on this page.
          if (!in_array($field_name, array_keys($page_elements))) {
            // check the data for the dependent field.
            $dependentValue = $webform_submission->getData($field_name);
            // for now only 2 conditions are supported "value", "checked" and "unchecked"
            if (isset($conditionalFieldValue['value'])) {
              $checkValue = $conditionalFieldValue['value'];
            } elseif ($conditionalFieldValue['checked']) {
              $checkValue = $conditionalFieldValue['checked'];
            } elseif ($conditionalFieldValue['unchecked']) {
              $checkValue = !$conditionalFieldValue['unchecked'];
            }
            // check the condition.
            if ($checkValue == $dependentValue) {
              $applyCondition[] = 1;
            } else {
              $applyCondition[] = 0;
            }
          }
        }
      }
      // check if we need to apply the condition.
      if (!empty($applyCondition) && $this->calculateConditions($applyCondition)) {
        $this->applyState($element, $state);
      }
    }
  }

  /**
   * Calculates the result of the conditions on state.
   *
   * @param $results
   * @return mixed
   */
  private function calculateConditions($results) {
    if (count($results) == 1) {
      return array_shift($results);
    }
    $eval = implode(" ", $results);
	// find better way to evaluate several conditions
    return eval("return $eval;");
  }

  /**
   * Apply the state.
   *
   * @param array $element
   * @param $state
   */
  protected function applyState(array &$element, $state) {
    switch ($state) {
      case 'invisible':
        $element['#attributes']['style'][] = 'display: none;';
        $this->setRecursivelyRequired($element, FALSE);
        break;
      case 'visible':
        $element['#attributes']['style'][] = 'display: block;';
        break;
      case 'required':
        $element['#required'] = TRUE;
        break;
      case 'optional':
        $element['#required'] = FALSE;
        break;
      case 'disabled':
        $element['##attributes']['disabled'] = TRUE;
        break;
    }
  }

  /**
   * Set required recursively.
   *
   * @param $elements
   * @param bool $required
   * @internal param $element
   */
  private function setRecursivelyRequired($elements, $required = FALSE) {
    foreach ($elements as $key => $element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }
      $element['#required'] = $required;
      $this->setRecursivelyRequired($elements[$key], $required);
    }
  }

}
