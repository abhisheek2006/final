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
                    'q' => 'What are your consultation timings?',
                    'a' => 'Monday to Saturday the OPD runs from 9:00 AM to 12:30 PM and 4:30 PM to 7:30 PM. '
                        . 'The clinic is closed on Sunday.',
                ],
            ],
            'Treatment and results' => [
                [
                    'q' => 'Is homoeopathy safe without side effects?',
                    'a' => 'No treatment can be guaranteed to be free of side effects. Products labelled '
                        . 'homeopathic may contain active ingredients and can cause side effects or '
                        . 'interact with other medicines. Tell your qualified health care provider about '
                        . 'any product you use, and do not delay or replace prescribed medical care.',
                ],
                [
                    'q' => 'Can I take homoeopathic medicines alongside allopathic medication, or should I stop my current medicines?',
                    'a' => 'Homoeopathy is sometimes used as a complementary approach, but good-quality evidence has not established that it acts quickly for acute conditions or treats long-standing disease. Do not stop, reduce or change prescribed medicines; discuss any complementary product with your qualified health care provider.',
                ],
                [
                    'q' => 'What should I avoid while taking treatment?',
                    'a' => 'The clinic advises avoiding Ayurvedic medicines, Ayurvedic toothpaste and '
                        . 'products containing Ayurvedic ingredients while taking a remedy. Also avoid '
                        . 'crude or raw herbal substances such as aloe juice, giloy juice or churan, and '
                        . 'strong-smelling substances such as ashwagandha, camphor, menthol, coffee, dark '
                        . 'chocolate and tamarind. Keep the medicine in a cool, dry place away from '
                        . 'direct sunlight. Do not stop prescribed medicines without speaking to the '
                        . 'clinician who prescribed them.',
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
                    'a' => 'Newborns and children should be assessed by a qualified paediatric clinician '
                        . 'before any medicine is given. If a homeopathic product is being considered, '
                        . 'discuss it and the child’s other medicines with the clinician. Do not delay or '
                        . 'replace recommended paediatric care, vaccines or prescribed medicines. No '
                        . 'product can be promised to be free of adverse effects.',
                ],
                [
                    'q' => 'Is homoeopathy safe during pregnancy?',
                    'a' => 'If you are pregnant and have a fever, flu-like symptoms, excessive vomiting or '
                        . 'urinary symptoms, contact your obstetrician or doctor promptly. These symptoms '
                        . 'may need timely assessment and treatment. Discuss any complementary product '
                        . 'with your obstetrician first, and do not use it instead of prescribed care.',
                ],
                [
                    'q' => 'Can I give my child a remedy from an earlier consultation?',
                    'a' => 'Please do not. A remedy is matched to one child’s symptom picture, and '
                        . 'an old prescription may not be suitable for a new illness. Contact your child’s '
                        . 'health care provider for advice about new or worsening symptoms.',
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
                    'q' => 'What should I bring to my first appointment?',
                    'a' => 'Bring your previous prescriptions, investigation reports, and details of your '
                        . 'past medical history and family history.',
                ],
                [
                    'q' => 'Can I be treated over phone or WhatsApp?',
                    'a' => 'An online or telephone discussion is fine for a brief follow-up, for reviewing '
                        . 'a prescription you have already been given, and for general guidance. A first '
                        . 'consultation requires an in-person visit so that you can be examined and a '
                        . 'complete history taken.',
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
