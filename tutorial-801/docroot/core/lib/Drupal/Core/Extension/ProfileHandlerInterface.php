<?php

namespace Drupal\Core\Extension;

/**
 * Lists and manages installation profiles.
 */
interface ProfileHandlerInterface {

  /**
   * Retrieve the info array for a profile.
   *
   * Parse and process the profile info.yml file.
   * Processing steps:
   *   1) Ensure default keys are set.
   *   2) Recursively collect dependencies from parent profiles.
   *   3) Exclude dependencies explicitly mentioned in
   *      $info['base profile']['exclude_dependencies']
   *   4) Add the $info['profile_list'] list of dependent profiles.
   *
   * @param string $profile
   *   The name of profile.
   *
   * @return array
   *   The processed $info array.
   *
   * @see install_profile_info()
   */
  public function getProfileInfo($profile);

  /**
   * Stores info data for a profile.
   *
   * This can be used in situations where the info cache needs to be changed
   * This is used for testing.
   *
   * @param string $profile
   *  The name of profile.
   * @param array $info
   *   The info array to be set.
   *
   * @see install_profile_info()
   */
  public function setProfileInfo($profile, array $info);

  /**
   * Clears the profile cache.
   */
  public function clearProfileCache();

  /**
   * Returns a list of dependent installation profiles.
   *
   * @param string $profile
   *   The name of profile. If none is specified, use the current profile.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An associative array of Extension objects, keyed by profile name in
   *   descending order of their dependencies.
   *   (parent profiles first, main profile last)
   */
  public function getProfiles($profile = NULL);

  /**
   * Select the install distribution from the list of profiles.
   *
   * If there are multiple profiles marked as distributions, select the first.
   * If there is an inherited profile marked as a distribution, select it over
   * its base profile.
   *
   * @param string[] $profile_list
   *   List of profile names to search.
   *
   * @return string|null
   *   The selected distribution profile name, or NULL if none is found.
   */
  public function selectDistribution(array $profile_list);

}
