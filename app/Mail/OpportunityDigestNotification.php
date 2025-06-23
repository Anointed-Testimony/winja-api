<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OpportunityDigestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $opportunities;
    public $opportunityCount;
    public $digestPeriod;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $opportunities, $digestPeriod = '5 hours')
    {
        $this->user = $user;
        $this->opportunities = $opportunities;
        $this->opportunityCount = count($opportunities);
        $this->digestPeriod = $digestPeriod;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->opportunityCount > 0 
            ? "{$this->opportunityCount} New Opportunities in Your Digest"
            : "Your Opportunity Digest";

        return $this->subject($subject)
                    ->view('emails.opportunity-digest-notification')
                    ->with([
                        'user' => $this->user,
                        'opportunities' => $this->opportunities,
                        'opportunityCount' => $this->opportunityCount,
                        'digestPeriod' => $this->digestPeriod,
                        'opportunitiesUrl' => config('app.frontend_url') . '/opportunities',
                        'hasOpportunities' => $this->opportunityCount > 0,
                    ]);
    }
} 