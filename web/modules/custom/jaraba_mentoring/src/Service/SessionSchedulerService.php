<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Service;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\jaraba_mentoring\Entity\MentoringEngagement;
use Drupal\jaraba_mentoring\Entity\MentoringSession;
use Drupal\jaraba_mentoring\Entity\MentorProfile;

/**
 * Service for scheduling mentoring sessions.
 */
class SessionSchedulerService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TimeInterface $time,
    ) {
    }

    /**
     * Gets available slots for a mentor in a date range.
     *
     * @param MentorProfile $mentor
     *   The mentor profile.
     * @param \DateTimeInterface $start
     *   Start of the range.
     * @param \DateTimeInterface $end
     *   End of the range.
     *
     * @return array
     *   Array of available slots with start/end times.
     */
    public function getAvailableSlots(MentorProfile $mentor, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $slot_storage = $this->entityTypeManager->getStorage('availability_slot');
        $session_storage = $this->entityTypeManager->getStorage('mentoring_session');

        // Get recurring slots for this mentor.
        $slots = $slot_storage->loadByProperties([
            'mentor_id' => $mentor->id(),
            'is_available' => TRUE,
        ]);

        // Get already booked sessions in this range.
        $booked_sessions = $session_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('mentor_id', $mentor->id())
            ->condition('scheduled_start', $start->format('Y-m-d\TH:i:s'), '>=')
            ->condition('scheduled_start', $end->format('Y-m-d\TH:i:s'), '<=')
            ->condition('status', ['cancelled', 'no_show'], 'NOT IN')
            ->execute();

        $booked_times = [];
        if (!empty($booked_sessions)) {
            $sessions = $session_storage->loadMultiple($booked_sessions);
            foreach ($sessions as $session) {
                $booked_times[] = $session->get('scheduled_start')->value;
            }
        }

        // Generate available slots for each day in range.
        $available = [];
        $current = clone $start;

        while ($current <= $end) {
            $day_of_week = (int) $current->format('w');

            foreach ($slots as $slot) {
                if ((int) $slot->get('day_of_week')->value === $day_of_week) {
                    $slot_start = $slot->get('start_time')->value;
                    $slot_end = $slot->get('end_time')->value;

                    // Generate 1-hour blocks.
                    $block_start = new DrupalDateTime($current->format('Y-m-d') . ' ' . $slot_start);
                    $block_end_limit = new DrupalDateTime($current->format('Y-m-d') . ' ' . $slot_end);

                    while ($block_start < $block_end_limit) {
                        $block_end = clone $block_start;
                        $block_end->modify('+1 hour');

                        $slot_datetime = $block_start->format('Y-m-d\TH:i:s');

                        // Check if not already booked and in the future.
                        if (!in_array($slot_datetime, $booked_times, TRUE) && $block_start->getTimestamp() > $this->time->getCurrentTime()) {
                            $available[] = [
                                'start' => $slot_datetime,
                                'end' => $block_end->format('Y-m-d\TH:i:s'),
                                'day' => $current->format('l'),
                                'date' => $current->format('Y-m-d'),
                            ];
                        }

                        $block_start->modify('+1 hour');
                    }
                }
            }

            $current->modify('+1 day');
        }

        return $available;
    }

    /**
     * Books a session.
     *
     * @param MentoringEngagement $engagement
     *   The engagement.
     * @param string $start_datetime
     *   ISO 8601 datetime string.
     * @param int $duration_minutes
     *   Duration in minutes.
     *
     * @return MentoringSession|null
     *   The created session or null on failure.
     */
    public function bookSession(MentoringEngagement $engagement, string $start_datetime, int $duration_minutes = 60): ?MentoringSession
    {
        if ($engagement->getSessionsRemaining() < 1) {
            return NULL;
        }

        $session_storage = $this->entityTypeManager->getStorage('mentoring_session');

        $start = new DrupalDateTime($start_datetime);
        $end = clone $start;
        $end->modify("+{$duration_minutes} minutes");

        // Count existing sessions for numbering.
        $existing = $session_storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('engagement_id', $engagement->id())
            ->count()
            ->execute();

        /** @var MentoringSession $session */
        $session = $session_storage->create([
            'engagement_id' => $engagement->id(),
            'mentor_id' => $engagement->get('mentor_id')->target_id,
            'mentee_id' => $engagement->get('mentee_id')->target_id,
            'session_number' => $existing + 1,
            'scheduled_start' => $start->format('Y-m-d\TH:i:s'),
            'scheduled_end' => $end->format('Y-m-d\TH:i:s'),
            'status' => 'scheduled',
        ]);

        $session->save();

        // Decrement remaining sessions.
        $engagement->useSession();
        $engagement->save();

        return $session;
    }

    /**
     * Reschedules a session.
     *
     * @param MentoringSession $session
     *   The session.
     * @param string $new_start
     *   New start datetime.
     *
     * @return bool
     *   TRUE if rescheduled successfully.
     */
    public function rescheduleSession(MentoringSession $session, string $new_start): bool
    {
        $status = $session->get('status')->value;
        if (!in_array($status, ['scheduled', 'confirmed'], TRUE)) {
            return FALSE;
        }

        $start = new DrupalDateTime($new_start);
        $end = clone $start;
        $end->modify('+1 hour');

        $session->set('scheduled_start', $start->format('Y-m-d\TH:i:s'));
        $session->set('scheduled_end', $end->format('Y-m-d\TH:i:s'));
        $session->set('status', 'scheduled');

        // Reset reminders.
        $session->set('reminder_24h_sent', FALSE);
        $session->set('reminder_1h_sent', FALSE);
        $session->set('reminder_15min_sent', FALSE);

        $session->save();

        return TRUE;
    }

    /**
     * Cancels a session.
     *
     * @param MentoringSession $session
     *   The session.
     * @param string $reason
     *   Cancellation reason.
     * @param bool $refund_session
     *   Whether to refund the session to the engagement.
     *
     * @return bool
     *   TRUE if cancelled successfully.
     */
    public function cancelSession(MentoringSession $session, string $reason, bool $refund_session = TRUE): bool
    {
        $session->set('status', 'cancelled');
        $session->save();

        if ($refund_session) {
            $engagement_id = $session->get('engagement_id')->target_id;
            $engagement_storage = $this->entityTypeManager->getStorage('mentoring_engagement');

            /** @var MentoringEngagement $engagement */
            $engagement = $engagement_storage->load($engagement_id);
            if ($engagement) {
                $remaining = (int) $engagement->get('sessions_remaining')->value;
                $engagement->set('sessions_remaining', $remaining + 1);
                $used = (int) $engagement->get('sessions_used')->value;
                $engagement->set('sessions_used', max(0, $used - 1));
                $engagement->save();
            }
        }

        return TRUE;
    }

}
