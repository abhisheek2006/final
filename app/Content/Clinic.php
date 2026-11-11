<?php
declare(strict_types=1);

namespace App\Content;

use App\Core\Config;

/**
 * Single source of truth for clinic identity, contact details, navigation and
 * structural copy. Every value can be overridden from .env for the contact
 * fields so the deployed site can be re-pointed without a code change.
 */
final class Clinic
{
    public const NAME            = 'Swasti Homoeo Clinic';
    public const LEGAL_NAME      = 'Andaman Homoeo Health Care LLP';
    public const TAGLINE         = 'We believe in easy, safe and quick recovery';
    public const SERVICE_LINE    = 'Classical Homoeopathic Treatment';
    public const HEADLINE        = 'Homoeopathy Clinic in Sri Vijaya Puram';
    public const ESTABLISHED     = '2017';
    public const LOCALITY        = 'Garacharma, Sri Vijaya Puram';

    /**
     * @return array<string,string>
     */
    public static function info(): array
    {
        return [
            'name'         => self::NAME,
            'legal_name'   => self::LEGAL_NAME,
            'tagline'      => self::TAGLINE,
            'service_line' => self::SERVICE_LINE,
            'headline'     => self::HEADLINE,
            'established'  => self::ESTABLISHED,
            'locality'     => self::LOCALITY,
            'phone'        => Config::string('clinic.phone', '+917980644867'),
            'whatsapp'     => Config::string('clinic.whatsapp', '917980644867'),
            'email'        => Config::string('clinic.email', 'info@swastihomeo.com'),
            'facebook'     => Config::string('clinic.facebook', ''),
            'instagram'    => Config::string('clinic.instagram', ''),
            'x'            => Config::string('clinic.x', ''),
            'youtube'      => Config::string('clinic.youtube', ''),
            'address'      => self::addressLines(),
            'address_full' => self::addressOneLine(),
            'city'         => 'Sri Vijaya Puram',
            'state'        => 'Andaman and Nicobar Islands',
            'country'      => 'India',
            'country_code' => 'IN',
            'pin'          => '744105',
            'timezone'     => 'Asia/Kolkata',
        ];
    }

    /** @return string[] */
    public static function addressLines(): array
    {
        return [
            'NH4 main road, Garacharma',
            'Near Susane roses',
            'Andaman & Nicobar Islands 744105',
        ];
    }

    public static function addressOneLine(): string
    {
        return 'NH4 main road, Garacharma, Near Susane roses, Andaman & Nicobar Islands 744105';
    }

    /**
     * Google Maps place-name search string.
     *
     * Google resolves this to the clinic listing / pin, which is more
     * reliable than raw coordinates across map regions.
     */
    public static function mapQuery(): string
    {
        return 'SWASTI HOMOEO CLINIC | Dr. Smriti Das, BHMS, MD (Hom) | NH4 main road, '
            . 'Garacharma, near Susane roses, Andaman and Nicobar Islands 744105';
    }

    /**
     * Google Maps link for the clinic, used by the footer location icon, the
     * contact page button and the schema.org hasMap property.
     *
     * Searches by place name so Google lands on the clinic listing rather than
     * a bare lat/lng point.
     */
    public static function mapUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='
            . rawurlencode(self::mapQuery());
    }

    /**
     * Primary navigation used by the header and the mobile drawer.
     *
     * @return array<int,array{label:string,url:string,match:string}>
     */
    public static function navigation(): array
    {
        return [
            ['label' => 'Home',           'url' => '/',                  'match' => '/'],
            ['label' => 'About Us',       'url' => '/about-us',           'match' => '/about-us'],
            ['label' => 'FAQ',            'url' => '/faq',               'match' => '/faq'],
            ['label' => 'Contact',        'url' => '/contact',           'match' => '/contact'],
        ];
    }

    /**
     * Footer navigation split into columns.
     *
     * @return array<string,array<int,array{label:string,url:string}>>
     */
    public static function footerNavigation(): array
    {
        return [
            'Appointments' => [
                ['label' => 'Book Consultation', 'url' => '/book-consultation'],
                ['label' => 'Book Follow-Up',    'url' => '/book-follow-up'],
            ],
            'Clinic' => [
                ['label' => 'About the Doctor',     'url' => '/about-us#doctor'],
                ['label' => 'Our Approach',         'url' => '/about-us#approach'],
                ['label' => 'Patient Stories',      'url' => '/about-us#testimonials'],
                ['label' => 'Contact & Directions', 'url' => '/contact'],
            ],
            'Support' => [
                ['label' => 'Frequently Asked',     'url' => '/faq'],
                ['label' => 'Privacy Policy',       'url' => '/privacy-policy'],
            ],
        ];
    }

    /** Opening periods. Keys are lowercase English day names. */
    public static function hours(): array
    {
        $weekday = [
            ['open' => '09:00', 'close' => '12:30'],
            ['open' => '16:30', 'close' => '19:30'],
        ];

        return [
            'monday'    => ['periods' => $weekday],
            'tuesday'   => ['periods' => $weekday],
            'wednesday' => ['periods' => $weekday],
            'thursday'  => ['periods' => $weekday],
            'friday'    => ['periods' => $weekday],
            'saturday'  => ['periods' => $weekday],
            'sunday'    => ['periods' => [], 'closed' => true, 'note' => 'Closed'],
        ];
    }

    /** @return array<int,array{day:string,open:string,close:string}> */
    public static function hoursForSchema(): array
    {
        $out = [];
        foreach (self::hours() as $day => $slot) {
            foreach ($slot['periods'] as $period) {
                $out[] = [
                    'day'   => ucfirst($day),
                    'open'  => $period['open'],
                    'close' => $period['close'],
                ];
            }
        }

        return $out;
    }

    /**
     * Statistic tiles used on the home page and in structured data.
     *
     * @return array<int,array{value:string,label:string,icon:string}>
     */
    public static function stats(): array
    {
        return [
            ['value' => '8+',    'label' => 'Years of clinical practice', 'icon' => 'stethoscope'],
            ['value' => '6,000+','label' => 'Consultations completed',      'icon' => 'users'],
            ['value' => '40+',   'label' => 'Conditions treated',          'icon' => 'leaf'],
            ['value' => '12',    'label' => 'Remedies in classical use',   'icon' => 'flask'],
        ];
    }

    /**
     * The three "why choose us" pillars.
     *
     * @return array<int,array{title:string,text:string,icon:string}>
     */
    public static function pillars(): array
    {
        return [
            [
                'icon'  => 'leaf',
                'title' => 'Classical homoeopathy, practised properly',
                'text'  => 'Each prescription is based on your symptoms and history, with clear '
                    . 'written instructions.',
            ],
            [
                'icon'  => 'shield',
                'title' => 'Safe for the whole family',
                'text'  => 'We tailor each prescription to your age and sensitivity, with no hidden '
                    . 'antibiotics, steroids or sedatives.',
            ],
        ];
    }

    /**
     * Emergency and out-of-scope guidance. Shown on booking and contact pages
     * so visitors know when not to wait for a homoeopathic appointment.
     *
     * @return array<int,string>
     */
    public static function emergencyGuidance(): array
    {
        return [
            'Chest pain, breathing difficulty, sudden weakness, facial droop, heavy bleeding, loss of '
            . 'consciousness, severe burns, a severe allergic reaction, or any injury from an accident '
            . 'are emergencies.',
            'For an emergency, call 112 or take the patient to the nearest emergency department '
            . 'immediately. Do not wait for a homoeopathic appointment.',
            'Homoeopathic treatment is not a substitute for emergency care, surgery, antibiotics where '
            . 'clinically indicated, or any treatment your doctor has already prescribed. We will '
            . 'always tell you when a case needs hospital care.',
            'If you are already on medication for diabetes, blood pressure, thyroid, epilepsy, heart '
            . 'disease, mental health conditions or pregnancy, please continue it and tell the doctor.',
        ];
    }

    /**
     * Short positioning statement used for SEO descriptions and the intro band.
     */
    public static function intro(): string
    {
        return 'Swasti Homeo Clinic offers Affordable Homoeopathy Consultation in Port Blair, providing '
            . 'in-depth case taking, review of previous medical records, and accurate prescriptions '
            . 'based on classical homeopathic principles. Each consultation includes personalised '
            . 'guidance on diet and lifestyle, ensuring holistic and long-lasting healing. With '
            . 'authentic medicines available through the clinic\'s own pharmacy and minimal consultation '
            . 'fees, patients receive effective, ethical, and budget-friendly care.';
    }

    /**
     * Privacy policy sections (revision date is derived at render time).
     *
     * @return array<int,array{title:string,body:string}>
     */
    public static function privacySections(): array
    {
        return [
            [
                'title' => 'Who we are',
                'body'  => 'Swasti Homoeo Clinic ("we", "us") is operated by ' . self::LEGAL_NAME . ', '
                    . 'a Limited Liability Partnership registered in Sri Vijaya Puram, Andaman and Nicobar '
                    . 'Islands, India. For any question about this policy or your health information, '
                    . 'contact us at ' . Config::string('clinic.email', 'info@swastihomeo.com') . '.',
            ],
            [
                'title' => 'What we collect',
                'body'  => 'When you submit a consultation, follow-up or contact form we collect only '
                    . 'what is needed to help you: your name, phone number, optional email address, '
                    . 'age, the problem you want help with, your preferred date and time, and anything '
                    . 'else you choose to write. Our servers also record the IP address and browser '
                    . 'used when the form was submitted, which we use only for spam prevention and '
                    . 'security auditing.',
            ],
            [
                'title' => 'How we use your information',
                'body'  => 'Your details are used to contact you about your appointment, to prepare for '
                    . 'your consultation, to send the prescription and dosage instructions, and to '
                    . 'send a short reminder before your visit. If you ask us in writing, we will '
                    . 'remove your enquiry record from our system. We do not sell, rent or share your '
                    . 'health information with advertisers, data brokers or third-party marketers.',
            ],
            [
                'title' => 'How your information is stored',
                'body'  => 'Consultation details are stored in our own clinic database, on servers '
                    . 'hosted by our web hosting provider in India, protected by access controls and '
                    . 'encrypted in transit over HTTPS. Paper records, where kept, are stored in a '
                    . 'locked cabinet at the clinic. We retain enquiry details only as long as needed '
                    . 'for your care and our legal obligations.',
            ],
            [
                'title' => 'Sharing, only when necessary',
                'body'  => 'We share your information only with a hospital or laboratory if we believe '
                    . 'your condition requires it, with your written consent, and with our email and '
                    . 'web hosting providers purely to deliver the service. We may disclose information '
                    . 'where the law or a court order requires it.',
            ],
            [
                'title' => 'Cookies',
                'body'  => 'This website uses a single first-party session cookie that keeps you signed '
                    . 'in to the clinic admin area and protects forms against cross-site request '
                    . 'forgery. It contains no tracking identifier and is not shared with anyone. We do '
                    . 'not run advertising, analytics or cross-site tracking cookies.',
            ],
            [
                'title' => 'Your rights and choices',
                'body'  => 'You may ask to see the information we hold about you, ask us to correct it, '
                    . 'or ask us to delete it. You can also decline to be contacted for reminders, or '
                    . 'stop using the forms and simply call the clinic instead. Write to us and we will '
                    . 'respond within seven working days.',
            ],
            [
                'title' => 'Security',
                'body'  => 'The site is served over HTTPS, forms are protected with CSRF tokens, a honeypot '
                    . 'field, a minimum dwell-time check and rate limiting, and all output is escaped. '
                    . 'No online system is perfectly secure, so please do not send photographs or highly '
                    . 'sensitive personal details through the form — share those during your visit.',
            ],
            [
                'title' => 'Medical disclaimer',
                'body'  => 'Information on this website is provided for general awareness and is not a '
                    . 'diagnosis or a substitute for a consultation with a qualified doctor. Nothing here '
                    . 'is intended to replace advice from your treating physician, and no remedy should '
                    . 'be started, stopped or changed without speaking to a clinician.',
            ],
            [
                'title' => 'Changes to this policy',
                'body'  => 'We may revise this policy as the website or the law changes. The revision '
                    . 'date is shown at the top of this page, and the updated version will always be '
                    . 'available at /privacy-policy.',
            ],
        ];
    }
}
