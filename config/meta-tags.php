<?php
/**
 * Meta Tags Helper - Generate SEO and Social Media Meta Tags
 * Supports: Open Graph (Facebook, LinkedIn), Twitter Card, Basic SEO
 */

class MetaTags {
    private static $defaults = [
        'title' => 'Umojawetu Platform',
        'description' => 'Discover, book, and support community events. Join us for amazing gatherings and pledge your support.',
        'image' => '/assets/og-image.png',
        'url' => '',
        'type' => 'website',
        'twitter_handle' => '@umojawetu'
    ];

    /**
     * Generate all meta tags for a page
     */
    public static function generate($data = []) {
        $meta = array_merge(self::$defaults, $data);
        
        // Ensure URL is absolute
        if (empty($meta['url'])) {
            $meta['url'] = self::getCurrentUrl();
        }
        
        // Ensure image is absolute URL
        if (!preg_match('/^https?:\/\//', $meta['image'])) {
            $baseUrl = self::getBaseUrl();
            $meta['image'] = rtrim($baseUrl, '/') . '/' . ltrim($meta['image'], '/');
        }
        
        // Sanitize for HTML context
        $meta['title'] = htmlspecialchars($meta['title'], ENT_QUOTES, 'UTF-8');
        $meta['description'] = htmlspecialchars($meta['description'], ENT_QUOTES, 'UTF-8');
        
        return self::renderTags($meta);
    }

    /**
     * Generate meta tags for an event page
     */
    public static function generateEventMeta($event) {
        $description = substr($event['Description'] ?? '', 0, 155);
        if (strlen($event['Description'] ?? '') > 155) {
            $description .= '...';
        }

        return self::generate([
            'title' => ($event['Title'] ?? 'Event') . ' - Umojawetu',
            'description' => $description,
            'image' => $event['ImageUrl'] ?? '/assets/og-image.png',
            'url' => self::getEventUrl($event['Id'] ?? ''),
            'type' => 'event',
            'event_date' => $event['Date'] ?? '',
            'price' => $event['PriceAdult'] ?? 0
        ]);
    }

    /**
     * Generate meta tags for pledge/fundraiser page
     */
    public static function generatePledgeMeta($pledge) {
        return self::generate([
            'title' => ($pledge['campaign_name'] ?? 'Campaign') . ' - Support Umojawetu',
            'description' => $pledge['description'] ?? 'Be part of something amazing. Pledge your support now.',
            'image' => $pledge['campaign_image'] ?? '/assets/og-image.png',
            'url' => self::getPledgeUrl($pledge['Id'] ?? ''),
            'type' => 'fundraiser'
        ]);
    }

    /**
     * Render HTML meta tags
     */
    private static function renderTags($meta) {
        $html = "<!-- SEO & Social Meta Tags -->\n";
        
        // Basic SEO Tags
        $html .= "    <meta name=\"title\" content=\"{$meta['title']}\">\n";
        $html .= "    <meta name=\"description\" content=\"{$meta['description']}\">\n";
        $html .= "    <meta name=\"keywords\" content=\"events, community, pledges, fundraising, bookings\">\n";
        $html .= "    <meta name=\"author\" content=\"Umojawetu Platform\">\n";
        $html .= "    <meta name=\"robots\" content=\"index, follow\">\n";
        
        // Open Graph (Facebook, LinkedIn, Pinterest)
        $html .= "\n    <!-- Open Graph -->\n";
        $html .= "    <meta property=\"og:type\" content=\"{$meta['type']}\">\n";
        $html .= "    <meta property=\"og:url\" content=\"{$meta['url']}\">\n";
        $html .= "    <meta property=\"og:title\" content=\"{$meta['title']}\">\n";
        $html .= "    <meta property=\"og:description\" content=\"{$meta['description']}\">\n";
        $html .= "    <meta property=\"og:image\" content=\"{$meta['image']}\">\n";
        $html .= "    <meta property=\"og:site_name\" content=\"Umojawetu\">\n";
        $html .= "    <meta property=\"og:locale\" content=\"en_GB\">\n";
        
        // Event-specific OG tags
        if (isset($meta['event_date']) && $meta['event_date']) {
            $html .= "    <meta property=\"og:event:start_time\" content=\"{$meta['event_date']}\">\n";
        }
        if (isset($meta['price']) && $meta['price']) {
            $html .= "    <meta property=\"product:price:amount\" content=\"{$meta['price']}\">\n";
            $html .= "    <meta property=\"product:price:currency\" content=\"GBP\">\n";
        }
        
        // Twitter Card
        $html .= "\n    <!-- Twitter Card -->\n";
        $html .= "    <meta name=\"twitter:card\" content=\"summary_large_image\">\n";
        $html .= "    <meta name=\"twitter:site\" content=\"{$meta['twitter_handle']}\">\n";
        $html .= "    <meta name=\"twitter:title\" content=\"{$meta['title']}\">\n";
        $html .= "    <meta name=\"twitter:description\" content=\"{$meta['description']}\">\n";
        $html .= "    <meta name=\"twitter:image\" content=\"{$meta['image']}\">\n";
        
        // Apple
        $html .= "\n    <!-- Apple -->\n";
        $html .= "    <meta name=\"apple-mobile-web-app-capable\" content=\"yes\">\n";
        $html .= "    <meta name=\"apple-mobile-web-app-status-bar-style\" content=\"black-translucent\">\n";
        $html .= "    <meta name=\"apple-mobile-web-app-title\" content=\"Umojawetu\">\n";
        
        // Canonical URL
        $html .= "\n    <!-- Canonical URL -->\n";
        $html .= "    <meta name=\"canonical\" href=\"{$meta['url']}\">\n";
        
        return $html;
    }

    /**
     * Get current page URL
     */
    private static function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        return $protocol . '://' . $host . $path;
    }

    /**
     * Get base URL (site root)
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Get full event URL
     */
    private static function getEventUrl($eventId) {
        return self::getBaseUrl() . '/public/events.php?id=' . $eventId;
    }

    /**
     * Get full pledge URL
     */
    private static function getPledgeUrl($pledgeId) {
        return self::getBaseUrl() . '/public/pledge-wall.php?id=' . $pledgeId;
    }
}

// Quick helper function for use in templates
function generateMetaTags($data = []) {
    return MetaTags::generate($data);
}

function generateEventMetaTags($event) {
    return MetaTags::generateEventMeta($event);
}

function generatePledgeMetaTags($pledge) {
    return MetaTags::generatePledgeMeta($pledge);
}
?>
