<?php
declare(strict_types=1);

/**
 * Transforme un contenu texte/Markdown léger (CV, lettre) en :
 *  - HTML professionnel (pour aperçu / impression PDF)
 *  - document .docx (WordprocessingML via ZipArchive, sans dépendance)
 */
final class DocumentRenderer
{
    /** Parse le contenu en blocs : ['h1'|'h2'|'p'|'ul', valeur]. */
    public static function parse(string $content): array
    {
        $blocks = [];
        $lines = preg_split('/\r?\n/', trim($content)) ?: [];
        $list = [];
        $flush = static function () use (&$list, &$blocks): void {
            if ($list) { $blocks[] = ['ul', $list]; $list = []; }
        };

        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') { $flush(); continue; }
            if (preg_match('/^#\s+(.*)/', $t, $m)) { $flush(); $blocks[] = ['h1', $m[1]]; }
            elseif (preg_match('/^##\s+(.*)/', $t, $m)) { $flush(); $blocks[] = ['h2', $m[1]]; }
            elseif (preg_match('/^###\s+(.*)/', $t, $m)) { $flush(); $blocks[] = ['h2', $m[1]]; }
            elseif (preg_match('/^[-*]\s+(.*)/', $t, $m)) { $list[] = $m[1]; }
            else { $flush(); $blocks[] = ['p', $t]; }
        }
        $flush();
        return $blocks;
    }

    /** Découpe le texte en segments [texte, gras?] à partir des **...**. */
    private static function inline(string $text): array
    {
        $parts = preg_split('/(\*\*.+?\*\*)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $out = [];
        foreach ($parts as $p) {
            if (preg_match('/^\*\*(.+?)\*\*$/u', $p, $m)) {
                $out[] = [$m[1], true];
            } else {
                $out[] = [str_replace(['**', '*'], '', $p), false];
            }
        }
        return $out ?: [[$text, false]];
    }

    // ---------------------------------------------------------------- HTML
    public static function toHtml(string $content, string $title, string $subtitle = ''): string
    {
        $blocks = self::parse($content);
        $body = '';
        foreach ($blocks as [$type, $val]) {
            if ($type === 'h1') { $body .= '<h1>' . self::inlineHtml($val) . '</h1>'; }
            elseif ($type === 'h2') { $body .= '<h2>' . self::inlineHtml($val) . '</h2>'; }
            elseif ($type === 'ul') {
                $body .= '<ul>' . implode('', array_map(static fn ($li) => '<li>' . self::inlineHtml($li) . '</li>', $val)) . '</ul>';
            } else { $body .= '<p>' . self::inlineHtml($val) . '</p>'; }
        }

        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>{$safeTitle}</title>
<style>
  @page { margin: 2cm; }
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; color: #1E293B; line-height: 1.55; max-width: 800px; margin: 0 auto; padding: 40px 32px; }
  h1 { font-size: 26px; color: #0C4A6E; margin: 0 0 4px; letter-spacing: -.02em; }
  h1 + p em, .subtitle { color: #0369A1; font-style: italic; }
  h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .08em; color: #0369A1; border-bottom: 2px solid #E2E8F0; padding-bottom: 4px; margin: 22px 0 10px; }
  p { margin: 0 0 10px; }
  ul { margin: 0 0 12px; padding-left: 20px; }
  li { margin-bottom: 4px; }
  strong { color: #0C4A6E; }
  .doc-toolbar { position: fixed; top: 12px; right: 12px; display: flex; gap: 8px; }
  .doc-toolbar button { border: 0; background: #0369A1; color: #fff; padding: 8px 14px; border-radius: 8px; font: inherit; cursor: pointer; }
  @media print { .doc-toolbar { display: none; } body { padding: 0; } }
</style></head>
<body>
  <div class="doc-toolbar"><button onclick="window.print()">Enregistrer en PDF</button></div>
  {$body}
  <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
</body></html>
HTML;
    }

    private static function inlineHtml(string $text): string
    {
        $html = '';
        foreach (self::inline($text) as [$seg, $bold]) {
            $safe = htmlspecialchars($seg, ENT_QUOTES, 'UTF-8');
            $html .= $bold ? "<strong>{$safe}</strong>" : $safe;
        }
        return $html;
    }

    // ---------------------------------------------------------------- DOCX
    public static function toDocx(string $content, string $title): string
    {
        $blocks = self::parse($content);
        $paragraphs = '';
        foreach ($blocks as [$type, $val]) {
            if ($type === 'h1') { $paragraphs .= self::docxPara($val, 'Title'); }
            elseif ($type === 'h2') { $paragraphs .= self::docxPara($val, 'Heading1'); }
            elseif ($type === 'ul') {
                foreach ($val as $li) { $paragraphs .= self::docxPara('• ' . $li, 'ListBullet'); }
            } else { $paragraphs .= self::docxPara($val, 'Normal'); }
        }

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            . $paragraphs
            . '<w:sectPr><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>'
            . '</w:body></w:document>';

        return self::zipDocx($documentXml);
    }

    private static function docxPara(string $text, string $style): string
    {
        $runs = '';
        foreach (self::inline($text) as [$seg, $bold]) {
            $safe = self::xml($seg);
            $rpr = $bold ? '<w:rPr><w:b/></w:rPr>' : '';
            $runs .= "<w:r>{$rpr}<w:t xml:space=\"preserve\">{$safe}</w:t></w:r>";
        }
        return "<w:p><w:pPr><w:pStyle w:val=\"{$style}\"/></w:pPr>{$runs}</w:p>";
    }

    private static function xml(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function zipDocx(string $documentXml): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');

        $zip->addFromString('word/_rels/document.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');

        $zip->addFromString('word/styles.xml', self::stylesXml());
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);
        return $bytes;
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:styleId="Normal"><w:name w:val="Normal"/><w:pPr><w:spacing w:after="120"/></w:pPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:pPr><w:spacing w:after="60"/></w:pPr><w:rPr><w:b/><w:color w:val="0C4A6E"/><w:sz w:val="48"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="Heading 1"/><w:pPr><w:spacing w:before="240" w:after="80"/></w:pPr><w:rPr><w:b/><w:caps/><w:color w:val="0369A1"/><w:sz w:val="24"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="ListBullet"><w:name w:val="List Bullet"/><w:pPr><w:spacing w:after="40"/><w:ind w:left="360"/></w:pPr></w:style>'
            . '</w:styles>';
    }
}
