<?php
/**
 * API Queue System
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AAPI
 * @subpackage AAPI/includes/api
 */

namespace AAPI\API;

use AAPI\Core\Settings;

/**
 * API Queue class.
 *
 * Manages queued API operations for bulk processing, background jobs,
 * and rate-limited operations with retry logic and progress tracking.
 *
 * @since      1.0.0
 * @package    AAPI
 * @subpackage AAPI/includes/api
 * @author     Your Name <email@example.com>
 */
class API_Queue {

    /**
     * Queue table name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $table_name    Database table name.
     */
    private $table_name;

    /**
     * Maximum retry attempts.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_retries    Maximum number of retries.
     */
    private $max_retries = 3;

    /**
     * Batch size for processing.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $batch_size    Number of jobs to process per batch.
     */
    private $batch_size = 10;

    /**
     * Job timeout in seconds.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $job_timeout    Timeout for individual jobs.
     */
    private $job_timeout = 30;

    /**
     * Settings instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Settings    $settings    Settings instance.
     */
    private $settings;

    /**
     * Current batch ID being processed.
     *
     * @since    1.0.0
     * @access   private
     * @var      string|null    $current_batch    Current batch identifier.
     */
    private $current_batch = null;

    /**
     * Progress tracking.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $progress    Progress information.
     */
    private $progress = array();

    /**
     * Queue instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      API_Queue    $instance    Singleton instance.
     */
    private static $instance = null;

    /**
     * Job statuses.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Job priorities.
     *
     * @since    1.0.0
     * @access   public
     * @var      int    Priority constants.
     */
    const PRIORITY_LOW = 10;
    const PRIORITY_NORMAL = 50;
    const PRIORITY_HIGH = 90;
    const PRIORITY_URGENT = 100;

    /**
     * Get instance.
     *
     * @since    1.0.0
     * @return   API_Queue    Queue instance.
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     *
     * @since    1.0.0
     */
    public function __construct() {
        global $wpdb;
        
        $this->table_name = $wpdb->prefix . 'aapi_api_queue';
        $this->settings = new Settings();
        $this->init();
    }

    /**
     * Initialize queue system.
     *
     * @since    1.0.0
     */
    private function init() {
        // Load configuration
        $this->max_retries = $this->settings->get('queue', 'max_retries', 3);
        $this->batch_size = $this->settings->get('queue', 'batch_size', 10);
        $this->job_timeout = $this->settings->get('queue', 'job_timeout', 30);
        
        // Register cron hooks
        add_action('aapi_process_queue', array($this, 'process_queue'));
        add_action('aapi_cleanup_queue', array($this, 'cleanup_old_jobs'));
        
        // Schedule queue processor if not scheduled
        if (!wp_next_scheduled('aapi_process_queue')) {
            wp_schedule_event(time(), 'aapi_queue_interval', 'aapi_process_queue');
        }
    }

    /**
     * Add job to queue.
     *
     * @since    1.0.0
     * @param    string    $action      Job action/type.
     * @param    array     $payload     Job data.
     * @param    array     $options     Job options.
     * @return   int|false              Job ID or false on failure.
     */
    public function add(string $action, array $payload, array $options = array()) {
        global $wpdb;
        
        $defaults = array(
            'priority' => self::PRIORITY_NORMAL,
            'provider' => '',
            'batch_id' => null,
            'scheduled_at' => current_time('mysql'),
            'max_retries' => $this->max_retries,
            'metadata' => array(),
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Prepare data
        $data = array(
            'action' => $action,
            'payload' => json_encode($payload),
            'provider' => $options['provider'],
            'batch_id' => $options['batch_id'],
            'priority' => $options['priority'],
            'status' => self::STATUS_PENDING,
            'attempts' => 0,
            'scheduled_at' => $options['scheduled_at'],
            'created_at' => current_time('mysql'),
            'metadata' => json_encode($options['metadata']),
        );
        
        // Insert job
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array('%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            $this->log_error('Failed to add job to queue: ' . $wpdb->last_error);
            return false;
        }
        
        $job_id = $wpdb->insert_id;
        
        // Trigger immediate processing for high priority jobs
        if ($options['priority'] >= self::PRIORITY_HIGH) {
            $this->maybe_trigger_immediate_processing();
        }
        
        return $job_id;
    }

    /**
     * Add multiple jobs to queue.
     *
     * @since    1.0.0
     * @param    array    $jobs       Array of jobs to add.
     * @param    array    $options    Bulk options.
     * @return   array                Array of job IDs.
     */
    public function add_bulk(array $jobs, array $options = array()): array {
        $batch_id = $options['batch_id'] ?? $this->generate_batch_id();
        $job_ids = array();
        
        foreach ($jobs as $job) {
            if (!isset($job['action']) || !isset($job['payload'])) {
                continue;
            }
            
            $job_options = array_merge(
                $options,
                $job['options'] ?? array(),
                array('batch_id' => $batch_id)
            );
            
            $job_id = $this->add($job['action'], $job['payload'], $job_options);
            
            if ($job_id !== false) {
                $job_ids[] = $job_id;
            }
        }
        
        // Store batch info
        if (!empty($job_ids)) {
            $this->store_batch_info($batch_id, count($job_ids));
        }
        
        return $job_ids;
    }

    /**
     * Process queue.
     *
     * @since    1.0.0
     * @param    int|null    $limit    Number of jobs to process.
     * @return   array                 Processing results.
     */
    public function process_queue(?int $limit = null): array {
        global $wpdb;
        
        if ($limit === null) {
            $limit = $this->batch_size;
        }
        
        // Prevent concurrent processing
        if ($this->is_processing()) {
            return array(
                'status' => 'already_running',
                'message' => 'Queue is already being processed',
            );
        }
        
        // Mark as processing
        $this->set_processing_flag();
        
        $results = array(
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => array(),
        );
        
        try {
            // Get pending jobs
            $jobs = $this->get_pending_jobs($limit);
            
            foreach ($jobs as $job) {
                $result = $this->process_job($job);
                
                $results['processed']++;
                
                if ($result['success']) {
                    $results['succeeded']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = array(
                        'job_id' => $job->id,
                        'error' => $result['error'],
                    );
                }
                
                // Update progress
                $this->update_progress($job->batch_id, $results);
                
                // Check if we should continue
                if ($this->should_stop_processing()) {
                    break;
                }
            }
            
        } catch (\Exception $e) {
            $this->log_error('Queue processing error: ' . $e->getMessage());
            $results['errors'][] = array(
                'job_id' => 0,
                'error' => $e->getMessage(),
            );
        } finally {
            // Clear processing flag
            $this->clear_processing_flag();
        }
        
        return $results;
    }

    /**
     * Process single job.
     *
     * @since    1.0.0
     * @param    object    $job    Job object from database.
     * @return   array             Processing result.
     */
    private function process_job($job): array {
        global $wpdb;
        
        // Update status to processing
        $this->update_job_status($job->id, self::STATUS_PROCESSING);
        
        // Increment attempts
        $wpdb->update(
            $this->table_name,
            array(
                'attempts' => $job->attempts + 1,
                'started_at' => current_time('mysql'),
            ),
            array('id' => $job->id),
            array('%d', '%s'),
            array('%d')
        );
        
        $result = array(
            'success' => false,
            'error' => null,
            'data' => null,
        );
        
        try {
            // Set timeout
            set_time_limit($this->job_timeout);
            
            // Decode payload
            $payload = json_decode($job->payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid job payload');
            }
            
            // Execute job based on action
            $job_result = $this->execute_job($job->action, $payload, $job);
            
            // Mark as completed
            $wpdb->update(
                $this->table_name,
                array(
                    'status' => self::STATUS_COMPLETED,
                    'completed_at' => current_time('mysql'),
                    'result' => json_encode($job_result),
                ),
                array('id' => $job->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            $result['success'] = true;
            $result['data'] = $job_result;
            
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            
            // Check if should retry
            if ($job->attempts < $this->max_retries) {
                // Schedule retry
                $retry_at = $this->calculate_retry_time($job->attempts);
                
                $wpdb->update(
                    $this->table_name,
                    array(
                        'status' => self::STATUS_PENDING,
                        'scheduled_at' => $retry_at,
                        'error_message' => $e->getMessage(),
                    ),
                    array('id' => $job->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            } else {
                // Mark as failed
                $wpdb->update(
                    $this->table_name,
                    array(
                        'status' => self::STATUS_FAILED,
                        'completed_at' => current_time('mysql'),
                        'error_message' => $e->getMessage(),
                    ),
                    array('id' => $job->id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            }
        }
        
        return $result;
    }

    /**
     * Execute job action.
     *
     * @since    1.0.0
     * @param    string    $action     Job action.
     * @param    array     $payload    Job payload.
     * @param    object    $job        Job object.
     * @return   mixed                 Job result.
     * @throws   \Exception           If job execution fails.
     */
    private function execute_job(string $action, array $payload, $job) {
        $api_manager = API_Manager::get_instance();
        
        switch ($action) {
            case 'import_product':
                if (!isset($payload['asin'])) {
                    throw new \Exception('ASIN is required');
                }
                
                $product = $api_manager->get_product(
                    $payload['asin'],
                    $payload['options'] ?? array()
                );
                
                if (!$product) {
                    throw new \Exception('Failed to fetch product');
                }
                
                // Create/update product post
                $post_id = $this->create_product_post($product);
                
                return array(
                    'post_id' => $post_id,
                    'asin' => $payload['asin'],
                );
                
            case 'update_product':
                if (!isset($payload['post_id'])) {
                    throw new \Exception('Post ID is required');
                }
                
                $asin = get_post_meta($payload['post_id'], '_aapi_asin', true);
                if (!$asin) {
                    throw new \Exception('ASIN not found for post');
                }
                
                $product = $api_manager->get_product($asin);
                
                if (!$product) {
                    throw new \Exception('Failed to fetch product update');
                }
                
                $this->update_product_post($payload['post_id'], $product);
                
                return array(
                    'post_id' => $payload['post_id'],
                    'updated' => true,
                );
                
            case 'bulk_search':
                if (!isset($payload['keyword'])) {
                    throw new \Exception('Keyword is required');
                }
                
                $results = $api_manager->search_products(
                    $payload['keyword'],
                    $payload['options'] ?? array()
                );
                
                return $results;
                
            default:
                // Allow custom job handlers
                $result = apply_filters('aapi_queue_execute_job', null, $action, $payload, $job);
                
                if ($result === null) {
                    throw new \Exception('Unknown job action: ' . $action);
                }
                
                return $result;
        }
    }

    /**
     * Get pending jobs.
     *
     * @since    1.0.0
     * @param    int    $limit    Number of jobs to retrieve.
     * @return   array            Array of job objects.
     */
    private function get_pending_jobs(int $limit): array {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
             WHERE status = %s
             AND scheduled_at <= %s
             ORDER BY priority DESC, scheduled_at ASC
             LIMIT %d",
            self::STATUS_PENDING,
            current_time('mysql'),
            $limit
        );
        
        return $wpdb->get_results($query);
    }

    /**
     * Get job by ID.
     *
     * @since    1.0.0
     * @param    int    $job_id    Job ID.
     * @return   object|null       Job object or null.
     */
    public function get_job(int $job_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $job_id
            )
        );
    }

    /**
     * Get jobs by batch ID.
     *
     * @since    1.0.0
     * @param    string    $batch_id    Batch ID.
     * @return   array                  Array of job objects.
     */
    public function get_batch_jobs(string $batch_id): array {
        global $wpdb;
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE batch_id = %s ORDER BY id",
                $batch_id
            )
        );
    }

    /**
     * Get batch status.
     *
     * @since    1.0.0
     * @param    string    $batch_id    Batch ID.
     * @return   array                  Batch status information.
     */
    public function get_batch_status(string $batch_id): array {
        global $wpdb;
        
        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as cancelled
                 FROM {$this->table_name}
                 WHERE batch_id = %s",
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
                $batch_id
            ),
            ARRAY_A
        );
        
        $progress = $stats['total'] > 0 
            ? round((($stats['completed'] + $stats['failed']) / $stats['total']) * 100, 2)
            : 0;
        
        return array(
            'batch_id' => $batch_id,
            'total' => intval($stats['total']),
            'pending' => intval($stats['pending']),
            'processing' => intval($stats['processing']),
            'completed' => intval($stats['completed']),
            'failed' => intval($stats['failed']),
            'cancelled' => intval($stats['cancelled']),
            'progress' => $progress,
            'is_complete' => $stats['pending'] == 0 && $stats['processing'] == 0,
        );
    }

    /**
     * Cancel job.
     *
     * @since    1.0.0
     * @param    int    $job_id    Job ID.
     * @return   bool              Success status.
     */
    public function cancel_job(int $job_id): bool {
        return $this->update_job_status($job_id, self::STATUS_CANCELLED);
    }

    /**
     * Cancel batch.
     *
     * @since    1.0.0
     * @param    string    $batch_id    Batch ID.
     * @return   int                    Number of jobs cancelled.
     */
    public function cancel_batch(string $batch_id): int {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array('status' => self::STATUS_CANCELLED),
            array(
                'batch_id' => $batch_id,
                'status' => self::STATUS_PENDING,
            ),
            array('%s'),
            array('%s', '%s')
        );
        
        return $result === false ? 0 : $result;
    }

    /**
     * Retry failed jobs.
     *
     * @since    1.0.0
     * @param    string|null    $batch_id    Batch ID or null for all.
     * @return   int                         Number of jobs retried.
     */
    public function retry_failed_jobs(?string $batch_id = null): int {
        global $wpdb;
        
        $where = array('status' => self::STATUS_FAILED);
        $where_format = array('%s');
        
        if ($batch_id !== null) {
            $where['batch_id'] = $batch_id;
            $where_format[] = '%s';
        }
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'status' => self::STATUS_PENDING,
                'attempts' => 0,
                'scheduled_at' => current_time('mysql'),
                'error_message' => null,
            ),
            $where,
            array('%s', '%d', '%s', '%s'),
            $where_format
        );
        
        return $result === false ? 0 : $result;
    }

    /**
     * Get queue statistics.
     *
     * @since    1.0.0
     * @return   array    Queue statistics.
     */
    public function get_statistics(): array {
        global $wpdb;
        
        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_jobs,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as processing,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = %s THEN 1 ELSE 0 END) as failed,
                    AVG(CASE 
                        WHEN completed_at IS NOT NULL AND started_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(SECOND, started_at, completed_at) 
                        ELSE NULL 
                    END) as avg_processing_time,
                    COUNT(DISTINCT batch_id) as total_batches
                 FROM {$this->table_name}",
                self::STATUS_PENDING,
                self::STATUS_PROCESSING,
                self::STATUS_COMPLETED,
                self::STATUS_FAILED
            ),
            ARRAY_A
        );
        
        // Get recent activity
        $recent_completed = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE status = 'completed' 
             AND completed_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        return array(
            'total_jobs' => intval($stats['total_jobs']),
            'pending' => intval($stats['pending']),
            'processing' => intval($stats['processing']),
            'completed' => intval($stats['completed']),
            'failed' => intval($stats['failed']),
            'avg_processing_time' => round(floatval($stats['avg_processing_time']), 2),
            'total_batches' => intval($stats['total_batches']),
            'recent_completed' => intval($recent_completed),
            'success_rate' => $stats['completed'] + $stats['failed'] > 0
                ? round(($stats['completed'] / ($stats['completed'] + $stats['failed'])) * 100, 2)
                : 0,
        );
    }

    /**
     * Cleanup old jobs.
     *
     * @since    1.0.0
     * @param    int    $days    Days to keep completed/failed jobs.
     * @return   int             Number of jobs deleted.
     */
    public function cleanup_old_jobs(int $days = 30): int {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name}
                 WHERE status IN (%s, %s, %s)
                 AND completed_at < %s",
                self::STATUS_COMPLETED,
                self::STATUS_FAILED,
                self::STATUS_CANCELLED,
                $cutoff_date
            )
        );
        
        return $deleted === false ? 0 : $deleted;
    }

    /**
     * Update job status.
     *
     * @since    1.0.0
     * @param    int       $job_id    Job ID.
     * @param    string    $status    New status.
     * @return   bool                 Success status.
     */
    private function update_job_status(int $job_id, string $status): bool {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_name,
            array('status' => $status),
            array('id' => $job_id),
            array('%s'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Generate batch ID.
     *
     * @since    1.0.0
     * @return   string    Batch ID.
     */
    private function generate_batch_id(): string {
        return 'batch_' . time() . '_' . wp_generate_password(8, false);
    }

    /**
     * Store batch information.
     *
     * @since    1.0.0
     * @param    string    $batch_id      Batch ID.
     * @param    int       $total_jobs    Total jobs in batch.
     */
    private function store_batch_info(string $batch_id, int $total_jobs) {
        set_transient('aapi_batch_' . $batch_id, array(
            'total_jobs' => $total_jobs,
            'created_at' => current_time('mysql'),
        ), DAY_IN_SECONDS);
    }

    /**
     * Calculate retry time with exponential backoff.
     *
     * @since    1.0.0
     * @param    int    $attempts    Number of attempts.
     * @return   string              Retry timestamp.
     */
    private function calculate_retry_time(int $attempts): string {
        $delay = min(pow(2, $attempts) * 60, 3600); // Max 1 hour
        return date('Y-m-d H:i:s', time() + $delay);
    }

    /**
     * Check if queue is processing.
     *
     * @since    1.0.0
     * @return   bool    Processing status.
     */
    private function is_processing(): bool {
        $flag = get_transient('aapi_queue_processing');
        
        // Check if flag is stale (older than 5 minutes)
        if ($flag && $flag < time() - 300) {
            delete_transient('aapi_queue_processing');
            return false;
        }
        
        return $flag !== false;
    }

    /**
     * Set processing flag.
     *
     * @since    1.0.0
     */
    private function set_processing_flag() {
        set_transient('aapi_queue_processing', time(), 300); // 5 minutes
    }

    /**
     * Clear processing flag.
     *
     * @since    1.0.0
     */
    private function clear_processing_flag() {
        delete_transient('aapi_queue_processing');
    }

    /**
     * Check if should stop processing.
     *
     * @since    1.0.0
     * @return   bool    Whether to stop.
     */
    private function should_stop_processing(): bool {
        // Check memory usage
        if (memory_get_usage(true) > 0.9 * wp_convert_hr_to_bytes(WP_MEMORY_LIMIT)) {
            return true;
        }
        
        // Check execution time
        if (time() - $_SERVER['REQUEST_TIME'] > $this->job_timeout * 0.8) {
            return true;
        }
        
        // Check for stop signal
        if (get_transient('aapi_queue_stop')) {
            return true;
        }
        
        return false;
    }

    /**
     * Update progress tracking.
     *
     * @since    1.0.0
     * @param    string|null    $batch_id    Batch ID.
     * @param    array          $results     Current results.
     */
    private function update_progress(?string $batch_id, array $results) {
        if (!$batch_id) {
            return;
        }
        
        $this->progress[$batch_id] = array(
            'processed' => $results['processed'],
            'succeeded' => $results['succeeded'],
            'failed' => $results['failed'],
            'updated_at' => current_time('mysql'),
        );
        
        // Store progress
        set_transient('aapi_batch_progress_' . $batch_id, $this->progress[$batch_id], HOUR_IN_SECONDS);
    }

    /**
     * Get batch progress.
     *
     * @since    1.0.0
     * @param    string    $batch_id    Batch ID.
     * @return   array|null             Progress information or null.
     */
    public function get_batch_progress(string $batch_id): ?array {
        $progress = get_transient('aapi_batch_progress_' . $batch_id);
        return $progress !== false ? $progress : null;
    }

    /**
     * Maybe trigger immediate processing.
     *
     * @since    1.0.0
     */
    private function maybe_trigger_immediate_processing() {
        // Only trigger if not already processing
        if (!$this->is_processing()) {
            // Use WordPress cron to process immediately
            wp_schedule_single_event(time(), 'aapi_process_queue');
        }
    }

    /**
     * Create product post.
     * Placeholder - should be implemented properly.
     *
     * @since    1.0.0
     * @param    array    $product    Product data.
     * @return   int                  Post ID.
     */
    private function create_product_post(array $product): int {
        // This is a placeholder implementation
        // In real implementation, this would create/update the custom post type
        return 0;
    }

    /**
     * Update product post.
     * Placeholder - should be implemented properly.
     *
     * @since    1.0.0
     * @param    int      $post_id    Post ID.
     * @param    array    $product    Product data.
     */
    private function update_product_post(int $post_id, array $product) {
        // This is a placeholder implementation
        // In real implementation, this would update the custom post type
    }

    /**
     * Log error.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    private function log_error(string $message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AAPI Queue] ' . $message);
        }
    }
}