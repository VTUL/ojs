<?php

/**
 * @defgroup plugins_importexport_ezid
 */

/**
 * @file plugins/importexport/ezid/index.php
 *
 * Copyright (c) 2015 Virginia Tech University Library
 * Copyright (c) 2015 Tingting Jiang
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_ezid
 *
 * @brief Wrapper for the CrossRef export plugin.
 */


require_once('EzidRegisterPlugin.inc.php');

return new EzidRegisterPlugin();

?>
