<?php
/**
 * Calendar Invite Generator - Creates iCalendar (.ics) files
 * Used for adding events to Google Calendar, Outlook, Apple Calendar, etc.
 */

class CalendarInvite {
    
    /**
     * Generate iCalendar format (.ics) content for an event
     */
    public static function generateEventIcs($event) {
        $title = $event['Title'] ?? 'Event';
        $description = $event['Description'] ?? '';
        $dateTime = $event['Date'] ?? date('Y-m-d H:i:s');
        $location = $event['Location'] ?? '';
        
        // Parse event date
        $startTime = strtotime($dateTime);
        $endTime = $startTime + (2 * 3600); // Add 2 hours duration
        
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Umojawetu//Calendar//EN\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        $icsContent .= "X-WR-CALNAME:Umojawetu Events\r\n";
        $icsContent .= "X-WR-TIMEZONE:Europe/London\r\n";
        $icsContent .= "BEGIN:VTIMEZONE\r\n";
        $icsContent .= "TZID:Europe/London\r\n";
        $icsContent .= "X-LIC-LOCATION:Europe/London\r\n";
        $icsContent .= "END:VTIMEZONE\r\n";
        
        // Event details
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . uniqid('event-') . "@umojawetu.com\r\n";
        $icsContent .= "DTSTAMP:" . self::formatDateTime(time()) . "\r\n";
        $icsContent .= "DTSTART;TZID=Europe/London:" . self::formatDateTimeWithTz($startTime) . "\r\n";
        $icsContent .= "DTEND;TZID=Europe/London:" . self::formatDateTimeWithTz($endTime) . "\r\n";
        $icsContent .= "SUMMARY:" . self::escapeText($title) . "\r\n";
        
        if (!empty($description)) {
            $icsContent .= "DESCRIPTION:" . self::escapeText(strip_tags($description)) . "\r\n";
        }
        
        if (!empty($location)) {
            $icsContent .= "LOCATION:" . self::escapeText($location) . "\r\n";
        }
        
        $icsContent .= "STATUS:CONFIRMED\r\n";
        $icsContent .= "SEQUENCE:0\r\n";
        
        // Add reminder notification (30 minutes before)
        $icsContent .= "BEGIN:VALARM\r\n";
        $icsContent .= "TRIGGER:-PT30M\r\n";
        $icsContent .= "ACTION:DISPLAY\r\n";
        $icsContent .= "DESCRIPTION:Event Reminder\r\n";
        $icsContent .= "END:VALARM\r\n";
        
        $icsContent .= "END:VEVENT\r\n";
        $icsContent .= "END:VCALENDAR\r\n";
        
        return $icsContent;
    }
    
    /**
     * Generate iCalendar format (.ics) content for a booking confirmation
     */
    public static function generateBookingIcs($booking, $event) {
        $title = "Booking Confirmation: " . ($event['Title'] ?? 'Event');
        $description = "Booking Reference: " . $booking['BookingReference'] . "\n";
        $description .= "Tickets: " . ($booking['Adults'] ?? 0) . " adult, ";
        $description .= ($booking['Children'] ?? 0) . " child";
        if ($booking['VIP'] ?? 0) {
            $description .= ", " . $booking['VIP'] . " VIP";
        }
        
        $dateTime = $event['Date'] ?? date('Y-m-d H:i:s');
        $location = $event['Location'] ?? '';
        
        // Parse event date
        $startTime = strtotime($dateTime);
        $endTime = $startTime + (2 * 3600);
        
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Umojawetu//Booking//EN\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . uniqid('booking-') . "@umojawetu.com\r\n";
        $icsContent .= "DTSTAMP:" . self::formatDateTime(time()) . "\r\n";
        $icsContent .= "DTSTART:" . self::formatDateTime($startTime) . "\r\n";
        $icsContent .= "DTEND:" . self::formatDateTime($endTime) . "\r\n";
        $icsContent .= "SUMMARY:" . self::escapeText($title) . "\r\n";
        $icsContent .= "DESCRIPTION:" . self::escapeText($description) . "\r\n";
        
        if (!empty($location)) {
            $icsContent .= "LOCATION:" . self::escapeText($location) . "\r\n";
        }
        
        $icsContent .= "STATUS:CONFIRMED\r\n";
        $icsContent .= "SEQUENCE:0\r\n";
        
        // Add reminder
        $icsContent .= "BEGIN:VALARM\r\n";
        $icsContent .= "TRIGGER:-PT1D\r\n"; // 1 day before
        $icsContent .= "ACTION:DISPLAY\r\n";
        $icsContent .= "DESCRIPTION:Your event is tomorrow!\r\n";
        $icsContent .= "END:VALARM\r\n";
        
        $icsContent .= "END:VEVENT\r\n";
        $icsContent .= "END:VCALENDAR\r\n";
        
        return $icsContent;
    }
    
    /**
     * Save .ics file for download link
     */
    public static function saveIcsFile($content, $filename = null) {
        if (!$filename) {
            $filename = 'event-' . date('YmdHis') . '.ics';
        }
        
        $filepath = __DIR__ . '/../storage/ics/' . $filename;
        
        // Create directory if needed
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($filepath, $content);
        return $filepath;
    }
    
    /**
     * Format date/time for iCalendar (UTC format)
     */
    private static function formatDateTime($timestamp) {
        return date('Ymd\THis\Z', $timestamp);
    }
    
    /**
     * Format date/time with timezone info
     */
    private static function formatDateTimeWithTz($timestamp) {
        return date('Ymd\THis', $timestamp);
    }
    
    /**
     * Escape text for iCalendar format
     */
    private static function escapeText($text) {
        $text = str_replace(['\r\n', '\r', '\n', ';', ','], ['\n', '\n', '\n', '\;', '\,'], $text);
        // Remove HTML tags
        $text = strip_tags($text);
        // Limit line length (fold lines at 75 characters)
        return preg_replace('/(.{75})/', '$1' . "\r\n ", $text);
    }
}

// Quick helper functions
function generateEventCalendarInvite($event) {
    return CalendarInvite::generateEventIcs($event);
}

function generateBookingCalendarInvite($booking, $event) {
    return CalendarInvite::generateBookingIcs($booking, $event);
}
?>
