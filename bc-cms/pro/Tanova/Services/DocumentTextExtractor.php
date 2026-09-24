<?php

namespace Pro\Tanova\Services;

use RuntimeException;

/**
 * Extracts plain text from an uploaded document.
 *
 * WHY NO LIBRARY: the obvious choices (smalot/pdfparser, phpoffice/phpword) both
 * require ext-bcmath, which is installed on neither the build machine nor the
 * production server. Forcing a PHP extension onto a box running five other apps is
 * a worse trade than the focused extractors below, which need only ext-zip and
 * ext-zlib — both already present.
 *
 * SUPPORTED
 *   .docx  — full text, paragraph breaks preserved
 *   .txt / .md — as-is
 *   .pdf   — text-based PDFs using Flate-compressed content streams (what Word,
 *            LibreOffice, Canva and dompdf produce)
 *
 * NOT SUPPORTED
 *   Scanned or image-only PDFs (no text layer to read — needs OCR)
 *   .doc (the pre-2007 binary format)
 *   PDFs using uncommon font encodings may return partial text
 *
 * Callers must treat the result as a best effort and let a human check it, which
 * is why the importer creates a *draft* itinerary rather than a published one.
 */
class DocumentTextExtractor
{
    public const SUPPORTED = ['pdf', 'docx', 'txt', 'md'];

    public function extract(string $path, string $extension): string
    {
        $extension = strtolower($extension);

        return match ($extension) {
            'docx'       => $this->fromDocx($path),
            'pdf'        => $this->fromPdf($path),
            'txt', 'md'  => (string) file_get_contents($path),
            default      => throw new RuntimeException(__('Unsupported file type: :ext', ['ext' => $extension])),
        };
    }

    /**
     * DOCX is a ZIP containing word/document.xml. Paragraph and break tags become
     * newlines before the markup is stripped, so day headings stay on their own
     * lines — which is what the importer keys off.
     */
    private function fromDocx(string $path): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new RuntimeException(__('The zip extension is required to read .docx files.'));
        }

        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException(__('That .docx file could not be opened.'));
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException(__('That .docx file has no readable document body.'));
        }

        // Tabs, line breaks and paragraph ends → whitespace we can rely on.
        $xml = str_replace(['<w:tab/>', '<w:br/>', '</w:p>'], ["\t", "\n", "\n"], $xml);

        $text = strip_tags($xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->tidy($text);
    }

    /**
     * Pulls text out of a PDF's content streams.
     *
     * Streams are usually Flate-compressed; text sits in the arguments of the Tj
     * (show string) and TJ (show array) operators. Anything we cannot inflate is
     * skipped rather than aborting the whole import — a partly-read document is
     * more useful than an error.
     */
    private function fromPdf(string $path): string
    {
        $raw = (string) file_get_contents($path);

        if (!str_starts_with($raw, '%PDF-')) {
            throw new RuntimeException(__('That file is not a PDF.'));
        }

        $chunks = [];

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $raw, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);

                if ($decoded === false) {
                    $decoded = @gzinflate($stream);
                }

                // Some producers leave content streams uncompressed.
                if ($decoded === false) {
                    $decoded = str_contains($stream, 'Tj') || str_contains($stream, 'TJ') ? $stream : null;
                }

                if ($decoded !== null && $decoded !== false) {
                    $chunks[] = $decoded;
                }
            }
        }

        if (!$chunks) {
            throw new RuntimeException(__('No readable text found — this may be a scanned PDF, which needs OCR.'));
        }

        $text = '';

        foreach ($chunks as $chunk) {
            $text .= $this->textFromContentStream($chunk) . "\n";
        }

        $text = $this->tidy($text);

        if (trim($text) === '') {
            throw new RuntimeException(__('No readable text found — this may be a scanned PDF, which needs OCR.'));
        }

        return $text;
    }

    /** Read the string arguments of Tj / TJ operators out of one content stream. */
    private function textFromContentStream(string $stream): string
    {
        $out = '';

        // TJ arrays: [(Hello) -250 (World)] TJ — kerning numbers are dropped.
        if (preg_match_all('/\[(.*?)\]\s*TJ/s', $stream, $arrays)) {
            foreach ($arrays[1] as $array) {
                if (preg_match_all('/\(((?:\\\\.|[^\\\\()])*)\)/s', $array, $parts)) {
                    $out .= implode('', array_map([$this, 'unescapePdfString'], $parts[1])) . "\n";
                }
            }
        }

        // Plain Tj: (Hello) Tj
        if (preg_match_all('/\(((?:\\\\.|[^\\\\()])*)\)\s*Tj/s', $stream, $singles)) {
            foreach ($singles[1] as $single) {
                $out .= $this->unescapePdfString($single) . "\n";
            }
        }

        return $out;
    }

    private function unescapePdfString(string $s): string
    {
        return strtr($s, [
            '\\n' => "\n", '\\r' => "\r", '\\t' => "\t",
            '\\(' => '(',  '\\)' => ')',  '\\\\' => '\\',
        ]);
    }

    /** Normalise whitespace without destroying the line structure we rely on. */
    private function tidy(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim((string) $text);
    }
}
