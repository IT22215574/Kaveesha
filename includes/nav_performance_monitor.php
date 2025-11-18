<?php
// Performance monitoring for navigation components
// Add this to pages that include navigation to monitor performance

class NavPerformanceMonitor {
    private static $timers = [];
    
    public static function startTimer($name) {
        self::$timers[$name] = microtime(true);
    }
    
    public static function endTimer($name) {
        if (isset(self::$timers[$name])) {
            $elapsed = microtime(true) - self::$timers[$name];
            error_log("Navigation Performance - {$name}: " . number_format($elapsed * 1000, 2) . "ms");
            return $elapsed;
        }
        return null;
    }
    
    public static function logDatabaseQuery($query, $params = []) {
        $start = microtime(true);
        return function() use ($query, $start) {
            $elapsed = microtime(true) - $start;
            if ($elapsed > 0.1) { // Log slow queries (> 100ms)
                error_log("Slow Navigation Query ({$elapsed}s): " . $query);
            }
        };
    }
}

// Usage in navigation files:
// NavPerformanceMonitor::startTimer('user_data_fetch');
// ... database query ...
// NavPerformanceMonitor::endTimer('user_data_fetch');
?>