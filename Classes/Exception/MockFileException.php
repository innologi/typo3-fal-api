<?php
namespace Innologi\TYPO3FalApi\Exception;

/**
 * Mock File Exception
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class MockFileException extends Exception
{

    /**
     *
     * @var string
     */
    protected $message = 'Failed to create a mock file object from \'%1$s\'';
}
