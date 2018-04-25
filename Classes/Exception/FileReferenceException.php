<?php
namespace Innologi\TYPO3FalApi\Exception;

/**
 * File Reference Exception
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class FileReferenceException extends Exception
{

    /**
     *
     * @var string
     */
    protected $message = 'Failed to create a file reference record: %1$s';
}
