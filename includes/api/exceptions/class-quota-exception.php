<?php
/**
 * Quota Exception - For rate limit/quota exceeded errors
 * File: includes/api/exceptions/class-quota-exception.php
 */

namespace AAPI\API;

/**
 * Quota/Rate Limit Exception class.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api/exceptions
 */
class Quota_Exception extends API_Exception {
    
    /**
     * Reset time for quota.
     *
     * @since    1.0.0
     * @var      int    $reset_time    Unix timestamp when quota resets.
     */
    protected $reset_time;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string         $message      Error message.
     * @param    int            $reset_time   When quota resets.
     * @param    int            $code         Error code.
     * @param    \Throwable     $previous     Previous exception.
     */
    public function __construct($message = '', $reset_time = 0, $code = 429, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->reset_time = $reset_time;
    }

    /**
     * Get reset time.
     *
     * @since    1.0.0
     * @return   int    Reset timestamp.
     */
    public function getResetTime() {
        return $this->reset_time;
    }

    /**
     * Get human-readable time until reset.
     *
     * @since    1.0.0
     * @return   string    Time until reset.
     */
    public function getTimeUntilReset() {
        if (!$this->reset_time) {
            return 'Unknown';
        }
        
        $diff = $this->reset_time - time();
        if ($diff <= 0) {
            return 'Now';
        }
        
        return human_time_diff(time(), $this->reset_time);
    }
}