<?php

namespace Tests\Feature\Mail;

use App\Mail\YourMailableName;
use App\Models\YourModel;
use App\Models\User;
use Carbon\Carbon;
use Tests\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\WithFaker;

class YourMailableNameTest extends TestCase
{
    use WithFaker;

    protected function beforeTest()
    {
        $this->seedOnce([
            \Database\Seeders\VirtualResourcesTableSeeder::class,
            \Database\Seeders\TermsTableSeeder::class,
        ]);
    }

    /**
     * Test HTML email output
     *
     * @test
     */
    public function html(): void
    {
        $mailer = Mail::fake();

        Mail::to('test@example.com')
            ->send(new YourMailableName($this->seedModel()));

        $mailer->assertEqualHtml(
            spaceless(file_get_contents(
                base_path('tests/data/email_template_test/to_consumer/your_template.html')
            )),
            'Tests\spaceless'
        );
    }

    /**
     * Test plain text email output
     *
     * @test
     */
    public function text(): void
    {
        $mailer = Mail::fake();

        Mail::to('test@example.com')
            ->send(new YourMailableName($this->seedModel()));

        $mailer->assertEqualText(
            spaceless(file_get_contents(
                base_path('tests/data/email_template_test/to_consumer/your_template_plain.txt')
            )),
            'Tests\spaceless'
        );
    }

    /**
     * Test email subject line
     *
     * @test
     */
    public function subject(): void
    {
        $mailer = Mail::fake();

        $mail = new YourMailableName($this->seedModel());
        $builtMail = $mail->build();

        self::assertStringContainsString('Your Expected Subject', $builtMail->subject);
    }

    /**
     * Test sanitize method returns correct data structure
     *
     * @test
     */
    public function sanitizeReturnsCorrectDataStructure(): void
    {
        $mail = new YourMailableName($this->seedModel());

        $reflection = new \ReflectionClass($mail);
        $sanitizeMethod = $reflection->getMethod('sanitize');
        $sanitizeMethod->setAccessible(true);

        $result = $sanitizeMethod->invoke($mail);

        self::assertArrayHasKey('model', $result);
        // Add more assertions as needed
    }

    // =========================================================================
    // Test Data Seed Methods
    // =========================================================================

    private function seedModel(): YourModel
    {
        return (new YourModel())
            ->setAttribute('id', 123)
            ->setAttribute('name', 'Test Model')
            ->setAttribute('created_at', Carbon::now()->subDays(7)) // 7日前
            ->setRelation('user', $this->seedUser());
    }

    private function seedUser(): User
    {
        return new User([
            'first_name' => '太郎',
            'last_name'  => '山田',
        ]);
    }
}
