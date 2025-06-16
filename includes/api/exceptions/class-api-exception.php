<?php
/**
 * API Exception - Base exception class
 * File: includes/api/exceptions/class-api-exception.php
 */

namespace AAPI\API;

/**
 * Base API Exception class.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api/exceptions
 */
class API_Exception extends \Exception {
    
    /**
     * Additional error details.
     *
     * @since    1.0.0
     * @var      mixed    $details    Error details.
     */
    protected $details;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string         $message     Error message.
     * @param    int            $code        Error code.
     * @param    \Throwable     $previous    Previous exception.
     * @param    mixed          $details     Additional details.
     */
    public function __construct($message = '', $code = 0, \Throwable $previous = null, $details = null) {
        parent::__construct($message, $code, $previous);
        $this->details = $details;
    }

    /**
     * Get error details.
     *
     * @since    1.0.0
     * @return   mixed    Error details.
     */
    public function getDetails() {
        return $this->details;
    }
}