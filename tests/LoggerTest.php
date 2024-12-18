<?php

use CruiseCritic\KLogger\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerTest extends PHPUnit\Framework\TestCase
{
    private string $logPath;
    private Logger $logger;
    private Logger $errLogger;

    public function setUp(): void
    {
        $this->logPath = __DIR__.'/logs';
        $this->logger = new Logger($this->logPath, LogLevel::DEBUG, ['flushFrequency' => 1]);
        $this->errLogger = new Logger($this->logPath, LogLevel::ERROR, [
            'extension' => 'log',
            'prefix' => 'error_',
            'flushFrequency' => 1
        ]);
    }

    public function testImplementsPsr3LoggerInterface(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->logger);
    }

    public function testAcceptsExtension(): void
    {
        $this->assertStringEndsWith('.log', $this->errLogger->getLogFilePath());
    }

    public function testAcceptsPrefix(): void
    {
        $filename = basename($this->errLogger->getLogFilePath());
        $this->assertStringStartsWith('error_', $filename);
    }

    public function testWritesBasicLogs(): void
    {
        $this->logger->log(LogLevel::DEBUG, 'This is a test');
        $this->errLogger->log(LogLevel::ERROR, 'This is a test');

        $this->assertTrue(file_exists($this->errLogger->getLogFilePath()));
        $this->assertTrue(file_exists($this->logger->getLogFilePath()));

        $this->assertLastLineEquals($this->logger);
        $this->assertLastLineEquals($this->errLogger);
    }


    public function assertLastLineEquals(Logger $logr): void
    {
        $this->assertEquals($logr->getLastLogLine(), $this->getLastLine($logr->getLogFilePath()));
    }

    public function assertLastLineNotEquals(Logger $logr): void
    {
        $this->assertNotEquals($logr->getLastLogLine(), $this->getLastLine($logr->getLogFilePath()));
    }

    private function getLastLine(string $filename): string
    {
        $size = filesize($filename);
        $fp = fopen($filename, 'r');
        $pos = -2; // start from second to last char
        $t = ' ';

        while ($t != "\n") {
            fseek($fp, $pos, SEEK_END);
            $t = fgetc($fp);
            $pos = $pos - 1;
            if ($size + $pos < -1) {
                rewind($fp);
                break;
            }
        }

        $t = fgets($fp);
        fclose($fp);

        return trim($t);
    }

    public function tearDown(): void {
        @unlink($this->logger->getLogFilePath());
        @unlink($this->errLogger->getLogFilePath());
    }
}
