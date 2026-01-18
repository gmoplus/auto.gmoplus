<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.0
 *  LICENSE: FL0255RKH690 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmoplus.com
 *  FILE: rlListingRefresh.class.php
 *  
 *  GMO Plus Listing Refresh System - TASK 8 & 9
 *  İlan Yenileme Sistemi - Paket sayılarını etkilemeden yenileme
 *  
 ******************************************************************************/

/**
 * Listing Refresh Manager Class
 * 
 * TASK 8: Vasıta ilanları - 3 günde 1 yenileme
 * TASK 9: Kariyer ilanları - 7 günde 2 yenileme
 * 
 * Bu class mevcut sistemle uyumlu çalışır ve package sayılarını etkilemez
 */
class rlListingRefresh
{
    /**
     * Refresh rules per listing type
     * @var array
     */
    private $refreshRules = [
        'vasita' => [
            'period' => 3,      // days
            'limit' => 1,       // max refreshes per period
            'unit' => 'days'
        ],
        'kariyer' => [
            'period' => 7,      // days  
            'limit' => 2,       // max refreshes per period
            'unit' => 'days'
        ],
        'realestate' => [
            'period' => 3,      // days
            'limit' => 1,       // max refreshes per period
            'unit' => 'days'
        ],
        'online' => [
            'period' => 7,      // days
            'limit' => 1,       // max refreshes per period
            'unit' => 'days'
        ],
        'general' => [
            'period' => 7,      // days
            'limit' => 1,       // max refreshes per period
            'unit' => 'days'
        ]
    ];

    /**
     * Database connection
     * @var object
     */
    private $rlDb;

    /**
     * Config reference
     * @var array
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $rlDb, $config;
        
        // Initialize database connection
        if ($rlDb && is_object($rlDb)) {
            $this->rlDb = $rlDb;
        } else {
            // Fallback: Create PDO connection manually
            try {
                $dsn = "mysql:host=" . RL_DBHOST . ";dbname=" . RL_DBNAME . ";charset=utf8mb4";
                $this->rlDb = new PDO($dsn, RL_DBUSER, RL_DBPASS);
                $this->rlDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (Exception $e) {
                error_log("rlListingRefresh DB Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        
        $this->config = $config ?: [];
        
        // Load refresh rules from config/database
        $this->loadRefreshRules();
    }

    /**
     * Load refresh rules from config or database
     */
    private function loadRefreshRules()
    {
        global $config;
        
        // Override with config values if they exist
        if (isset($config['listing_refresh_vasita_days'])) {
            $this->refreshRules['vasita']['period'] = (int)$config['listing_refresh_vasita_days'];
            $this->refreshRules['vasita']['limit'] = (int)$config['listing_refresh_vasita_limit'];
        }
        
        if (isset($config['listing_refresh_kariyer_days'])) {
            $this->refreshRules['kariyer']['period'] = (int)$config['listing_refresh_kariyer_days'];
            $this->refreshRules['kariyer']['limit'] = (int)$config['listing_refresh_kariyer_limit'];
        }
        
        if (isset($config['listing_refresh_realestate_days'])) {
            $this->refreshRules['realestate']['period'] = (int)$config['listing_refresh_realestate_days'];
            $this->refreshRules['realestate']['limit'] = (int)$config['listing_refresh_realestate_limit'];
        }
    }

    /**
     * Check if listing can be refreshed
     * 
     * @param int $listing_id
     * @param string $listing_type (vasita, kariyer, realestate, etc.)
     * @return array ['allowed' => bool, 'message' => string, 'next_date' => string]
     */
    public function canRefresh($listing_id, $listing_type = 'general')
    {
        // System enabled check
        if (!$this->isRefreshEnabled()) {
            return [
                'allowed' => false,
                'message' => 'Refresh system is disabled',
                'next_date' => null
            ];
        }

        // Get listing info
        $listing = $this->getListingInfo($listing_id);
        if (!$listing) {
            return [
                'allowed' => false,
                'message' => 'Listing not found',
                'next_date' => null
            ];
        }

        // Check listing status
        if ($listing['Status'] !== 'active') {
            return [
                'allowed' => false,
                'message' => 'Only active listings can be refreshed',
                'next_date' => null
            ];
        }

        // Get rules for this listing type
        $rules = $this->getRefreshRules($listing_type);
        
        // Get refresh history within the period
        $history = $this->getRefreshHistory($listing_id, $rules['period']);
        
        // Check if limit exceeded
        if (count($history) >= $rules['limit']) {
            $nextDate = $this->calculateNextRefreshDate($listing_id, $listing_type);
            return [
                'allowed' => false,
                'message' => "Refresh limit exceeded. Next refresh: {$nextDate}",
                'next_date' => $nextDate,
                'remaining' => 0
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Refresh allowed',
            'next_date' => null,
            'remaining' => $rules['limit'] - count($history)
        ];
    }

    /**
     * Perform listing refresh
     * ⚠️ CRITICAL: Bu method package sayılarını DEĞİŞTİRMEZ!
     * 
     * @param int $listing_id
     * @param string $listing_type
     * @return array ['success' => bool, 'message' => string]
     */
    public function refreshListing($listing_id, $listing_type = 'general')
    {
        global $reefless;

        // Check if refresh is allowed
        $canRefresh = $this->canRefresh($listing_id, $listing_type);
        if (!$canRefresh['allowed']) {
            return [
                'success' => false,
                'message' => $canRefresh['message']
            ];
        }

        // Get listing info
        $listing = $this->getListingInfo($listing_id);
        if (!$listing) {
            return [
                'success' => false,
                'message' => 'Listing not found'
            ];
        }

        try {
            // Start transaction for safety
            if (method_exists($this->rlDb, 'query')) {
                $this->rlDb->query("START TRANSACTION");
            } else {
                $this->rlDb->beginTransaction();
            }

            // ⚠️ CRITICAL: Only update Pay_date, DO NOT touch package counts
            $updateQuery = "UPDATE `" . RL_DBPREFIX . "listings` SET 
                           `Pay_date` = NOW(),
                           `Cron_notified` = '0'
                           WHERE `ID` = {$listing_id}";
            
            if (method_exists($this->rlDb, 'query')) {
                // Flynax rlDb method
                $result = $this->rlDb->query($updateQuery);
            } else {
                // PDO fallback
                $result = $this->rlDb->exec($updateQuery);
            }
            
            if ($result) {
                // Log refresh action
                $this->logRefreshAction($listing_id, $listing['Account_ID'], $listing_type, $listing['Category_ID']);
                
                // Commit transaction
                if (method_exists($this->rlDb, 'query')) {
                    $this->rlDb->query("COMMIT");
                } else {
                    $this->rlDb->commit();
                }
                
                // Clear cache if needed
                $this->clearListingCache($listing_id);
                
                return [
                    'success' => true,
                    'message' => 'Listing refreshed successfully'
                ];
            } else {
                if (method_exists($this->rlDb, 'query')) {
                    $this->rlDb->query("ROLLBACK");
                } else {
                    $this->rlDb->rollBack();
                }
                return [
                    'success' => false,
                    'message' => 'Database error during refresh'
                ];
            }

        } catch (Exception $e) {
            if (method_exists($this->rlDb, 'query')) {
                $this->rlDb->query("ROLLBACK");
            } else {
                $this->rlDb->rollBack();
            }
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get refresh statistics for account
     * 
     * @param int $account_id
     * @param string $listing_type
     * @return array
     */
    public function getRefreshStats($account_id, $listing_type = null)
    {
        $whereClause = "`Account_ID` = {$account_id}";
        if ($listing_type) {
            $whereClause .= " AND `Listing_Type` = '{$listing_type}'";
        }
        $whereClause .= " AND `Status` = 'success'";
        
        $sql = "SELECT 
                    COUNT(*) as total_refreshes,
                    MAX(`Refresh_Date`) as last_refresh,
                    `Listing_Type`,
                    COUNT(CASE WHEN DATE(`Refresh_Date`) = CURDATE() THEN 1 END) as today_refreshes
                FROM `{db_prefix}listing_refresh_history` 
                WHERE {$whereClause}
                GROUP BY `Listing_Type`";
        
        return $this->rlDb->getAll($sql);
    }

    /**
     * Get remaining refresh attempts for a listing
     * 
     * @param int $listing_id
     * @param string $listing_type
     * @return int
     */
    public function getRemainingRefreshes($listing_id, $listing_type = 'general')
    {
        $rules = $this->getRefreshRules($listing_type);
        $history = $this->getRefreshHistory($listing_id, $rules['period']);
        
        return max(0, $rules['limit'] - count($history));
    }

    // ================================
    // PRIVATE METHODS
    // ================================

    /**
     * Check if refresh system is enabled
     */
    private function isRefreshEnabled()
    {
        return isset($this->config['listing_refresh_enabled']) 
               ? (bool)$this->config['listing_refresh_enabled'] 
               : true; // Default enabled
    }

    /**
     * Get listing information
     * 
     * @param int $listing_id
     * @return array|false
     */
    private function getListingInfo($listing_id)
    {
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "listings` WHERE `ID` = {$listing_id} AND `Status` != 'trash'";
        
        if (method_exists($this->rlDb, 'getRow')) {
            // Flynax rlDb method
            return $this->rlDb->getRow($sql);
        } else {
            // PDO fallback
            $stmt = $this->rlDb->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Get refresh rules for listing type
     * 
     * @param string $listing_type
     * @return array
     */
    private function getRefreshRules($listing_type)
    {
        return isset($this->refreshRules[$listing_type]) 
               ? $this->refreshRules[$listing_type] 
               : $this->refreshRules['general'];
    }

    /**
     * Get refresh history within period
     * 
     * @param int $listing_id
     * @param int $period_days
     * @return array
     */
    private function getRefreshHistory($listing_id, $period_days)
    {
        $sql = "SELECT * FROM `" . RL_DBPREFIX . "listing_refresh_history` 
                WHERE `Listing_ID` = {$listing_id} 
                AND `Status` = 'success'
                AND `Refresh_Date` >= DATE_SUB(NOW(), INTERVAL {$period_days} DAY)
                ORDER BY `Refresh_Date` DESC";
        
        if (method_exists($this->rlDb, 'getAll')) {
            // Flynax rlDb method
            return $this->rlDb->getAll($sql);
        } else {
            // PDO fallback
            $stmt = $this->rlDb->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Calculate next available refresh date
     * 
     * @param int $listing_id
     * @param string $listing_type
     * @return string
     */
    private function calculateNextRefreshDate($listing_id, $listing_type)
    {
        $rules = $this->getRefreshRules($listing_type);
        $history = $this->getRefreshHistory($listing_id, $rules['period']);
        
        if (empty($history)) {
            return date('Y-m-d H:i:s'); // Can refresh now
        }
        
        // Get oldest refresh in current period
        $oldestRefresh = end($history);
        $nextDate = date('Y-m-d H:i:s', strtotime($oldestRefresh['Refresh_Date'] . ' + ' . $rules['period'] . ' days'));
        
        return $nextDate;
    }

    /**
     * Log refresh action to database
     * 
     * @param int $listing_id
     * @param int $account_id
     * @param string $listing_type
     * @param int $category_id
     */
    private function logRefreshAction($listing_id, $account_id, $listing_type, $category_id)
    {
        global $reefless;
        
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : '';
        
        if (method_exists($this->rlDb, 'insertOne')) {
            // Flynax rlDb method
            $insert = [
                'Listing_ID' => $listing_id,
                'Account_ID' => $account_id,
                'Listing_Type' => $listing_type,
                'Category_ID' => $category_id,
                'Refresh_Date' => 'NOW()',
                'IP' => $ip,
                'User_Agent' => $user_agent,
                'Status' => 'success'
            ];
            $this->rlDb->insertOne($insert, 'listing_refresh_history');
        } else {
            // PDO fallback
            $sql = "INSERT INTO `" . RL_DBPREFIX . "listing_refresh_history` 
                    (`Listing_ID`, `Account_ID`, `Listing_Type`, `Category_ID`, `Refresh_Date`, `IP`, `User_Agent`, `Status`) 
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, 'success')";
            
            $stmt = $this->rlDb->prepare($sql);
            $stmt->execute([
                $listing_id, 
                $account_id, 
                $listing_type, 
                $category_id, 
                $ip, 
                $user_agent
            ]);
        }
    }

    /**
     * Clear listing cache if needed
     * 
     * @param int $listing_id
     */
    private function clearListingCache($listing_id)
    {
        global $rlCache;
        
        if (isset($rlCache)) {
            // Clear relevant cache entries
            $rlCache->delete('listing_' . $listing_id);
            $rlCache->delete('listings_cache');
        }
    }

    /**
     * Setup database tables (for installation/update)
     * 
     * @return bool
     */
    public function setupDatabase()
    {
        try {
            // Check if tables exist
            $tables = $this->rlDb->getAll("SHOW TABLES LIKE '%listing_refresh%'");
            
            if (empty($tables)) {
                // Execute setup SQL
                $sql_file = RL_ROOT . 'install/refresh_system_setup.sql';
                if (file_exists($sql_file)) {
                    $sql_content = file_get_contents($sql_file);
                    // Replace prefix
                    $sql_content = str_replace('{db_prefix}', RL_DBPREFIX, $sql_content);
                    
                    // Execute SQL (split by semicolons and execute each statement)
                    $statements = explode(';', $sql_content);
                    foreach ($statements as $statement) {
                        $statement = trim($statement);
                        if (!empty($statement) && !str_starts_with($statement, '--')) {
                            $this->rlDb->query($statement);
                        }
                    }
                    
                    return true;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Listing Refresh Setup Error: " . $e->getMessage());
            return false;
        }
    }
}

// Global instance (compatible with Flynax patterns)
if (!isset($GLOBALS['rlListingRefresh'])) {
    $GLOBALS['rlListingRefresh'] = new rlListingRefresh();
} 