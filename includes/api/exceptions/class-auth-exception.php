<?php
/**
 * Authentication Exception - For authentication/authorization errors
 * File: includes/api/exceptions/class-auth-exception.php
 */

namespace AAPI\API;

/**
 * Authentication Exception class.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api/exceptions
 */
class Auth_Exception extends API_Exception {
    
    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string         $message     Error message.
     * @param    int            $code        Error code.
     * @param    \Throwable     $previous    Previous exception.
     */
    public function __construct($message = 'Authentication failed', $code = 401, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}