<?php
namespace Innologi\TYPO3FalApi;

use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Mock File Resource
 *
 * Poses as a regular FAL-resource, but isn't persisted therefore not a a real FAL-resource.
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class MockFile extends \TYPO3\CMS\Core\Resource\File
{

    /*
     * @TODO enable this once we're sure there are no problems with it
     * purposefully keep metadata empty, so that none of the metadata stuff gets called
     * @var boolean
     *
     * protected $metaDataLoaded = TRUE;
     */

    /**
     * Returns a modified version of the file.
     *
     * @param string $taskType
     *            The task type of this processing
     * @param array $configuration
     *            the processing configuration, see manual for that
     * @return ProcessedFile The processed file
     */
    public function process($taskType, array $configuration): ProcessedFile
    {
        // directly process file, circumventing any persistence logic

        /** @var ProcessedFile $processedFile */
        $processedFile = GeneralUtility::makeInstance(
            ProcessedFile::class,
            $this,
            $taskType,
            $configuration
        );
        $task = $processedFile->getTask();

        /** @var $processor \TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor */
        $processor = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor::class
        );
        $processor->processTask($task);

        if ($task->isExecuted() && $task->isSuccessful() && $processedFile->isProcessed()) {
            return $processedFile;
        }

        // @TODO throw exception OR still return processedFile
    }

    /**
     * Returns the uid of this file
     *
     * @return integer
     */
    public function getUid(): int
    {
        return 0;
    }

    /**
     * Deletes this file from its storage.
     * This also means that this object becomes useless.
     *
     * @return boolean TRUE if deletion succeeded
     */
    public function delete(): bool
    {
        if (! $this->deleted) {
            $filePath = PATH_site . $this->getPublicUrl();
            @unlink($filePath);
            $this->setDeleted();
        }
    }

    /**
     * Renames this file.
     *
     * @param string $newName
     *            The new file name
     * @param string $conflictMode
     * @return FileInterface
     * @throws Exception\UnsupportedResourceInteraction
     */
    public function rename($newName, $conflictMode = DuplicationBehavior::RENAME): FileInterface
    {
        throw new Exception\UnsupportedResourceInteraction(1525179515, [
            'rename', self::class . ':' . $this->getPublicUrl()
        ]);
    }

    /**
     * Copies this file into a target folder
     *
     * @param Folder $targetFolder
     *            Folder to copy file into.
     * @param string $targetFileName
     *            an optional destination fileName
     * @param string $conflictMode
     *            a value of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     * @return File The new (copied) file.
     * @throws Exception\UnsupportedResourceInteraction
     */
    public function copyTo(Folder $targetFolder, $targetFileName = null, $conflictMode = DuplicationBehavior::RENAME): File
    {
        throw new Exception\UnsupportedResourceInteraction(1525179516, [
            'copy', self::class . ':' . $this->getPublicUrl()
        ]);
    }

    /**
     * Moves the file into the target folder
     *
     * @param Folder $targetFolder
     *            Folder to move file into.
     * @param string $targetFileName
     *            an optional destination fileName
     * @param string $conflictMode
     *            a value of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     * @return File This file object, with updated properties.
     * @throws Exception\UnsupportedResourceInteraction
     */
    public function moveTo(Folder $targetFolder, $targetFileName = null, $conflictMode = DuplicationBehavior::RENAME): File
    {
        throw new Exception\UnsupportedResourceInteraction(1525179517, [
            'move', self::class . ':' . $this->getPublicUrl()
        ]);
    }
}