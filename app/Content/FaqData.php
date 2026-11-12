<?php
declare(strict_types=1);

namespace App\Content;

/**
 * Default FAQ set. These rows are seeded into MongoDB on the first admin or
 * public FAQ read so they can be edited from /admin/faqs.
 */
final class FaqData
{
    /**
     * @return array<int,array{question:string,answer:string,category:string,sort_order:int,is_published:int}>
     */
    public static function all(): array
    {
        $rows   = [];
        $sort   = 0;

        foreach (self::groups() as $category => $items) {
            foreach ($items as $item) {
                $rows[] = [
                    'question'    => $item['q'],
                    'answer'      => $item['a'],
                    'category'    => $category,
                    'sort_order'  => $sort += 10,
                    'is_published'=> 1,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<string,array<int,array{q:string,a:string}>>
     */
    public static function groups(): array
    {
        return [
            'About the clinic' => [
                [
                    'q' => 'Where is Swasti Homoeo Clinic located?',
                    'a' => 'The clinic is at NH4 main road, Garacharma, near Susane roses. Call '
                        . '+91 79806 44867 or send a WhatsApp message and we will share the exact '
                        . 'location pin as well.',
                ],
                [
                    'q' => 'Who treats patients at the clinic?',
                    'a' => 'All consultations are conducted by Dr. Smriti Das, BHMS, MD (Homeopathy, '
                        . 'WBUHS), the homoeopathic physician, with more than eight years of '
                        . 'clinical experience. Registration particulars are displayed at the clinic and '
                        . 'available on request.',
                ],
                [
                    'q' => 'Is homoeopathy safe without side effects?',
                    'a' => 'Homoeopathic medicines are highly diluted and, when prescribed in the correct '
                        . 'potency and dose, are generally very well tolerated. "No side effects" is not '
                        . 'the right claim, though — some patients notice a temporary change in '
                        . 'symptoms after starting a remedy, and the remedy must be chosen for the right '
                        . 'person. The real risk is delaying necessary medical care, which is why we '
                        . 'always tell you when a condition needs hospital treatment.',
                ],
                [
                    'q' => 'What are your consultation timings?',
                    'a' => 'Monday to Saturday the OPD runs from 9:00 AM to 12:30 PM and 4:30 PM to 7:30 PM. '
                        . 'The clinic is closed on Sunday. Patients are always given a specific slot so the waiting '
                        . 'time is short.',
                ],
            ],
            'Booking and fees' => [
                [
                    'q' => 'What does a consultation cost, and is it inclusive of medicines?',
                    'a' => 'The consultation fee is separate from the cost of the dispensed remedy, and '
                        . 'the remedy cost depends on the potencies and quantities your prescription '
                        . 'requires. Both figures are confirmed to you in writing before anything is '
                        . 'dispensed, and a receipt is issued. Follow-up review fees are lower than a '
                        . 'first consultation.',
                ],
                [
                    'q' => 'Is a follow-up appointment necessary?',
                    'a' => 'Yes. Homoeopathic prescribing depends on how your symptoms are responding, so '
                        . 'review appointments let us continue an effective prescription, reduce it once '
                        . 'things settle, or change it if it is not working. Most patients need two to four '
                        . 'reviews before a meaningful judgement can be made.',
                ],
                [
                    'q' => 'What should I bring to my first appointment?',
                    'a' => 'Bring a list of every medicine you are currently taking — including '
                        . 'supplements — with the doses, any previous prescriptions or investigation '
                        . 'reports, and a rough timeline of how your symptoms have changed. If you can '
                        . 'note down when your periods or sleep patterns changed, that is genuinely '
                        . 'useful to the doctor.',
                ],
                [
                    'q' => 'Can I be treated over phone or WhatsApp?',
                    'a' => 'An online or telephone discussion is fine for a brief follow-up, for reviewing '
                        . 'a prescription you have already been given, and for general guidance. A first '
                        . 'consultation requires an in-person visit so that you can be examined and a '
                        . 'complete history taken.',
                ],
            ],
            'Treatment and results' => [
                [
                    'q' => 'Can I stop my allopathic medicines once I feel better?',
                    'a' => 'No. Do not stop, reduce or change any prescribed medicine without speaking to '
                        . 'the doctor who prescribed it. In particular, insulin, blood-pressure and thyroid '
                        . 'medicines, anti-epileptics, steroids and antibiotics must continue exactly as '
                        . 'prescribed. Our aim is to work alongside your medical care, never to replace it '
                        . 'unilaterally.',
                ],
                [
                    'q' => 'What should I avoid while taking treatment?',
                    'a' => 'Take the remedy as prescribed, roughly half an hour away from meals, and avoid '
                        . 'strong coffee, alcohol and very spicy or very oily food for the first few weeks. '
                        . 'Do not rub the pills, keep them away from strong smells and camphor, and store '
                        . 'the bottle tightly closed. Do not crush the pills to make them easier to swallow '
                        . 'unless the doctor has told you to.',
                ],
                [
                    'q' => 'Will homoeopathy work for my condition?',
                    'a' => 'Ask us at your consultation and we will give you a direct answer. Some '
                        . 'conditions respond well, some respond slowly, and a few need hospital care '
                        . 'instead. We would rather tell you on the first day that homoeopathy is not the '
                        . 'right tool for your problem than take your money and your time for six months.',
                ],
            ],
            'For children and parents' => [
                [
                    'q' => 'Can homoeopathy be given to infants and children?',
                    'a' => 'Yes, with the correct potency and dose for the child’s age and '
                        . 'sensitivity, and with the parent administering it rather than the child. A '
                        . 'paediatric consultation takes longer because the history comes from you and '
                        . 'every detail about feeding, sleep and behaviour matters.',
                ],
                [
                    'q' => 'Is homoeopathy safe during pregnancy?',
                    'a' => 'It can be used under professional supervision once the pregnancy is confirmed by '
                        . 'a qualified obstetrician. We will not prescribe during the first trimester '
                        . 'except in exceptional circumstances, and we will always ask you to continue any '
                        . 'medication your doctor has given you. Please bring your antenatal records to '
                        . 'every visit.',
                ],
                [
                    'q' => 'Can I give my child a remedy from an earlier consultation?',
                    'a' => 'Please do not. A remedy is matched to one child’s symptom picture, and '
                        . 'an old prescription is often not the right one for a new illness. Bring the old '
                        . 'bottle to the visit so the doctor can review what was given and why.',
                ],
            ],
            'Emergencies and limits' => [
                [
                    'q' => 'Does the clinic handle emergencies?',
                    'a' => 'No. A homoeopathy clinic is not an emergency facility and we do not have the '
                        . 'equipment, monitoring or surgical capability for one. For chest pain, breathing '
                        . 'difficulty, sudden weakness, facial droop, heavy bleeding, loss of '
                        . 'consciousness, a severe allergic reaction, a serious fall or a suspected '
                        . 'poisoning, call 112 or go to the nearest emergency department immediately.',
                ],
                [
                    'q' => 'Can homoeopathy replace antibiotics or surgery?',
                    'a' => 'No. Where antibiotics, surgery or hospital care are clinically indicated, that '
                        . 'is the treatment of choice and we will say so. Homoeopathy is used as '
                        . 'complementary care: it can support recovery, manage recurrent tendencies and '
                        . 'sometimes reduce repeat prescriptions, but it does not substitute for '
                        . 'necessary medical intervention.',
                ],
                [
                    'q' => 'Why is a homoeopathy doctor always asking about my whole lifestyle?',
                    'a' => 'Because classical homoeopathy works on the whole person rather than on a single '
                        . 'symptom. Your sleep, digestion, temperature preference, thirst, mood, what you '
                        . 'crave and what you dread, your menstrual pattern and your family history are all '
                        . 'part of the "totality" the remedy is matched to. That is why the first '
                        . 'consultation takes 30 to 45 minutes while a follow-up takes ten.',
                ],
            ],
        ];
    }

    /** @return string[] */
    public static function categories(): array
    {
        return array_keys(self::groups());
    }

    /**
     * @return array<int,array{question:string,answer:string}>
     */
    public static function topFaqs(int $limit = 6): array
    {
        $rows = array_slice(self::all(), 0, $limit);

        return array_map(
            static fn (array $r): array => ['question' => $r['question'], 'answer' => $r['answer']],
            $rows
        );
    }
}
