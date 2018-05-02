<?php
namespace Innologi\TYPO3FalApi\Exception;

/**
 * Unsupported Resource Interaction Exception
 *
 * @package TYPO3FalApi
 * @author Frenck Lutke
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 2 or later
 */
class UnsupportedResourceInteraction extends Exception
{

    /**
     *
     * @var string
     */
    protected $message = '\'%1$s\' is not supported on resource \'%2$s\'';
}
