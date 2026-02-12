<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio para compilar plantillas MJML a HTML.
 *
 * PROPÓSITO:
 * Convierte plantillas MJML (Mailjet Markup Language) a HTML compatible
 * con clientes de email. MJML simplifica la creación de emails responsive
 * que funcionan en todos los clientes.
 *
 * ESTRATEGIA DE COMPILACIÓN:
 * 1. Intenta usar binario MJML local (/usr/local/bin/mjml)
 * 2. Fallback a npx mjml si no está instalado localmente
 * 3. Conversión básica regex si ningún binario está disponible
 *
 * LIMITACIONES:
 * - El fallback regex solo soporta tags MJML básicos
 * - Para producción, se recomienda tener MJML instalado
 *
 * ESPECIFICACIÓN: Doc 139 - Marketing_AI_Stack_Native
 */
class MjmlCompilerService
{

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un MjmlCompilerService.
     *
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Compila MJML a HTML.
     *
     * Intenta compilar usando el binario MJML si está disponible.
     * Si no, usa una conversión básica que reemplaza tags MJML
     * comunes con equivalentes HTML inline.
     *
     * @param string $mjml
     *   El contenido MJML a compilar.
     *
     * @return string
     *   El HTML compilado listo para envío.
     */
    public function compile(string $mjml): string
    {
        // Verificar si tenemos binario MJML local.
        $mjmlBinary = '/usr/local/bin/mjml';
        if (!file_exists($mjmlBinary)) {
            $mjmlBinary = 'npx mjml';
        }

        // Intentar compilar via línea de comandos.
        $tempInput = tempnam(sys_get_temp_dir(), 'mjml_');
        $tempOutput = tempnam(sys_get_temp_dir(), 'html_');

        file_put_contents($tempInput, $mjml);

        $command = "{$mjmlBinary} {$tempInput} -o {$tempOutput} 2>&1";
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($tempOutput)) {
            $html = file_get_contents($tempOutput);
            unlink($tempInput);
            unlink($tempOutput);
            return $html;
        }

        // Fallback: conversión básica MJML a HTML.
        $this->logger->warning('Binario MJML no disponible, usando conversión fallback.');

        return $this->fallbackConvert($mjml);
    }

    /**
     * Conversión fallback básica de MJML a HTML.
     *
     * Usa expresiones regulares para reemplazar tags MJML comunes
     * con equivalentes HTML con estilos inline. Esta conversión
     * es básica y no soporta todas las características de MJML.
     *
     * Tags soportados:
     * - mj-section → div con padding
     * - mj-column → div inline-block
     * - mj-text → div con font-family
     * - mj-button → enlace estilizado
     * - mj-image → img responsive
     * - mj-divider → hr estilizado
     *
     * @param string $mjml
     *   El contenido MJML.
     *
     * @return string
     *   HTML básico generado.
     */
    protected function fallbackConvert(string $mjml): string
    {
        // Extraer contenido entre tags mj-body.
        if (preg_match('/<mj-body[^>]*>(.*?)<\/mj-body>/s', $mjml, $matches)) {
            $body = $matches[1];

            // Reemplazar tags MJML comunes con equivalentes HTML.
            $replacements = [
                '/<mj-section[^>]*>/i' => '<div style="padding: 20px;">',
                '/<\/mj-section>/i' => '</div>',
                '/<mj-column[^>]*>/i' => '<div style="display: inline-block; vertical-align: top;">',
                '/<\/mj-column>/i' => '</div>',
                '/<mj-text[^>]*>/i' => '<div style="font-family: Arial, sans-serif;">',
                '/<\/mj-text>/i' => '</div>',
                '/<mj-button[^>]*href="([^"]*)"[^>]*>/i' => '<a href="$1" style="display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 4px;">',
                '/<\/mj-button>/i' => '</a>',
                '/<mj-image[^>]*src="([^"]*)"[^>]*>/i' => '<img src="$1" style="max-width: 100%;">',
                '/<mj-divider[^>]*>/i' => '<hr style="border: 0; border-top: 1px solid #e5e5e5; margin: 20px 0;">',
            ];

            $html = preg_replace(
                array_keys($replacements),
                array_values($replacements),
                $body
            );

            return <<<HTML
      <!DOCTYPE html>
      <html>
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
      </head>
      <body style="margin: 0; padding: 0; font-family: Arial, sans-serif;">
        <div style="max-width: 600px; margin: 0 auto;">
          {$html}
        </div>
      </body>
      </html>
      HTML;
        }

        // Retornar original si no se puede parsear.
        return $mjml;
    }

    /**
     * Valida la sintaxis MJML.
     *
     * Realiza validaciones básicas de estructura MJML:
     * - Presencia de tag raíz mjml
     * - Presencia de mj-body
     * - Balance de tags abiertos/cerrados
     *
     * @param string $mjml
     *   El contenido MJML a validar.
     *
     * @return array
     *   Array con:
     *   - 'valid': bool - Si la sintaxis es válida.
     *   - 'errors': array - Lista de errores encontrados.
     */
    public function validate(string $mjml): array
    {
        $errors = [];

        // Verificaciones básicas de estructura.
        if (!str_contains($mjml, '<mjml')) {
            $errors[] = 'Falta el tag raíz <mjml>.';
        }

        if (!str_contains($mjml, '<mj-body')) {
            $errors[] = 'Falta el tag <mj-body>.';
        }

        // Verificar balance de tags.
        $openTags = preg_match_all('/<mj-[a-z]+/i', $mjml);
        $closeTags = preg_match_all('/<\/mj-[a-z]+>/i', $mjml);

        if ($openTags !== $closeTags) {
            $errors[] = 'Se detectaron tags MJML desbalanceados.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

}
