<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class MensagemChamadoSanitizer
{
    private const TAGS_PERMITIDAS = ['a', 'b', 'br', 'div', 'em', 'i', 'li', 'ol', 'p', 's', 'strong', 'u', 'ul'];

    private const TAGS_PERIGOSAS = ['iframe', 'object', 'script', 'style', 'svg', 'template'];

    public function sanitizar(string $html): string
    {
        $estadoAnterior = libxml_use_internal_errors(true);
        $documento = new DOMDocument('1.0', 'UTF-8');
        $documento->loadHTML(
            '<?xml encoding="UTF-8"><div id="mensagem-raiz">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($estadoAnterior);

        $raiz = $documento->getElementById('mensagem-raiz');
        if (! $raiz) {
            return e($html);
        }

        $this->sanitizarFilhos($raiz);
        $resultado = '';
        foreach ($raiz->childNodes as $filho) {
            $resultado .= $documento->saveHTML($filho);
        }

        return $resultado;
    }

    public function textoSimples(string $html): string
    {
        $texto = preg_replace('/<br\s*\/?\s*>|<\/(?:div|p|li)>/i', ' ', $html);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($texto ?? $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function sanitizarFilhos(DOMNode $pai): void
    {
        foreach (iterator_to_array($pai->childNodes) as $filho) {
            if (! $filho instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($filho->tagName);
            if (! in_array($tag, self::TAGS_PERMITIDAS, true)) {
                if (in_array($tag, self::TAGS_PERIGOSAS, true) || in_array($tag, ['img', 'input', 'video', 'audio'], true)) {
                    $pai->removeChild($filho);
                } else {
                    while ($filho->firstChild) {
                        $pai->insertBefore($filho->firstChild, $filho);
                    }
                    $pai->removeChild($filho);
                }

                continue;
            }

            if ($tag === 'a') {
                $href = trim($filho->getAttribute('href'));
                $esquema = strtolower(parse_url($href, PHP_URL_SCHEME) ?? '');
                if (! in_array($esquema, ['http', 'https', 'mailto'], true)) {
                    while ($filho->firstChild) {
                        $pai->insertBefore($filho->firstChild, $filho);
                    }
                    $pai->removeChild($filho);

                    continue;
                }

                while ($filho->attributes->length > 0) {
                    $filho->removeAttributeNode($filho->attributes->item(0));
                }
                $filho->setAttribute('href', $href);
                $filho->setAttribute('rel', 'noopener noreferrer');
            } else {
                while ($filho->attributes->length > 0) {
                    $filho->removeAttributeNode($filho->attributes->item(0));
                }
            }

            $this->sanitizarFilhos($filho);
        }
    }
}
