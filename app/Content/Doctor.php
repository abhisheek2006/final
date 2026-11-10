<?php
declare(strict_types=1);

namespace App\Content;

/**
 * Physician profile and clinic credentials.
 */
final class Doctor
{
    /** @return array<string,mixed> */
    public static function profile(): array
    {
        return [
            'name'        => 'Dr. Smriti Das',
            'display'     => 'Dr. Smriti Das',
            'qualifications' => [
                'Bachelor of Homeopathic Medicine and Surgery (BHMS)',
                'Doctor of Medicine — Homeopathy (MD), West Bengal University of Health Sciences',
            ],
            'short_qualification' => 'BHMS, MD (WBUHS)',
            'experience'  => '8+ years of clinical experience',
            'experience_years' => 8,
            'role'        => 'Homoeopathic Physician',
            'image'       => '/assets/images/about/doctor-smriti-das.jpg',
            'image_alt'   => 'Photograph of Dr. Smriti Das, Homoeopathic Physician at Swasti Homoeo Clinic, Sri Vijaya Puram',
            'registration_note' => 'Registered homoeopathic practitioner licensed to practise in '
                . 'Andaman and Nicobar Islands. Registration particulars are available on request and '
                . 'are displayed in the clinic.',
            'languages'   => self::languages(),
        ];
    }

    /** @return array<string,string> */
    public static function schemaPerson(): array
    {
        $profile = self::profile();

        return [
            '@id'         => url('/about-us#doctor'),
            '@type'       => 'Physician',
            'name'        => $profile['name'],
            'honorificPrefix' => 'Dr.',
            'jobTitle'    => $profile['role'],
            'description' => 'Homoeopathic physician with ' . $profile['experience'] . ' at '
                . Clinic::NAME . ', ' . Clinic::LOCALITY . '.',
            'medicalSpecialty' => 'Homeopathy',
            'url'         => url('/about-us#doctor'),
            'image'       => url($profile['image']),
            'telephone'   => Clinic::info()['phone'],
            'email'       => Clinic::info()['email'],
            'worksFor'    => ['@id' => url('/#clinic')],
        ];
    }

    /**
     * Long-form biography shown on the About page.
     *
     * @return array<int,array{heading:string,body:string}>
     */
    public static function biography(): array
    {
        return [
            [
                'heading' => '',
                'body'    => 'Dr. Smriti is a well experienced physician with profound clinical knowledge, '
                    . 'she is committed and dedicated towards alleviating patients’ illnesses since '
                    . 'she has started her clinical journey.',
            ],
            [
                'heading' => '',
                'body'    => 'She pursued her graduation from D.N.De HMC&H under West Bengal University of Health Sciences.',
            ],
            [
                'heading' => '',
                'body'    => 'She pursued her post-graduation at Mahesh Bhattacharya HMC&H under West Bengal University of Health Sciences.',
            ],
            [
                'heading' => '',
                'body'    => 'She received the prestigious academic excellence award, the Dr. Malati Allen Noble Award in 2017, '
                    . 'world’s largest award ceremony in the field of Homoeopathy.',
            ],
        ];
    }

    /**
     * Clinic credentials shown on the About page.
     *
     * @return array<int,array{title:string,text:string,icon:string}>
     */
    public static function credentials(): array
    {
        return [
            [
                'icon'  => 'building',
                'title' => 'Registered clinical practice',
                'text'  => 'Operated by ' . Clinic::LEGAL_NAME . ', a Limited Liability Partnership '
                    . 'registered in the Andaman and Nicobar Islands.',
            ],
            [
                'icon'  => 'certificate',
                'title' => 'Licensed homoeopathic physician',
                'text'  => 'BHMS and MD (Homeopathy) with a valid registration to practise in '
                    . 'Andaman and Nicobar Islands. Registration details are on display at the clinic.',
            ],
            [
                'icon'  => 'flask',
                'title' => 'Licensed pharmacy supply',
                'text'  => 'Medicines are procured from licensed homoeopathic pharmacies and dispensed '
                    . 'in sealed, labelled bottles with potency and dose clearly written.',
            ],
            [
                'icon'  => 'note',
                'title' => 'Written prescriptions',
                'text'  => 'Every patient leaves with a written record of the remedy, potency, dose, '
                    . 'timing and the follow-up date, so treatment never depends on memory.',
            ],
        ];
    }

    /**
     * Language capability, used as a small trust signal on booking pages.
     *
     * @return string[]
     */
    public static function languages(): array
    {
        return ['English', 'Hindi', 'Bengali'];
    }
}
