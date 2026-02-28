<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Genera contratos de servicio PDF y los registra para firma digital.
 *
 * Estructura: Crea un node documento_firma con el PDF del contrato
 *   derivado de los datos del Booking (proveedor, servicio, precio,
 *   condiciones). Reutiliza FirmaDigitalService de ecosistema_jaraba_core.
 *
 * Logica: El contrato se genera como PDF simple, se almacena como
 *   managed file, se crea un node documento_firma con estado 'pendiente',
 *   y se actualiza booking.contract_document_id con el nid.
 */
class BookingContractService {

  /**
   * The FirmaDigital service (optional, from ecosistema_jaraba_core).
   *
   * @var object|null
   */
  protected ?object $firmaDigital;

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    ?object $firma_digital,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {
    $this->firmaDigital = $firma_digital;
  }

  /**
   * Genera un contrato PDF para un booking y lo registra para firma.
   *
   * @param int $bookingId
   *   ID del Booking.
   *
   * @return array|null
   *   Array con document_id, status, sign_url o NULL si falla.
   */
  public function generateContract(int $bookingId): ?array {
    try {
      $booking = $this->entityTypeManager->getStorage('booking')->load($bookingId);
      if (!$booking) {
        $this->logger->warning('Booking @id not found for contract generation.', ['@id' => $bookingId]);
        return NULL;
      }

      // Load related entities.
      $provider = $booking->get('provider_id')->entity;
      $offering = $booking->get('offering_id')->entity;
      $clientUid = (int) $booking->getOwnerId();
      $clientUser = $this->entityTypeManager->getStorage('user')->load($clientUid);

      if (!$provider || !$offering) {
        $this->logger->warning('Missing provider or offering for booking @id.', ['@id' => $bookingId]);
        return NULL;
      }

      // Check if documento_firma node type exists.
      if (!$this->entityTypeManager->hasDefinition('node')) {
        return NULL;
      }
      $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      if (!isset($nodeTypes['documento_firma'])) {
        $this->logger->warning('Node type documento_firma not found. Firma digital module may not be configured.');
        return NULL;
      }

      // Generate contract PDF content.
      $pdfContent = $this->buildContractPdf($booking, $provider, $offering, $clientUser);
      if (!$pdfContent) {
        return NULL;
      }

      // Save PDF as managed file.
      $directory = 'private://contratos/' . date('Y-m');
      \Drupal::service('file_system')->prepareDirectory($directory, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

      $filename = 'contrato-booking-' . $bookingId . '-' . time() . '.pdf';
      $file = \Drupal::service('file.repository')->writeData(
        $pdfContent,
        $directory . '/' . $filename,
        \Drupal\Core\File\FileExists::Replace
      );

      if (!$file) {
        $this->logger->error('Failed to save contract PDF for booking @id.', ['@id' => $bookingId]);
        return NULL;
      }
      $file->setPermanent();
      $file->save();

      // Create documento_firma node.
      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'documento_firma',
        'title' => 'Contrato Servicio — Reserva #' . $bookingId,
        'uid' => $this->currentUser->id(),
        'status' => 1,
      ]);

      if ($node->hasField('field_documento_pdf')) {
        $node->set('field_documento_pdf', ['target_id' => $file->id()]);
      }
      if ($node->hasField('field_estado_firma')) {
        $node->set('field_estado_firma', 'pendiente');
      }
      if ($node->hasField('field_firmante_destinatario')) {
        $node->set('field_firmante_destinatario', $clientUid);
      }

      $node->save();

      // Update booking with contract reference.
      $booking->set('contract_document_id', (int) $node->id());
      $booking->save();

      $this->logger->info('Contract document @nid generated for booking @id.', [
        '@nid' => $node->id(),
        '@id' => $bookingId,
      ]);

      return [
        'document_id' => (int) $node->id(),
        'status' => 'pendiente',
        'sign_url' => '/firma-digital/documento/' . $node->id(),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error generating contract for booking @id: @error', [
        '@id' => $bookingId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene el estado de firma del contrato de un booking.
   *
   * @param int $bookingId
   *   ID del Booking.
   *
   * @return array|null
   *   Estado del contrato o NULL.
   */
  public function getContractStatus(int $bookingId): ?array {
    try {
      $booking = $this->entityTypeManager->getStorage('booking')->load($bookingId);
      if (!$booking || !$booking->hasField('contract_document_id')) {
        return NULL;
      }

      $documentId = (int) ($booking->get('contract_document_id')->value ?? 0);
      if ($documentId <= 0) {
        return NULL;
      }

      $node = $this->entityTypeManager->getStorage('node')->load($documentId);
      if (!$node || $node->bundle() !== 'documento_firma') {
        return NULL;
      }

      $status = 'pendiente';
      if ($node->hasField('field_estado_firma')) {
        $status = $node->get('field_estado_firma')->value ?? 'pendiente';
      }

      return [
        'document_id' => $documentId,
        'status' => $status,
        'title' => $node->getTitle(),
        'sign_url' => '/firma-digital/documento/' . $documentId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error checking contract status for booking @id: @error', [
        '@id' => $bookingId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Builds a simple PDF contract from booking data.
   *
   * @param object $booking
   *   The Booking entity.
   * @param object $provider
   *   The ProviderProfile entity.
   * @param object $offering
   *   The ServiceOffering entity.
   * @param object|null $clientUser
   *   The client user entity.
   *
   * @return string|null
   *   PDF content as string, or NULL on failure.
   */
  protected function buildContractPdf(object $booking, object $provider, object $offering, ?object $clientUser): ?string {
    $providerName = $provider->get('display_name')->value ?? 'Profesional';
    $serviceName = $offering->get('title')->value ?? 'Servicio';
    $price = (float) ($booking->get('price')->value ?? 0);
    $bookingDate = $booking->get('booking_date')->value ?? date('Y-m-d');
    $clientName = $booking->get('client_name')->value ?? ($clientUser ? $clientUser->getDisplayName() : 'Cliente');
    $clientEmail = $booking->get('client_email')->value ?? '';
    $modality = $booking->get('modality')->value ?? 'in_person';

    // Generate a simple text-based PDF.
    // In production, TCPDF/DOMPDF would be used for rich formatting.
    $content = "%PDF-1.4\n";
    $content .= "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n";
    $content .= "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n";

    $text = "CONTRATO DE PRESTACION DE SERVICIOS\n\n";
    $text .= "Fecha: " . date('d/m/Y') . "\n";
    $text .= "Referencia: Reserva #" . $booking->id() . "\n\n";
    $text .= "PROFESIONAL: " . $providerName . "\n";
    $text .= "CLIENTE: " . $clientName . "\n";
    $text .= "Email: " . $clientEmail . "\n\n";
    $text .= "SERVICIO: " . $serviceName . "\n";
    $text .= "Fecha de servicio: " . $bookingDate . "\n";
    $text .= "Modalidad: " . $modality . "\n";
    $text .= "Precio: " . number_format($price, 2, ',', '.') . " EUR\n";
    $text .= "IVA (21%): " . number_format($price * 0.21, 2, ',', '.') . " EUR\n";
    $text .= "Total: " . number_format($price * 1.21, 2, ',', '.') . " EUR\n\n";
    $text .= "CONDICIONES GENERALES\n";
    $text .= "1. El presente contrato regula la prestacion del servicio descrito.\n";
    $text .= "2. La cancelacion debe realizarse con 24h de antelacion.\n";
    $text .= "3. El pago se realizara segun las condiciones acordadas.\n";
    $text .= "4. Ambas partes aceptan la jurisdiccion de los tribunales competentes.\n\n";
    $text .= "Generado automaticamente por Jaraba Impact Platform\n";

    $streamLength = strlen($text);
    $content .= "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n";
    $content .= "4 0 obj<</Length " . $streamLength . ">>stream\nBT /F1 12 Tf 50 750 Td (" . addcslashes($text, '()\\') . ") Tj ET\nendstream\nendobj\n";
    $content .= "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n";

    $xrefPos = strlen($content);
    $content .= "xref\n0 6\n";
    $content .= "0000000000 65535 f \n";
    $content .= "trailer<</Size 6/Root 1 0 R>>\n";
    $content .= "startxref\n" . $xrefPos . "\n%%EOF";

    return $content;
  }

}
