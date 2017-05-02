<?php

class HtmlDiffAdvanced extends \Caxy\HtmlDiff\HtmlDiff implements HtmlDiffAdvancedInterface {

  protected $buildRequired = TRUE;

  public function __construct($oldText = '', $newText = '', $encoding = 'UTF-8', $specialCaseTags = null, $groupDiffs = null) {
    parent::__construct($oldText, $newText, $encoding, $specialCaseTags, $groupDiffs);

    if ($oldText) {
      $this->setOldHtml($oldText);
    }

    if ($newText) {
      $this->setNewHtml($newText);
    }
  }

  public function setEncoding($encoding) {
    $this->encoding = $encoding;
    $this->buildRequired = TRUE;
  }

  public function setOldHtml($oldText) {
    $this->oldText = $oldText;
    $this->buildRequired = TRUE;
  }

  public function setNewHtml($newText) {
    $this->newText = $newText;
    $this->buildRequired = TRUE;
  }

  public function setInsertSpaceInReplace($boolean) {
    parent::setInsertSpaceInReplace($boolean);
    $this->buildRequired = TRUE;
  }

  public function setSpecialCaseChars(array $chars) {
    parent::setSpecialCaseChars($chars);
    $this->buildRequired = TRUE;
  }

  public function addSpecialCaseChar($char) {
    parent::addSpecialCaseChar($char);
    $this->buildRequired = TRUE;
  }

  public function removeSpecialCaseChar($char) {
    parent::removeSpecialCaseChar($char);
    $this->buildRequired = TRUE;
  }

  public function setSpecialCaseTags(array $tags = array()) {
    parent::setSpecialCaseTags($tags);
    $this->buildRequired = TRUE;
  }

  public function addSpecialCaseTag($tag) {
    parent::addSpecialCaseTag($tag);
    $this->buildRequired = TRUE;
  }

  public function removeSpecialCaseTag($tag) {
    parent::removeSpecialCaseTag($tag);
    $this->buildRequired = TRUE;
  }

  public function setGroupDiffs($boolean) {
    parent::setGroupDiffs($this->groupDiffs);
    $this->buildRequired = TRUE;
  }

  public function getDifference() {
    if ($this->buildRequired) {
      $this->build();
    }
    return parent::getDifference();
  }

  public function build() {
    if ($this->buildRequired) {
      $this->buildRequired = FALSE;
      $this->content = '';
      return parent::build();
    }
  }

  public function setPurifierSerializerCachePath($path = NULL) {
    $HTMLPurifierConfig = \HTMLPurifier_Config::createDefault();
    $HTMLPurifierConfig->set('Cache.SerializerPath', $path);
    $this->setHTMLPurifierConfig($HTMLPurifierConfig);
  }

}
