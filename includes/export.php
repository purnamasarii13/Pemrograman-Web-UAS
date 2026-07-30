<?php
function excel_download(string $filename, array $headers, array $rows): void
{
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "<table border='1'><thead><tr>";
    foreach ($headers as $h) echo '<th>' . htmlspecialchars((string)$h, ENT_QUOTES, 'UTF-8') . '</th>';
    echo "</tr></thead><tbody>";
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $cell) echo '<td>' . htmlspecialchars((string)$cell, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    exit;
}

function pdf_escape_text(string $text): string
{
    $text = str_replace(["\\", "(", ")", "\r"], ["\\\\", "\\(", "\\)", ""], $text);
    return $text;
}

function pdf_line_wrap(string $line, int $max = 105): array
{
    $line = trim(preg_replace('/\s+/', ' ', $line));
    if ($line === '') return [''];
    return explode("\n", wordwrap($line, $max, "\n", true));
}

function pdf_download(string $filename, string $title, array $headers, array $rows, array $meta = []): void
{
    $lines = [];
    $lines[] = strtoupper($title);
    foreach ($meta as $m) $lines[] = $m;
    $lines[] = '';
    $lines[] = implode(' | ', $headers);
    $lines[] = str_repeat('-', 120);
    foreach ($rows as $row) {
        $line = implode(' | ', array_map(fn($v) => (string)$v, $row));
        foreach (pdf_line_wrap($line) as $wrapped) $lines[] = $wrapped;
    }
    if (empty($rows)) $lines[] = 'Tidak ada data.';

    $chunks = array_chunk($lines, 46);
    $objects = [];
    $pageIds = [];
    $contentIds = [];
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
    $nextId = 4;
    foreach ($chunks as $chunk) {
        $pageId = $nextId++;
        $contentId = $nextId++;
        $pageIds[] = $pageId;
        $contentIds[] = $contentId;
        $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";
        $stream = "BT\n/F1 10 Tf\n40 800 Td\n12 TL\n";
        foreach ($chunk as $line) {
            $stream .= '(' . pdf_escape_text($line) . ") Tj\nT*\n";
        }
        $stream .= "ET\n";
        $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
    }
    $kids = implode(' ', array_map(fn($id) => $id . ' 0 R', $pageIds));
    $objects[2] = "<< /Type /Pages /Kids [{$kids}] /Count " . count($pageIds) . " >>";
    ksort($objects);

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $id => $body) {
        $offsets[$id] = strlen($pdf);
        $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
    }
    $xrefPos = strlen($pdf);
    $maxId = max(array_keys($objects));
    $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $maxId; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
    }
    $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $pdf;
    exit;
}
