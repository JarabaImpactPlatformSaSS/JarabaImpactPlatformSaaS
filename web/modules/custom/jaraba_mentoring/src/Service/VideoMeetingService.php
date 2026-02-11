<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Drupal\jaraba_mentoring\Entity\MentoringSession;

/**
 * Service for video meeting integration (Jitsi Meet / Zoom).
 */
class VideoMeetingService
{

    /**
     * Jitsi public server URL.
     */
    protected const JITSI_SERVER = 'https://meet.jit.si';

    /**
     * Constructor.
     */
    public function __construct(
        protected ConfigFactoryInterface $configFactory,
        protected ClientInterface $httpClient,
    ) {
    }

    /**
     * Generates a Jitsi room for a session.
     *
     * @param MentoringSession $session
     *   The mentoring session.
     *
     * @return array
     *   Room information with room_id and url.
     */
    public function generateJitsiRoom(MentoringSession $session): array
    {
        $config = $this->configFactory->get('jaraba_mentoring.settings');
        $custom_server = $config->get('jitsi_server');
        $server = $custom_server ?: self::JITSI_SERVER;

        // Generate unique room ID based on session.
        $room_id = 'jaraba-mentoring-' . $session->uuid();

        $url = $server . '/' . $room_id;

        // Update session with meeting info.
        $session->set('meeting_provider', 'jitsi');
        $session->set('meeting_room_id', $room_id);
        $session->set('meeting_url', $url);
        $session->save();

        return [
            'room_id' => $room_id,
            'url' => $url,
            'provider' => 'jitsi',
        ];
    }

    /**
     * Gets join URL with optional user info.
     *
     * @param MentoringSession $session
     *   The session.
     * @param string $display_name
     *   User's display name.
     * @param bool $is_moderator
     *   Whether the user is a moderator (mentor).
     *
     * @return string
     *   The join URL with query parameters.
     */
    public function getJoinUrl(MentoringSession $session, string $display_name, bool $is_moderator = FALSE): string
    {
        $base_url = $session->getMeetingUrl();

        if (empty($base_url)) {
            $room_info = $this->generateJitsiRoom($session);
            $base_url = $room_info['url'];
        }

        $params = [
            'userInfo.displayName' => $display_name,
        ];

        if ($is_moderator) {
            $config = $this->configFactory->get('jaraba_mentoring.settings');
            $jwt_secret = $config->get('jitsi_jwt_secret');

            // If JWT is configured, generate moderator token.
            if ($jwt_secret) {
                // JWT generation would go here for private Jitsi servers.
                // For public Jitsi, moderation is not strictly enforced.
            }
        }

        return $base_url . '#' . http_build_query($params);
    }

    /**
     * Generates an ICS calendar invite.
     *
     * @param MentoringSession $session
     *   The session.
     * @param string $attendee_email
     *   Attendee email.
     * @param string $attendee_name
     *   Attendee name.
     *
     * @return string
     *   ICS file content.
     */
    public function generateCalendarInvite(MentoringSession $session, string $attendee_email, string $attendee_name): string
    {
        $start = new \DateTime($session->get('scheduled_start')->value);
        $end = new \DateTime($session->get('scheduled_end')->value);

        $uid = $session->uuid() . '@jaraba.es';
        $dtstamp = gmdate('Ymd\THis\Z');
        $dtstart = $start->format('Ymd\THis');
        $dtend = $end->format('Ymd\THis');

        $summary = t('Sesión de Mentoría Jaraba');
        $description = t('Únete a tu sesión de mentoría: @url', ['@url' => $session->getMeetingUrl()]);
        $location = $session->getMeetingUrl();

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Jaraba Impact Platform//Mentoring//ES\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:{$dtstamp}\r\n";
        $ics .= "DTSTART:{$dtstart}\r\n";
        $ics .= "DTEND:{$dtend}\r\n";
        $ics .= "SUMMARY:{$summary}\r\n";
        $ics .= "DESCRIPTION:{$description}\r\n";
        $ics .= "LOCATION:{$location}\r\n";
        $ics .= "ATTENDEE;CN={$attendee_name}:mailto:{$attendee_email}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

}
