<?php
namespace Stevenscg\StatsD;

/**
 * Sends statistics to the statsd daemon over UDP
 *
 * This class generally follows the official php-example from Etsy, but moves
 * the configuration options into the main class.
 *
 * The default configuration parameters can be overridden by environment variables
 * or submitted configuration parameters (see config method below).
 *
 * Environment variables
 * STATSD_ENABLED true or false.
 * STATSD_PREFIX  Optional metrics prefix.
 * STATSD_ADDR    IP:PORT of the statsd server.
 *
 *
 * @author Etsy https://github.com/etsy/statsd
 * @author Chris Stevens
 * @link https://github.com/stevenscg/statsd-php
 **/

class StatsD
{
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
    public static $host = '127.0.0.1';

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
    public static function config($params = [])
    {
        // Handle environment variables.
        $addrPieces = explode(':', getenv('STATSD_ADDR'));
        if (!empty($addrPieces[0])) {
            $host = $addrPieces[0];
        }
        if (!empty($addrPieces[1])) {
            $port = $addrPieces[1];
        }

        $enabled = getenv('STATSD_ENABLED');
        $prefix  = getenv('STATSD_PREFIX');

        if (!is_bool($enabled)) {
            $enabled = ($enabled == 'false') ? false : boolval($enabled);
        }

        // Handle submitted parameters.
        foreach (['enabled', 'prefix', 'host', 'port'] as $key) {
            if (array_key_exists($key, $params)) {
                $$key = $params[$key];
            }
        }

        if (!$enabled) {
            StatsD::$enabled = false;
        }
        if (!empty($prefix)) {
            StatsD::$prefix = $prefix;
        }
        if (!empty($host)) {
            StatsD::$host = $host;
        }
        if (!empty($port)) {
            StatsD::$port = $port;
        }
    }

    /**
     * Sets one or more timing values
     *
     * @param string|array $stats The metric(s) to set.
     * @param float $time The elapsed time (ms) to log
     **/
    public static function timing($stats, $time)
    {
        StatsD::updateStats($stats, $time, 1, 'ms');
    }

    /**
     * Sets one or more gauges to a value
     *
     * @param string|array $stats The metric(s) to set.
     * @param float $value The value for the stats.
     **/
    public static function gauge($stats, $value)
    {
        StatsD::updateStats($stats, $value, 1, 'g');
    }

    /**
     * A "Set" is a count of unique events.
     * This data type acts like a counter, but supports counting
     * of unique occurences of values between flushes. The backend
     * receives the number of unique events that happened since
     * the last flush.
     *
     * The reference use case involved tracking the number of active
     * and logged in users by sending the current userId of a user
     * with each request with a key of "uniques" (or similar).
     *
     * @param string|array $stats The metric(s) to set.
     * @param float $value The value for the stats.
     **/
    public static function set($stats, $value)
    {
        StatsD::updateStats($stats, $value, 1, 's');
    }

    /**
     * Increments one or more stats counters
     *
     * @param string|array $stats The metric(s) to increment.
     * @param int|1 $delta The amount to increment/decrement.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
    public static function increment($stats, $delta = 1, $sampleRate = 1)
    {
        StatsD::updateStats($stats, $delta, $sampleRate, 'c');
    }

    /**
     * Decrements one or more stats counters.
     *
     * @param string|array $stats The metric(s) to decrement.
     * @param int|1 $delta The amount to increment/decrement.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @return boolean
     **/
    public static function decrement($stats, $delta = -1, $sampleRate = 1)
    {
        StatsD::updateStats($stats, $delta, $sampleRate, 'c');
    }

    /**
     * Updates one or more stats.
     *
     * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
     * @param int|1 $delta The amount to increment/decrement each metric by.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     * @param string|c $metric The metric type ("c" for count, "ms" for timing, "g" for gauge, "s" for set)
     * @return boolean
     **/
    protected static function updateStats($stats, $delta = 1, $sampleRate = 1, $metric = 'c')
    {
        if (!is_array($stats)) {
            $stats = [$stats];
        }
        $data = [];
        foreach ($stats as $stat) {
            $data[$stat] = "$delta|$metric";
        }

        StatsD::send($data, $sampleRate);
    }

    /*
     * Squirt the metrics over UDP
     **/
    protected static function send($data, $sampleRate=1)
    {
        // Check that sending is actually enabled
        if (self::$enabled === false) { return; }

        // Sampling
        $sampledData = [];

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
