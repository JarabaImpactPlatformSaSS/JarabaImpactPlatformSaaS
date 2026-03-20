<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Landing de caso de éxito: Carmen Navarro, Madrid.
 *
 * Storytelling de producto para conversión del vertical ServiciosConecta.
 * Zero-region pattern (ZERO-REGION-001).
 */
class ServiciosCaseStudyController extends ControllerBase {

  /**
   * Página de caso de éxito: Carmen Navarro.
   *
   * @return array<string, mixed>
   *   Render array con el caso de éxito.
   */
  public function caseStudy(): array {
    $themePath = \Drupal::service('extension.list.theme')
      ->getPath('ecosistema_jaraba_theme');
    $imgBase = '/' . $themePath . '/images/serviciosconecta-case-study';

    return [
      '#theme' => 'serviciosconecta_case_study',
      '#hero_image' => $imgBase . '/chamberi-hero.webp',
      '#carmen_image' => $imgBase . '/carmen-consulta.webp',
      '#before_after_image' => $imgBase . '/antes-despues-servicios.webp',
      '#qr_image' => $imgBase . '/qr-reservas-clinica.webp',
      '#dashboard_image' => $imgBase . '/dashboard-servicios.webp',
      '#review_image' => $imgBase . '/paciente-resena-google.webp',
      '#metrics' => [
        ['label' => $this->t('Reservas online vs teléfono'), 'before' => '0%', 'after' => '68%', 'change' => '+68%'],
        ['label' => $this->t('No-shows mensuales'), 'before' => '12', 'after' => '2', 'change' => '-83%'],
        ['label' => $this->t('Reseñas Google'), 'before' => '3 (4,1★)' , 'after' => '47 (4,9★)', 'change' => '+44'],
        ['label' => $this->t('Facturación mensual'), 'before' => '4.200 €', 'after' => '6.180 €', 'change' => '+47%'],
        ['label' => $this->t('Tiempo gestión admin'), 'before' => '12 h/sem', 'after' => '3 h/sem', 'change' => '-75%'],
      ],
      '#testimonial' => [
        'quote' => $this->t('Antes perdía media mañana llamando para confirmar citas. Ahora los pacientes reservan solos por WhatsApp o QR, reciben su recordatorio automático y yo puedo dedicar ese tiempo a lo que realmente importa: tratar personas. Las reseñas automatizadas han sido un game-changer. En 90 días pasé de 3 reseñas a 47 con una media de 4,9 estrellas.'),
        'name' => 'Carmen Navarro',
        'role' => $this->t('Fisioterapeuta y directora'),
        'company' => $this->t('FisioVital Chamberí, Madrid'),
      ],
      '#timeline' => [
        ['day' => 'S1', 'title' => $this->t('Perfil y catálogo'), 'text' => $this->t('Perfil profesional verificado, 8 servicios catalogados con precio y duración')],
        ['day' => 'S2', 'title' => $this->t('Reservas online'), 'text' => $this->t('Widget de reserva en web + QR en recepción. 14 reservas la primera semana')],
        ['day' => 'S3-4', 'title' => $this->t('Reseñas automatizadas'), 'text' => $this->t('Post-cita: WhatsApp con enlace a Google Review. De 3 a 22 reseñas')],
        ['day' => 'S5-8', 'title' => $this->t('Presupuestador IA'), 'text' => $this->t('Bonos personalizados por IA. Ticket medio sube de 45 € a 62 €')],
        ['day' => 'S9-12', 'title' => $this->t('Copilot proactivo'), 'text' => $this->t('Alertas de huecos libres, recordatorios y seguimiento post-tratamiento')],
      ],
      '#pricing' => [
        'free_features' => $this->t('Perfil profesional, 1 servicio, reservas manuales'),
        'starter_price' => '25',
        'starter_features' => $this->t('Reservas online ilimitadas, reseñas automáticas, QR, recordatorios'),
        'professional_price' => '69',
        'professional_features' => $this->t('Presupuestador IA, copilot proactivo, bonos, analytics, firma digital'),
      ],
      '#pricing_url' => '/planes/serviciosconecta',
      '#register_url' => '/registro/serviciosconecta',
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/serviciosconecta-case-study',
          'ecosistema_jaraba_theme/scroll-animations',
        ],
      ],
      '#cache' => [
        'max-age' => 86400,
        'tags' => ['case_study_list'],
      ],
    ];
  }

}
