<?php
class WysiwygHelper extends AppHelper {
	/**
 	 * Helpers
	 *
	 * @var array
	 */
	public $helpers = array('Javascript', 'Form');

	/**
	 * Editor layouts
	 *
	 * @var array
	 */
	private $layouts = array(
		'basic' => array(
			'mode' => 'textareas',
			'editor_selector' => 'wysiwyg',
			'convert_urls' => true,
			'relative_urls' => false,
			'plugins' => array(),
			'theme' => 'advanced',
			'theme_advanced_toolbar_location' => 'top',
			'theme_advanced_toolbar_align' => 'left',
			'theme_advanced_buttons1' => array(
				'bold',
				'italic',
				'underline',
				'separator',
				'justifyleft',
				'justifycenter',
				'justifyright',
				'justifyfull',
				'separator',
				'bullist',
				'numlist',
				'separator',
				'undo',
				'redo',
				'separator',
				'link',
				'unlink'
			),
			'theme_advanced_buttons2' => '',
			'theme_advanced_buttons3' => '',
			'valid_elements' => array(
				'a[href|hreflang|lang|rel]',
				'blockquote',
				'br',
				'center',
				'code',
				'del',
				'em/i',
				'hr',
				'li',
				'ol',
				'p[align<center?justify?left?right]',
				'pre/listing/plaintext/xmp[align]',
				'small',
				'strike',
				'strong/b',
				'sub',
				'sup',
				'table[align<center?left?right|border|cellpadding|cellspacing|width]',
				'tbody[align<center?char?justify?left?right]',
				'td[align<center?char?justify?left?right|colspan|rowspan|valign<baseline?bottom?middle?top|width]',
				'tfoot[align<center?char?justify?left?right|valign<baseline?bottom?middle?top]',
				'th[align<center?char?justify?left?right|colspan|rowspan|valign<baseline?bottom?middle?top|width]',
				'thead[align<center?char?justify?left?right|valign<baseline?bottom?middle?top]',
				'tr[align<center?char?justify?left?right|rowspan|valign<baseline?bottom?middle?top]',
				'u',
				'ul[type]'
			)
		)
	);

	/**
     * Tells if editor JS was already included
	 *
	 * @var bool
	 */
	private $included = false;

	/**
	 * Include WYSIWYG editor
	 *
	 * @param string $fieldName Field name (Model.field)
	 * @param array $options Special options (layout, includeField), or options for field
	 * @param array $editorOptions Editor options (sent directly to JS instantiation)
	 * @return string HTML + JS code
	 */
	public function editor($fieldName, $options = array(), $editorOptions = array()) {
		$defaults = array(
			'layout' => 'basic',
			'includeField' => true
		);
		$options = array_merge($defaults, $options);
		$fieldOptions = array_merge(array(
			'class' => 'wysiwyg'
		), array_diff_key($options, $defaults));

		$layout = null;
		if (!empty($options['layout'])) {
			$layout = Configure::read('Wysiwyg.' . $options['layout']);
		}

		if (!empty($options['layout']) && !empty($this->layouts[$options['layout']])) {
			if (!empty($layout)) {
				$layout = Set::merge($this->layouts[$options['layout']], $layout);
			} else {
				$layout = $this->layouts[$options['layout']];
			}
		}

		if (!empty($options['layout']) && !empty($layout)) {
			$editorOptions = array_merge($layout, $editorOptions);
		}

		foreach($editorOptions as $key => $value) {
			if (is_array($value)) {
				$editorOptions[$key] = join(',', $value);
			}
		}

		if (!$this->included) {
			$this->Javascript->link('tiny_mce/tiny_mce.js', false);
		}

		$script = 'tinyMCE.init(' . $this->Javascript->object($editorOptions) . ');';

		$out = '';
		if (!empty($options['includeField'])) {
			$out .= $this->Form->textarea($fieldName, $fieldOptions);
		}
		$out .= $this->Javascript->codeBlock($script);

		return $out;
	}
}
?>
