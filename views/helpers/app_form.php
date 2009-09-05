<?php
App::import('Helper', 'Form');

class AppFormHelper extends FormHelper {
	public $helpers = array('Html', 'Syrup.Wysiwyg');

	/**
	 * Will display all the fields passed in an array expects fieldName as an array key
	 * replaces generateFields
	 *
	 * @access public
	 * @param array $fields works well with Controller::generateFields() or on its own;
	 * @param array $blacklist a simple array of fields to skip
	 * @return output
	 */
	public function inputs($fields = null, $blacklist = null, $buttons = null) {
		if (empty($buttons) && !empty($blacklist)) {
			$buttons = array();
			foreach($blacklist as $field) {
				if (!array_key_exists($field, $fields)) {
					$buttons[] = $field;
					unset($blacklist[$field]);
				}
			}
		}
		$inputs = parent::inputs($fields, $blacklist);
		if (!empty($buttons)) {
			$fieldSet = preg_match('/^<fieldset/i', $inputs) > 0;
			$out = '';
			foreach($buttons as $button) {
				$out .= $this->label(null, '');
				$out .= $this->submit($button, array('div'=>false));
			}
			if ($fieldSet) {
				$inputs = preg_replace('/(<\/fieldset)/i', $out . '\\1', $inputs);
			} else {
				$inputs .= $out;
			}
		}
		return $inputs;
	}

	/**
	 * Generates a form input element complete with label and wrapper div
	 *
	 * Options - See each field type method for more information. Any options that are part of
	 * $attributes or $options for the different type methods can be included in $options for input().
	 *
	 * - 'type' - Force the type of widget you want. e.g. ```type => 'select'```
	 * - 'label' - control the label
	 * - 'div' - control the wrapping div element
	 * - 'options' - for widgets that take options e.g. radio, select
	 * - 'error' - control the error message that is produced
	 *
	 * @param string $fieldName This should be "Modelname.fieldname"
	 * @param array $options Each type of input takes different options.
	 * @return string Completed form widget
	 */
	public function input($fieldName, $options = array()) {
		$type = (!empty($options['type']) ? $options['type'] : null);
		if ($type == 'wysiwyg') {
			$options = array_merge(array(
				'class' => 'wysiwyg'
			), array_diff_key($options, array('textarea'=>true)));
		}

		if (!empty($options['help'])) {
			if (empty($options['after'])) {
				$options['after'] = '';
			}
			$options['after'] = '<small>' . $options['help'] . '</small>' . $options['after'];
			unset($options['help']);
		}

		$out = parent::input($fieldName, $options);

		if ($type == 'wysiwyg') {
			$out .= $this->Wysiwyg->editor($fieldName, array('includeField' => false));
		}

		return $out;
	}

	/**
	 * Returns a formatted error message for given FORM field, NULL if no errors.
	 *
	 * Options:
	 *
	 * - 'escape'  bool  Whether or not to html escape the contents of the error.
	 * - 'wrap'  mixed  Whether or not the error message should be wrapped in a div. If a
	 *   string, will be used as the HTML tag to use.
	 * - 'class'  string  The classname for the error message
	 *
	 * @param string $field  A field name, like "Modelname.fieldname"
	 * @param mixed $text  Error message or array of $options
	 * @param array $options  Rendering options for <div /> wrapper tag
	 * @return string If there are errors this method returns an error message, otherwise null.
	 */
	public function error($field, $text = null, $options = array()) {
		if (empty($text) || is_array($text)) {
			$text = array_merge(array(
				'required' => __('This field is required', true),
				'valid' => __('The specified value is not valid', true)
			), (array) $text);
		}
		return parent::error($field, $text, $options);
	}
}
?>
