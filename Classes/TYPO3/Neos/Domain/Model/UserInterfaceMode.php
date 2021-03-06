<?php
namespace TYPO3\Neos\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Describes the mode in which the Neos interface is rendering currently, mainly distinguishing between edit and preview modes currently.
 */
class UserInterfaceMode {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var boolean
	 */
	protected $preview;

	/**
	 * @var boolean
	 */
	protected $edit;

	/**
	 * @var string
	 */
	protected $typoScriptPath;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var array
	 */
	protected $options;

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return boolean
	 */
	public function isPreview() {
		return $this->preview;
	}

	/**
	 * @param boolean $preview
	 * @return void
	 */
	public function setPreview($preview) {
		$this->preview = $preview;
	}

	/**
	 * @return boolean
	 */
	public function isEdit() {
		return $this->edit;
	}

	/**
	 * @param boolean $edit
	 * @return void
	 */
	public function setEdit($edit) {
		$this->edit = $edit;
	}

	/**
	 * @return string
	 */
	public function getTypoScriptPath() {
		return $this->typoScriptPath;
	}

	/**
	 * @param string $typoScriptPath
	 * @return void
	 */
	public function setTypoScriptPath($typoScriptPath) {
		$this->typoScriptPath = $typoScriptPath;
	}

	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param string $path
	 * @return mixed
	 */
	public function getOptionByPath($path) {
		return ObjectAccess::getPropertyPath($this->options, $path);
	}

	/**
	 * @param array $options
	 * @return void
	 */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 * Creates an UserInterfaceMode object by configuration
	 *
	 * @param string $modeName
	 * @param array $configuration
	 * @return static
	 */
	public static function createByConfiguration($modeName, array $configuration) {
		$mode = new static();
		$mode->setName($modeName);
		$mode->setPreview($configuration['isPreviewMode']);
		$mode->setEdit($configuration['isEditingMode']);
		$mode->setTitle($configuration['title']);
		$mode->setTypoScriptPath($configuration['typoScriptRenderingPath']);

		if (isset($configuration['options']) && is_array($configuration['options'])) {
			$mode->setOptions($configuration['options']);
		}

		return $mode;
	}

}