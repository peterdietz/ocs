<?php

/**
 * @file classes/note/NoteDAO.inc.php
 *
 * Copyright (c) 2005-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NoteDAO
 * @ingroup note
 * @see PKPNoteDAO
 *
 * @brief OJS extension of PKPNoteDAO
 */



import('lib.pkp.classes.note.PKPNoteDAO');
import('classes.note.Note');

class NoteDAO extends PKPNoteDAO {
	/** @var $paperFileDao Object */
	var $paperFileDao;

	/**
	 * Constructor
	 */
	function NoteDAO() {
		$this->paperFileDao =& DAORegistry::getDAO('PaperFileDAO');
		parent::PKPNoteDAO();
	}

	/**
	 * Construct a new data object corresponding to this DAO.
	 * @return Note
	 */
	function newDataObject() {
		return new Note();
	}

	function &_returnNoteFromRow($row) {
		$note =& parent::_returnNoteFromRow($row);

		if ($fileId = $note->getFileId()) {
			$file =& $this->paperFileDao->getPaperFile($fileId);
			$note->setFile($file);
		}

		return $note;
	}
}

?>
