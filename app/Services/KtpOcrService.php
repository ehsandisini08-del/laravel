<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class KtpOcrService
{
    public function extractNik(string $imagePath): ?string
    {
        try {
            $ocr = new TesseractOCR($imagePath);
            $ocr->psm(6);
            $ocr->lang('ind');

            $text = $ocr->run();

            return $this->parseNik($text);
        } catch (\Throwable $e) {
            Log::warning('KTP OCR failed', [
                'error' => $e->getMessage(),
                'image' => $imagePath,
            ]);

            return null;
        }
    }

    protected function parseNik(string $text): ?string
    {
        $text = preg_replace('/[^0-9\n]/', ' ', $text);

        if (preg_match('/\b(\d{16})\b/', $text, $matches)) {
            return $matches[1];
        }

        $digits = preg_replace('/\D/', '', $text);

        if (preg_match('/(\d{16})/', $digits, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
