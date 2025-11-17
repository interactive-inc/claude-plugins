<?php

namespace App\Mail;

use App\Mail\Traits\SanitizeUser;
use App\Mail\Traits\SanitizeCompany;
use App\Models\YourModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

/**
 * [Description of this mail]
 *
 * @example
 * Mail::to('user@example.com')->send(new YourMailableName($model));
 */
class YourMailableName extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    use SanitizeUser, SanitizeCompany;  // Include relevant Sanitize Traits

    public function __construct(
        private YourModel $model
    ) {
        parent::__construct();
    }

    public function build(): static
    {
        $siteName = config('siteNames.users');

        return $this->view('virtual_resources::emails.to_consumer.your_template')
            ->text('virtual_resources::emails.to_consumer.your_template_plain')
            ->subject('Your Email Subject【' . $siteName . '】');
    }

    protected function sanitize(): array
    {
        return [
            'model' => [
                'id'   => $this->model->id,
                'name' => $this->model->name,
                // Add more fields as needed
            ],
            // Use Sanitize Traits for complex models
            // 'user' => $this->sanitizeUserForMe($this->model->user),
            // 'company' => $this->sanitizeCompany($this->model->company),
        ];
    }
}
