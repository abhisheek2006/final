<?php
declare(strict_types=1);

namespace App\Content;

/**
 * Patient testimonials, migrated from the clinic's own published review slider.
 *
 * ---------------------------------------------------------------------------
 * LAUNCH CHECKLIST - READ BEFORE GOING LIVE
 * Each entry below is a real review the patient already published about the
 * clinic, paired with that patient's own photograph. A patient's photo is
 * personal data: keep written consent on file, and if consent for a photo can
 * no longer be evidenced, delete the 'avatar' key for that entry and the
 * initials fallback is used instead. See HOSTINGER_DEPLOYMENT.md, step 19.
 * ---------------------------------------------------------------------------
 */
final class Testimonials
{
    /**
     * @return array<int,array{
     *     name:string,when:string,rating:int,quote:string,avatar:string,featured:bool
     * }>
     */
    public static function all(): array
    {
        return [
            [
                'name'     => 'Swati',
                'when'     => '6 days ago',
                'source'   => 'Google review',
                'rating'   => 5,
                'quote'    => 'Highly recommended! I visited Swasti Hospital for a severe hair loss issue that was making me very anxious. Instead of just giving me a quick fix, the doctor took the time to beautifully explain the root cause.',
                'avatar'   => '',
                'featured' => true,
            ],
            [
                'name'     => 'Suparna Sarkar',
                'when'     => 'Edited 8 months ago',
                'source'   => 'Google review',
                'rating'   => 5,
                'quote'    => 'I consulted Dr. Smriti Das for my endometriosis treatment, and I am very satisfied with the care I received. She is a very good doctor—extremely patient, caring, and attentive. She explains everything clearly and treats her patients with genuine compassion. I would highly recommend her to anyone seeking proper and thoughtful medical care.',
                'avatar'   => '/assets/images/patients/patient-suparna-sarkar.png',
                'featured' => true,
            ],
            [
                'name'     => 'Dheerak Sharma',
                'when'     => 'a year ago',
                'rating'   => 5,
                'quote'    => 'I am writing this after being cured of chronic pharyngitis, which I had for almost three years. After the lockdown ended, I started developing a sore throat. Doctors diagnosed it as chronic pharyngitis and told me it could only be managed with medication. I dealt with it throughout my life, experiencing frequent coughs and colds. I visited many states across India, but the advice remained the same: avoid spicy food and cold drinks, wear a mask, and take other precautions. However, when I visited Swasti Homoeo Clinic, Dr. Smriti ma\'am listened carefully to my health checkup report from Mumbai. She suggested switching to homeopathy, and after six months of treatment, my chronic pharyngitis is gone. I am very grateful to Dr. Smriti ma\'am.',
                'avatar'   => '/assets/images/patients/patient-01.png',
                'featured' => false,
            ],
            [
                'name'     => 'Gurjeet Kaur',
                'when'     => '3 weeks ago',
                'rating'   => 5,
                'quote'    => 'My 3 old daughter used to have repeated cold cough, now I am happy that she easily takes medicine frm dr Smriti for all of her problem & it get\'s cured very easily in short time',
                'avatar'   => '/assets/images/patients/patient-02.png',
                'featured' => false,
            ],
            [
                'name'     => 'Arpita Samadder',
                'when'     => 'a month ago',
                'rating'   => 5,
                'quote'    => 'The medicines worked liked magic to be honest. I couldn\'t sleep for one week due to non stop cough. After your meds I\'m finally sleeping peacefully. Thank you so much. I\'m really grateful.',
                'avatar'   => '/assets/images/patients/patient-03.png',
                'featured' => true,
            ],
            [
                'name'     => 'P Raja',
                'when'     => '4 months ago',
                'rating'   => 5,
                'quote'    => 'Swasti homeo clinic located in Garacharma is very excellent in delivering homeopathy treatment ...in my personnel experience i want to say that my daughter aged 11 years had cold allergic issues I have shown her every possible clinic but everything went in vain...later I came to know abt this clinic here the doctor is so patiently listen to patient to know abt root cause of the disease...then diagnosis effective medicine..now my daughter is fine and better...I would strongly recommend this clinic which is clean, hygienic and maintained by trained good staff...Homeopathy is best for everyone without side effects...Be Healthy! Stay Healthy!',
                'avatar'   => '/assets/images/patients/patient-04.png',
                'featured' => false,
            ],
            [
                'name'     => 'Khokan Sekh',
                'when'     => 'a month ago',
                'rating'   => 5,
                'quote'    => 'Amazed how my child\'s dry cough & cold got better in just 2 days, faster than previously taken allopathic medicines, with just few medicine only. Pleasantly surprised and thankful!!!',
                'avatar'   => '/assets/images/patients/patient-05.png',
                'featured' => true,
            ],
            [
                'name'     => 'Sweety Teddy',
                'when'     => 'a year ago',
                'source'   => 'Google review · 6 reviews · 2 photos',
                'rating'   => 5,
                'quote'    => 'No words to express our gratitude to Dr smriti. She is highly skilled homeopathic doctor who has done an excellent job in my case where many of the doctors and allopathic medicines couldn\'t help me. The greatest thing in her is her friendly nature and her service moto of charging very less.Two years ago I was diagnosed with endometriotic cyst of 7cm in right ovary and was asked to get the oophorectomy done asap. Consulted many doctors and spend lot of money but no use. I have faced horrible things like excess fatigue, prolonged and heavy periods, excessive pain in the right leg, eczema, loss of appetite,sleeplessness. Have completely lost hope on life and thought never be able to become normal again but fortunately I met Dr smriti 6 months back and can\'t believe the magic she has done to me that too by charging very very less amount for treatment. Frankly saying I am back to my beautiful life again. Cyst got reduced to much more extent, periods got regularised, my appetite is back, got rid of eczema, able to sleep peacefully, doing household things with an ease.She has not only treated me but my husband and father too. Thanks to such an amazing doctor 🙏',
                'avatar'   => '',
                'featured' => true,
            ],
            [
                'name'     => 'Rahul Sahu',
                'when'     => '2 months ago',
                'rating'   => 5,
                'quote'    => 'Genuine doctor. She treated my sons asthma, which got much better after few visits but my hair fall is still same which she said in the beginning',
                'avatar'   => '/assets/images/patients/patient-07.png',
                'featured' => false,
            ],
            [
                'name'     => 'Rahima Bibi',
                'when'     => '2 months ago',
                'rating'   => 5,
                'quote'    => 'Was diagnosed with chronic bronchitis in 2021, was on deryphillin, decadron and other medicines. After taking homoeo medicines from Dr. Smriti, my breathlessness, discomfort is much better, rarely need those medicines',
                'avatar'   => '/assets/images/patients/patient-08.png',
                'featured' => false,
            ],
            [
                'name'     => 'Akash Kumar',
                'when'     => 'a year ago',
                'rating'   => 5,
                'quote'    => 'Results are Spectacular for the treatment of Seborrheic dermatitis also called: seborrheic eczema from the very first month. As you can see how badly my scalp was affected before treatment. I was worried that no matter what i do this recurring issue which i was facing for long time would come back and i have to shave my head every now and then to clean the buildup. Which was definitely not the solution. I tried Allopathy for a year only to have pathetic results & the expensive allopathic medicines,lotions had no effect. The scalp was back to same after a week. But after research i came to know skin issues are internal and needs targeted approach for treatment. Hence switched to Homeopathy. I am very thankful to Dr. Smriti for the care & treatment provided. Due to which i am happy with the results and its been almost 6 months as of now.',
                'avatar'   => '/assets/images/patients/patient-09.png',
                'featured' => false,
            ],
            [
                'name'     => 'Zeba Yacub',
                'when'     => '9 months ago',
                'rating'   => 5,
                'quote'    => 'Excellent experience at Swasthi Homeo Clinic! Dr. Smriti is knowledgeable, friendly, and genuinely cares about patients\' well-being. Effective treatment and personalized attention. Highly recommend!',
                'avatar'   => '/assets/images/patients/patient-10.png',
                'featured' => true,
            ],
            [
                'name'     => 'Dr FARHEEN',
                'when'     => 'a year ago',
                'source'   => 'Google review · Local Guide · 37 reviews · 4 photos',
                'rating'   => 5,
                'quote'    => 'Great experience, definitely recommend patients especially facing reproductive health issues, issues related to PCOD, Thyroid disorders, Endometriosis, Fibroid, cysts to consult the Renowned Homeopath and get the benefits via Homeopathy remedies.',
                'avatar'   => '',
                'featured' => true,
            ],
            [
                'name'     => 'Seema Devi',
                'when'     => 'Edited a year ago',
                'source'   => 'Google review',
                'rating'   => 5,
                'quote'    => 'The treatment is affordable and my itching problems is completely cured and even my daughter is taking medicines for her irregular periods and severe cramps but now after taking medicines her periods are on time and with much less pain.',
                'avatar'   => '',
                'featured' => true,
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public static function featured(): array
    {
        $featured = array_values(array_filter(self::all(), static fn (array $t): bool => (bool) $t['featured']));

        return $featured !== [] ? $featured : array_slice(self::all(), 0, 3);
    }

    public static function averageRating(): float
    {
        $all = self::all();
        if ($all === []) {
            return 0.0;
        }

        $sum = array_sum(array_map(static fn (array $t): int => (int) $t['rating'], $all));

        return round($sum / count($all), 1);
    }

    /**
     * @return array<int,array{label:string,value:string}>
     */
    public static function highlights(): array
    {
        return [
            ['label' => 'Repeat patients',   'value' => 'Most of our new patients come by referral from an existing patient.'],
            ['label' => 'Written plan',      'value' => 'Nobody leaves without knowing what to take and when to come back.'],
            ['label' => 'Honest referral',   'value' => 'If your case needs a hospital, we say so on the first visit.'],
            ['label' => 'Island-wide reach', 'value' => 'Phone and WhatsApp advice for patients across the islands.'],
        ];
    }
}
