<?php
namespace Innologi\TYPO3FalApi\Exception;

/**
 * SQL Error Exception
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class SqlError extends Exception
{

    /**
     *
     * @var string
     */
    protected $message = 'The following database query produced an unknown error: %1$s';
}
