<?php

/**
 * @file index.php
 *
 * Copyright (c) 2000-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Wrapper for XML user import/export plugin.
 *
 * @package plugins.importexport.users
 *
 * $Id$
 */

require_once('UserImportExportPlugin.inc.php');

return new UserImportExportPlugin();

?>