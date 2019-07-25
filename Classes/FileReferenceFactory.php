<?php
namespace Innologi\TYPO3FalApi;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/**
 * FileReference Domain Object factory
 *
 * You only need a Domain Object when you're going to persist a File as a reference
 * in the Parent Object. To persist correctly, you also need to add the following
 * TCA to the Parent Object's reference field:
 *
 * 'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
 *      '{fieldname}',
 *      [
 *          'foreign_match_fields' => [
 *              'fieldname' => '{fieldname}',
 *              'tablenames' => '{tablename}',
 *              'table_local' => 'sys_file',
 *          ],
 *      ]
 * ),
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class FileReferenceFactory implements SingletonInterface
{

    /**
     *
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     *
     * @param ResourceFactory $resourceFactory
     * @return void
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     *
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Creates and returns domain object from filepath.
     *
     * @param string $filePath
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @throws Exception\FileException
     */
    public function createByFilePath(string $filePath): FileReference
    {
        $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($filePath);
        if (! ($fileObject instanceof \TYPO3\CMS\Core\Resource\File)) {
            throw new Exception\FileException(1448550039, [
                $filePath
            ]);
        }
        return $this->create([
            'uid_local' => $fileObject->getUid()
        ]);
    }

    /**
     * Creates and returns domain object from data.
     *
     * @param array $data
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    public function create(array $data): FileReference
    {
        /* @var $object FileReference */
        $object = $this->objectManager->get(FileReference::class);
        // all you really need is an 'uid_local' key with the File uid as value for it
        // to persist correctly. Below method will throw an exception if missing.
        // @TODO document why you're setting a FileReference as the original resource to our new FileReference
        $object->setOriginalResource(
            $this->resourceFactory->createFileReferenceObject($data)
        );
        return $object;
    }
}