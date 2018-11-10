<?php

namespace Domnikl\Statsd;

/**
 * the statsd client
 */
class TelegrafClient extends Client
{
    /**
     * format tags for the telegraf message
     *
     * @param string $key
     * @param int $value
     * @param string $type
     * @param int $sampleRate
     * @param array $tags
     */
    protected function convertTagsForMessage($tags = []) {
        $tagArray = [];

        if (!empty($tags)) {
            $tagArray = [];
            foreach($tags as $key => $value) {
              $tagArray[] = ($key . '=' . $value);
            }
        }

        return $tagArray;
    }

    /**
     * actually sends a message to to the daemon and returns the sent message
     *
     * @param string $key
     * @param int $value
     * @param string $type
     * @param int $sampleRate
     * @param array $tags
     */
    protected function send($key, $value, $type, $sampleRate, $tags = [])
    {
        if ($sampleRate < 1 && mt_rand() / mt_getrandmax() > $sampleRate) {
            return;
        }

        if (strlen($this->namespace) !== 0) {
            $key = $this->namespace . '.' . $key;
        }

        $tagArray = $this->convertTagsForMessage($tags);

        if (!empty($tagArray)) {
            $message = $key .','. join(',', $tagArray) . ':' . $value . '|' . $type;
        } else {
            $message = $key . ':' . $value . '|' . $type;
        }

        // overwrite sampleRate if all metrics should be sampled
        if ($this->sampleRateAllMetrics < 1) {
            $sampleRate = $this->sampleRateAllMetrics;
        }

        if ($sampleRate < 1) {
            $sampledData = $message . '|@' . $sampleRate;
        } else {
            $sampledData = $message;
        }

        if (!$this->isBatch) {
            $this->connection->send($sampledData);
        } else {
            $this->batch[] = $sampledData;
        }
    }
}
