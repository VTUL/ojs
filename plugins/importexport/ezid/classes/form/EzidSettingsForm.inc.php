<?php

/**
 * @file plugins/importexport/crossref/classes/form/CrossRefSettingsForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefSettingsForm
 * @ingroup plugins_importexport_crossref_classes_form
 *
 * @brief Form for journal managers to setup the CrossRef plug-in.
 */


if (!class_exists('DOIExportSettingsForm')) { // Bug #7848
  import('plugins.importexport.crossref.classes.form.DOIExportSettingsForm');
}

class EzidSettingsForm extends DOIExportSettingsForm {

  //
  // Constructor
  //
  /**
   * Constructor
   * @param $plugin CrossRefExportPlugin
   * @param $journalId integer
   */
  function EzidSettingsForm(&$plugin, $journalId) {
    // Configure the object.
    parent::DOIExportSettingsForm($plugin, $journalId);
  }


  //
  // Implement template methods from DOIExportSettingsForm
  //
  /**
   * @see DOIExportSettingsForm::getFormFields()
   */
  function getFormFields() {
    return array(
      'username' => 'string',
      'password' => 'string',
      'shoulder' => 'string',
      'automaticRegistration' => 'bool'
      );
  }

  /**
   * @see DOIExportSettingsForm::isOptional()
   */
  function isOptional($settingName) {
    return in_array($settingName, array('username', 'password', 'shoulder', 'automaticRegistration'));
  }
}

?>
