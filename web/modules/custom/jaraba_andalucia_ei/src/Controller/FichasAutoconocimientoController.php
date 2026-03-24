<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wizard multi-step for 8 self-discovery sheets (Orientación Inicial).
 *
 * ZERO-REGION-001: Returns minimal markup; data via hook_preprocess_page.
 * SPEC-2E-014: 8 fichas autoconocimiento OI-1.1 to OI-2.2.
 *
 * The 8 sheets are:
 * 1. Mi historia personal (narrativa libre)
 * 2. Mis experiencias laborales (estructurado)
 * 3. Mis competencias y habilidades (checklist + autoevaluación)
 * 4. Mi situación actual (empleo, formación, barreras)
 * 5. Mis intereses profesionales (RIASEC simplificado)
 * 6. Mi relación con la tecnología (nivel digital: A/B/C)
 * 7. Mis recursos y red de contactos (mapa de recursos)
 * 8. Mi hipótesis de trabajo (pack preseleccionado + sector + cliente tipo)
 */
class FichasAutoconocimientoController extends ControllerBase {

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the multi-step wizard for the 8 self-discovery sheets.
   *
   * @return array
   *   Render array with theme hook.
   */
  public function wizard(): array {
    return [
      '#theme' => 'fichas_autoconocimiento',
      '#fichas' => $this->getFichasDefinitions(),
    ];
  }

  /**
   * Returns the 8 sheet definitions.
   *
   * @return array<int, array<string, string>>
   *   Array of sheet definitions.
   */
  protected function getFichasDefinitions(): array {
    return [
      1 => [
        'titulo' => (string) $this->t('Mi historia personal'),
        'descripcion' => (string) $this->t('Comparte tu trayectoria vital: de dónde vienes, qué has hecho, qué te motiva.'),
        'icono_categoria' => 'users',
        'icono_nombre' => 'user',
        'tipo_campo' => 'textarea',
      ],
      2 => [
        'titulo' => (string) $this->t('Mis experiencias laborales'),
        'descripcion' => (string) $this->t('Lista tus trabajos anteriores, voluntariados o proyectos. Incluye lo que aprendiste en cada uno.'),
        'icono_categoria' => 'business',
        'icono_nombre' => 'briefcase',
        'tipo_campo' => 'structured',
      ],
      3 => [
        'titulo' => (string) $this->t('Mis competencias y habilidades'),
        'descripcion' => (string) $this->t('¿Qué sabes hacer bien? Marca las competencias que reconoces en ti.'),
        'icono_categoria' => 'achievement',
        'icono_nombre' => 'award',
        'tipo_campo' => 'checklist',
      ],
      4 => [
        'titulo' => (string) $this->t('Mi situación actual'),
        'descripcion' => (string) $this->t('Empleo, formación, prestaciones, barreras. Un diagnóstico honesto de dónde estás hoy.'),
        'icono_categoria' => 'analytics',
        'icono_nombre' => 'gauge',
        'tipo_campo' => 'structured',
      ],
      5 => [
        'titulo' => (string) $this->t('Mis intereses profesionales'),
        'descripcion' => (string) $this->t('Descubre tu perfil RIASEC: ¿eres más práctico, investigador, artístico, social, emprendedor u organizado?'),
        'icono_categoria' => 'ai',
        'icono_nombre' => 'sparkles',
        'tipo_campo' => 'riasec',
      ],
      6 => [
        'titulo' => (string) $this->t('Mi relación con la tecnología'),
        'descripcion' => (string) $this->t('¿Cómo te llevas con la tecnología? Esto nos ayuda a adaptar tu formación.'),
        'icono_categoria' => 'tools',
        'icono_nombre' => 'settings',
        'tipo_campo' => 'nivel_digital',
      ],
      7 => [
        'titulo' => (string) $this->t('Mis recursos y red de contactos'),
        'descripcion' => (string) $this->t('¿A quién conoces? ¿Qué recursos tienes? Tu red es tu mayor activo.'),
        'icono_categoria' => 'communication',
        'icono_nombre' => 'message-circle',
        'tipo_campo' => 'structured',
      ],
      8 => [
        'titulo' => (string) $this->t('Mi hipótesis de trabajo'),
        'descripcion' => (string) $this->t('Elige 2-3 packs que te interesen, el sector y el tipo de cliente. Es tu primera hipótesis — puedes cambiar más adelante.'),
        'icono_categoria' => 'commerce',
        'icono_nombre' => 'store',
        'tipo_campo' => 'pack_selector',
      ],
    ];
  }

}
