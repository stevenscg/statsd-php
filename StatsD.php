<?php
/**
 * Sends statistics to the stats daemon over UDP
 *
 * This class generally follows the official php-example from Etsy, but moves
 * the configuration options into the main class.
 *
 * @author Etsy https://github.com/etsy/statsd
 * @author Chris Stevens
 * @see https://github.com/etsy/statsd/blob/master/examples/php-example.php
 * @link https://github.com/stevenscg/statsd-php
 **/
class StatsD {
    /**
     * Sending Enabled Flag
     *
     * @var boolean
     */
    public static $enabled = true;

    /**
     * Default prefix (e.g. myapp)
     *
     * In graphite, this would show up as stats.myapp.
     *
     * @var string
     */
    public static $prefix = null;

    /**
     * Default Host
     *
     * @var string
     */
    public static $host = 'localhost';

    /**
     * Default Port
     *
     * @var integer
     */
    public static $port = 8125;



    /**
     * Set configuration values
     *
     **/
    public static function config($params = array()) {
        extract($params);

        if (!empty($host)) {
            StatsD::$host = $host;
        }
        if (!empty($port)) {
            StatsD::$port = $port;
        }
        if (!empty($prefix)) {
            StatsD::$prefix = $prefix;
        }
        if (isset($enabled)) {
            StatsD::$enabled = $enabled;
        }
    }

    /**
     * Log timing information
     *
     * @param string $stats The metric to in log timing info for.
     * @param float $time The elapsed time (ms) to log
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     **/
    public static function timing($stat, $time, $sampleRate=1) {
        StatsD::send(array($stat => "$time|ms"), $sampleRate);
    }

    /**
     * Increments one or more stats counters
     *
     * @param string|array $stats The metric(s) to increment.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
    public static function increment($stats, $sampleRate=1) {
        StatsD::updateStats($stats, 1, $sampleRate);
    }

    /**
     * Decrements one or more stats counters.
     *
     * @param string|array $stats The metric(s) to decrement.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
    public static function decrement($stats, $sampleRate=1) {
        StatsD::updateStats($stats, -1, $sampleRate);
    }

    /**
     * Updates one or more stats counters by arbitrary amounts.
     *
     * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
     * @param int|1 $delta The amount to increment/decrement each metric by.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
    public static function updateStats($stats, $delta=1, $sampleRate=1) {
        if (!is_array($stats)) { $stats = array($stats); }
        $data = array();
        foreach($stats as $stat) {
            $data[$stat] = "$delta|c";
        }

        StatsD::send($data, $sampleRate);
    }

    /*
     * Squirt the metrics over UDP
     **/
    public static function send($data, $sampleRate=1) {
        // check that sending is actually enabled
        if (self::$enabled === false) { return; }

        // sampling
        $sampledData = array();

        if ($sampleRate < 1) {
            foreach ($data as $stat => $value) {
                if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
                    $sampledData[$stat] = "$value|@$sampleRate";
                }
            }
        } else {
            $sampledData = $data;
        }

        if (empty($sampledData)) { return; }

        // Wrap this in a try/catch - failures in any of this should be silently ignored
        try {
            $host = self::$host;
            $fp = fsockopen("udp://$host", self::$port, $errno, $errstr);
            if (! $fp) { return; }
            foreach ($sampledData as $stat => $value) {
                if (!empty(self::$prefix)) {
                    $stat = self::$prefix.'.'.$stat;
                }
                fwrite($fp, "$stat:$value");
            }
            fclose($fp);
        } catch (Exception $e) {
        }
    }
}
