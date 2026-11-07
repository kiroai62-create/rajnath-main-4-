<?php

namespace App\Support;

use App\Domain\Booking\Slots;
use App\Models\Offering;

/**
 * "How booking works" for a service: the steps a customer goes through, from choosing an
 * option to receiving the result. Shown on the service page, in the app and to the assistant.
 */
class BookingSteps
{
    /**
     * @return array<int, array{title: string, text: string}>
     */
    public static function for(Offering $o): array
    {
        $variants = $o->variants->where('active', true);
        $modes = $variants->pluck('mode')->filter()->unique()->values()->all();
        $family = $o->family?->code;
        $slotted = $variants->contains(fn ($v) => Slots::bookable($v));

        $steps = [[__('Choose an option'), __('Pick the option that suits you on this page. Prices include GST.')]];

        if (in_array($family, ['puja', 'chadhava'], true)) {
            $steps[] = [__('Add the sankalp details'), __('Enter the names, gotra, wish and preferred date. You can change them until the puja is scheduled.')];
            $steps[] = [__('Pay securely online — last step'), __('Pay by UPI, card, net banking or your wallet. You get a receipt and a GST invoice at once.')];
            $steps[] = [__('Date and time confirmed'), __('Acharya Ji\'s team fixes the muhurat and tells you the date, time and live link by SMS, email and app notification.')];
            $steps[] = [__('Puja performed in your name'), __('The puja is done with full vidhi. Photos (and video where possible) appear in your account; prasad is sent with the Family package.')];

            return self::shape($steps);
        }
        if (in_array('report', $modes, true) && $o->slug === 'palmistry') {
            $steps[] = [__('Upload photos of both palms'), __('Choose "Online — written report from palm photos" and take two clear photos with your phone camera in daylight.')];
            $steps[] = [__('Pay securely online — last step'), __('Pay by UPI, card, net banking or your wallet. That is all; nothing else is asked after paying.')];
            $steps[] = [__('Acharya Ji\'s team reads your palms'), __('Lines, mounts and signs are studied by a person, not a computer.')];
            $steps[] = [__('Your report is ready'), __('You get a notification and email; read it in your account or download the PDF.')];
            if ($slotted) {
                $steps[] = [__('Prefer to talk?'), __('Choose a video or audio call and show your palms on the call, or visit the Patna office.')];
            }

            return self::shape($steps);
        }
        if (in_array($family, ['report', 'bundle'], true)) {
            $steps[] = [__('Share your birth details'), __('Choose a saved birth profile (date, time and place of birth) and the report language.')];
            $steps[] = [__('Pay securely online — last step'), __('Pay by UPI, card, net banking or your wallet.')];
            $steps[] = [__('Prepared by Acharya Ji\'s team'), __('Your chart is calculated with the Swiss Ephemeris and the report is written by a person.')];
            $steps[] = [__('Download your report'), __('You get a notification and email when the PDF is ready in your account.')];

            return self::shape($steps);
        }
        if ($family === 'written_question') {
            $steps[] = [__('Write your question'), __('Choose your birth profile and write your question.')];
            $steps[] = [__('Pay securely online — last step'), __('Pay by UPI, card, net banking or your wallet.')];
            $steps[] = [__('Get a written answer'), __('The answer appears in your account, usually within 48 working hours, and we email you.')];

            return self::shape($steps);
        }
        if ($slotted) {
            $steps[] = [__('Pick a date and time'), __('Free times for the next three weeks are shown in Indian Standard Time.')];
            $steps[] = [__('Pay securely online'), __('Your time is held for 15 minutes while you pay by UPI, card, net banking or wallet.')];
            $steps[] = [__('Get confirmation'), __('The booking, call link and reminders 24 hours and 1 hour before reach you by SMS, email and app notification.')];
            $steps[] = [__('Join the consultation'), __('Join the call from your account or the app, or come to the Patna office for an in-person meeting. You can move or cancel up to 24 hours before.')];
        }
        if (in_array('onsite', $modes, true)) {
            $steps[] = [__('Site visit'), __('For a site visit, add the property address and preferred date, then pay online. Our team calls you within one working day to confirm the date. Travel within the option you chose is included.')];
        }
        if (! $slotted && ! in_array('onsite', $modes, true)) {
            $steps[] = [__('Pay securely online'), __('Pay by UPI, card, net banking or your wallet.')];
            $steps[] = [__('We contact you'), __('Our team calls you within one working day to take your details and fix the time.')];
        }

        return self::shape($steps);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $steps
     * @return array<int, array{title: string, text: string}>
     */
    private static function shape(array $steps): array
    {
        return array_map(fn ($s) => ['title' => $s[0], 'text' => $s[1]], $steps);
    }
}
