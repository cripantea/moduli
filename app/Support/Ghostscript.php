<?php

namespace App\Support;

use RuntimeException;

class Ghostscript
{
    private static ?string $binary = null;

    public static function binary(): string
    {
        if (self::$binary !== null) {
            return self::$binary;
        }

        foreach (['/usr/bin/gs', '/usr/local/bin/gs', '/opt/homebrew/bin/gs'] as $candidate) {
            if (is_executable($candidate)) {
                return self::$binary = $candidate;
            }
        }

        exec('which gs 2>/dev/null', $out, $code);
        if ($code === 0 && ! empty($out[0])) {
            return self::$binary = trim($out[0]);
        }

        throw new RuntimeException('Ghostscript non trovato sul server.');
    }

    /**
     * Esegue gs con gli argomenti dati (già separati, verranno quotati) e ritorna lo stdout.
     *
     * @param  array<int, string>  $args
     */
    public static function run(array $args): string
    {
        $cmd = escapeshellarg(self::binary()) . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>/dev/null';
        exec($cmd, $out, $code);

        if ($code !== 0) {
            throw new RuntimeException("Ghostscript ha restituito un errore (exit {$code}).");
        }

        return implode("\n", $out);
    }
}
