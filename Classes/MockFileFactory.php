<?php
namespace Innologi\TYPO3FalApi;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Mock File Object Factory
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class MockFileFactory implements SingletonInterface
{

    /**
     *
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     *
     * @var array
     */
    protected $mockFileInstances = [];

    /**
     *
     * @var string
     */
    protected $sitePath;

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
     * @return string
     */
    protected function getSitePath()
    {
        if ($this->sitePath === null) {
            /** @var Typo3Version $typo3Version */
            $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
            $this->sitePath = $typo3Version->getMajorVersion() > 10 ? '' : \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
        }
        return $this->sitePath;
    }

    /**
     * Returns MockFile domain object from filepath.
     * Will create it, if not yet available in local cache.
     *
     * @param string $filePath
     * @param integer $storageUid
     * @return MockFile
     */
    public function getByFilePath(string $filePath, int $storageUid = 0): MockFile
    {
        if (! isset($this->mockFileInstances[$filePath])) {
            $this->mockFileInstances[$filePath] = $this->createByFilePath($filePath, $storageUid);
        }
        return $this->mockFileInstances[$filePath];
    }

    /**
     * Creates and returns domain object from filepath.
     *
     * @param string $filePath
     * @param integer $storageUid
     * @return MockFile
     */
    public function createByFilePath(string $filePath, int $storageUid = 0): MockFile
    {
        // @TODO do we check if the file exists? after all we rely on filemtime()
        $storageObject = $this->resourceFactory->getStorageObject($storageUid);
        // strip off the absolute path and storage basePath if present
        $absolutePathPart = $this->getSitePath() . $storageObject->getConfiguration()['basePath'];
        // file identifier is relative to storage basePath
        // @TODO identifiers apparently are stored with a prefixed slash. Debug if we need to keep it here as well
        $identifier = str_starts_with($filePath, $absolutePathPart) ? str_replace($absolutePathPart, '', $filePath) : $filePath;
        return $this->create([
            'identifier' => $identifier,
            'modification_date' => (int) filemtime($filePath)
        ], $storageObject);
    }

    /**
     * Creates and returns domain object from data.
     * Needs to contain at least the elements:
     * - identifier: filepath relative to storage basepath
     * - storage: storage uid (if $storageObject is null)
     *
     * @param array $data
     * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject
     * @return MockFile
     * @throws Exception\MockFileException
     */
    public function create(array $data, ?\TYPO3\CMS\Core\Resource\ResourceStorage $storageObject = null): MockFile
    {
        if (! isset($data['identifier']) ) {
            throw new Exception\MockFileException(1525263286, [
                json_encode($data)
            ]);
        }

        // storage object checks / retrieval
        if (isset($data['storage']) && is_int($data['storage'])) {
            $storageObject = $this->resourceFactory->getStorageObject($data['storage']);
        } elseif ($storageObject !== null) {
            $data['storage'] = $storageObject->getUid();
        } else {
            throw new Exception\MockFileException(1525263733, [],
                'Creating a mock file object requires a valid $storageObject or $data[\'storage\'] uid'
            );
        }

        return GeneralUtility::makeInstance(MockFile::class, array_merge([
            'name' => basename($data['identifier']),
            'missing' => 0,
            'modification_date' => 0
        ], $data), $storageObject);
    }
}