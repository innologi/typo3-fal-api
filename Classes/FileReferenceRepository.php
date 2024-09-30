<?php
namespace Innologi\TYPO3FalApi;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * File Reference repository
 *
 * Note that this repository does not follow FLOW conventions and
 * is highly dependent of TYPO3 CMS' DatabaseConnection and DataHandler
 * classes.
 *
 * Should only be used in use-cases where FLOW/Extbase persistence
 * is disabled or not available. Otherwise you should use the
 * FileReferenceFactory and simply persist its parentObject.
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class FileReferenceRepository implements SingletonInterface
{

    /**
     *
     * @var string
     */
    protected $referenceTable = 'sys_file_reference';

    /**
     *
     * @var integer
     */
    protected $storagePid = 0;

    /**
     *
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $beUser;

    /**
     * Class constructor
     *
     * @return void
     */
    public function __construct(
        protected readonly ResourceFactory $resourceFactory,
        protected readonly DataHandler $dataHandler,
    ) {
        // don't need log entries for these
        $this->dataHandler->enableLogging = false;

        // this is enough to keep our DataHandler-method-calls from failing outside of BE
        $this->beUser = $GLOBALS['BE_USER'] ?? GeneralUtility::makeInstance(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class);
        // @TODO ___temp, remove once instructions are made?
        $this->beUser->user['admin'] = 1;
    }

    /**
     * Add a File Reference record via DataHandler
     *
     * Optionally it can be used to update an existing record by providing a $referenceUid
     *
     * @param integer $fileUid
     * @param string $foreignTable
     * @param integer $foreignUid
     * @param string $foreignField
     * @param string $referenceUid
     * @return void
     * @throws Exception\FileReferenceException
     */
    public function addRecord(int $fileUid, string $foreignTable, int $foreignUid, string $foreignField, ?string $referenceUid = null): void
    {
        if ($referenceUid === null) {
            $referenceUid = 'NEW' . $fileUid;
        }

        $data = [
            $this->referenceTable => [
                $referenceUid => [
                    'uid_local' => $fileUid,
                    'uid_foreign' => $foreignUid,
                    'tablenames' => $foreignTable,
                    'fieldname' => $foreignField,
                    'pid' => $this->storagePid
                ]
            ],
            // immediately update the reference in foreign table
            $foreignTable => [
                $foreignUid => [
                    'pid' => $this->storagePid,
                    $foreignField => $referenceUid
                ]
            ]
        ];

        $this->dataHandler->start($data, [], $this->beUser);
        $this->dataHandler->process_datamap();
        if ($this->dataHandler->errorLog) {
            throw new Exception\FileReferenceException(1448550062, [
                DebugUtility::viewArray($this->dataHandler->errorLog)
            ]);
        }
    }

    /**
     * Add a File Reference record via DataHandler
     *
     * Differs from addRecord() by the first parameter being a filePath
     * which will automatically be resolved into a valid sys_file uid.
     *
     * Optionally it can be used to update an existing record by providing a $referenceUid
     *
     * @param string $filePath
     * @param string $foreignTable
     * @param integer $foreignUid
     * @param string $foreignField
     * @param string $referenceUid
     * @return void
     * @throws Exception\FileException
     * @see addRecord()
     */
    public function addRecordByFilePath(string $filePath, string $foreignTable, int $foreignUid, string $foreignField, ?string $referenceUid = null): void
    {
        $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($filePath);
        if (! ($fileObject instanceof \TYPO3\CMS\Core\Resource\File)) {
            throw new Exception\FileException(1448550118, [
                $filePath
            ]);
        }
        $this->addRecord($fileObject->getUid(), $foreignTable, $foreignUid, $foreignField, $referenceUid);
    }

    /**
     * Adds or updates a File Reference record via DataHandler,
     * by automatically determining whether the reference already
     * exists in the reference table.
     *
     * Note that if the reference points to a different fileUid,
     * the original reference will be lost. We do this because in
     * all of our use-cases, there should never be more than one
     * ONE file reference per foreign record.
     *
     * @param integer $fileUid
     * @param string $foreignTable
     * @param integer $foreignUid
     * @param string $foreignField
     * @return void
     * @see addRecord()
     */
    public function upsertRecord(int $fileUid, string $foreignTable, int $foreignUid, string $foreignField): void
    {
        $row = $this->findOneByData([
            'uid_foreign' => $foreignUid,
            'tablenames' => $foreignTable,
            'fieldname' => $foreignField
        ]);

        if ($row !== false) {
            // one found: only update it if $fileUid has changed
            if ((int) $row['uid_local'] !== $fileUid) {
                $this->addRecord($fileUid, $foreignTable, $foreignUid, $foreignField, (int) $row['uid']);
            }
        } else {
            // none found: just add is as new
            $this->addRecord($fileUid, $foreignTable, $foreignUid, $foreignField);
        }
    }

    /**
     * Adds or updates a File Reference record via DataHandler,
     * by automatically determining whether the reference already
     * exists in the reference table.
     *
     * Differs from upsertRecord() by the first parameter being a filePath
     * which will automatically be resolved into a valid sys_file uid.
     *
     * @param string $filePath
     * @param string $foreignTable
     * @param integer $foreignUid
     * @param string $foreignField
     * @return void
     * @throws Exception\FileException
     * @see upsertRecord()
     */
    public function upsertRecordByFilePath(string $filePath, string $foreignTable, int $foreignUid, string $foreignField): void
    {
        $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($filePath);
        if (! ($fileObject instanceof \TYPO3\CMS\Core\Resource\File)) {
            throw new Exception\FileException(1448550330, [
                $filePath
            ]);
        }
        $this->upsertRecord($fileObject->getUid(), $foreignTable, $foreignUid, $foreignField);
    }

    /**
     * Returns a single reference record that matches $data
     * conditions.
     *
     * @param array $data
     *            Contains property => value conditions
     * @return array|boolean
     * @throws Exception\SqlError
     */
    public function findOneByData(array $data)
    {
        // @extensionScannerIgnoreLine
        /* @var $databaseConnection \TYPO3\CMS\Core\Database\DatabaseConnection */
        $databaseConnection = $GLOBALS['TYPO3_DB'];
        $databaseConnection->store_lastBuiltQuery = true;

        $data['pid'] = $this->storagePid;
        $data['deleted'] = 0;

        $where = [];
        foreach ($data as $property => $value) {
            $where[] = $property . '=' . $databaseConnection->fullQuoteStr($value, $this->referenceTable);
        }
        // @extensionScannerIgnoreLine
        $where = empty($where) ? '' : join(' ' . DatabaseConnection::AND_Constraint . ' ', $where);

        $row = $databaseConnection->exec_SELECTgetSingleRow('*', $this->referenceTable, $where, '', 'uid DESC');

        if ($row === null) {
            throw new Exception\SqlError(1448550356, [
                $databaseConnection->debug_lastBuiltQuery
            ]);
        }
        return $row;
    }

    /**
     * Sets storagePid for all database interactions of this class.
     *
     * @param integer $pid
     * @return void
     */
    public function setStoragePid(int $pid): void
    {
        $this->storagePid = $pid;
    }
}