<?php

/**
 */
interface HtmlDiffAdvancedInterface {

  // Essential functions

  public function setOldHtml($oldText);

  public function setNewHtml($newText);

  public function getDifference();

  // Convienience functions

  public function getOldHtml();

  public function getNewHtml();

  // Advanced settings functions

  public function setEncoding($encoding);

  public function setInsertSpaceInReplace($boolean);

  public function getInsertSpaceInReplace();

  public function setSpecialCaseChars(array $chars);

  public function getSpecialCaseChars();

  public function addSpecialCaseChar($char);

  public function removeSpecialCaseChar($char);

  public function setSpecialCaseTags(array $tags);

  public function addSpecialCaseTag($tag);

  public function removeSpecialCaseTag($tag);

  public function getSpecialCaseTags();

  public function setGroupDiffs($boolean);

  public function isGroupDiffs();

  public function setPurifierSerializerCachePath($path = NULL);
}