<?php

namespace Tests\Feature\Astro;

use App\Domain\Analytics\ToolPdf;
use App\Domain\Astro\Engine;
use App\Models\ToolRun;
use App\Support\Pdf\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Every free-calculator run is saved (encrypted), countable in the admin, and downloadable as a templated PDF. */
class ToolHistoryAndPdfTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    private const UA = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private function fakePrinter(): void
    {
        $this->mock(Pdf::class, fn (MockInterface $m) => $m->shouldReceive('render')->andReturnUsing(fn (string $html) => '%PDF-1.4 '.$html));
    }

    public function test_website_run_is_saved_and_its_pdf_follows_the_template(): void
    {
        $this->syncRbac();
        $this->fakePrinter();
        $res = $this->withHeaders(['User-Agent' => self::UA])->post(route('tools.compute', 'numerology'), ['name' => 'Ravi Kumar', 'birth_date' => '1990-05-14'])->assertOk()
            ->assertSee('Download PDF')->assertDontSee('are not stored');
        $run = ToolRun::firstOrFail();
        $this->assertSame('numerology', $run->tool);
        $this->assertSame('Ravi Kumar', $run->inputs['name']);
        $this->assertStringNotContainsString('Ravi Kumar', (string) DB::table('tool_runs')->value('inputs'), 'birth details are encrypted at rest');
        $this->assertStringContainsString('Mulank 5', $run->summary);
        $this->assertNotNull($run->visitor);

        // the link is signed: guessing a reference does not work
        $this->get('/tools/numerology/pdf/'.$run->ref)->assertForbidden();
        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->put(route('admin.pdf-templates.update', 'numerology'), [
            'enabled' => '1', 'title' => 'Ank Jyotish Report', 'accent' => '#7a1f2b', 'intro' => 'Prepared for you by Acharya Ji.', 'closing' => 'Call us for a full reading.',
            'disclaimer' => 'For guidance only.', 'paper' => 'A4', 'show_charts' => '1', 'show_contact' => '0', 'show_birth' => '1', 'watermark' => 'ARJ',
        ])->assertRedirect();
        $pdf = $this->get($res->viewData('pdfUrl'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $body = $pdf->getContent();
        $this->assertStringStartsWith('%PDF', $body);
        $this->assertStringContainsString('Ank Jyotish Report', $body);
        $this->assertStringContainsString('#7a1f2b', $body);
        $this->assertStringContainsString('Call us for a full reading.', $body);
        $this->assertSame(1, $run->fresh()->pdf_downloads);
        $this->assertSame(1, DB::table('analytics_events')->where('type', 'pdf_download')->count());

        // the owner switches this PDF off
        $this->asStaff($owner)->put(route('admin.pdf-templates.update', 'numerology'), ['enabled' => '0', 'accent' => '#7a1f2b', 'paper' => 'A4'])->assertRedirect();
        $this->get($res->viewData('pdfUrl'))->assertNotFound();
        $this->asStaff($owner)->put(route('admin.pdf-templates.update', 'numerology'), ['reset' => '1'])->assertRedirect();
        $this->assertFalse(app(ToolPdf::class)->hasOwn('numerology'));
    }

    public function test_admin_sees_counts_history_and_details_with_permission(): void
    {
        $this->syncRbac();
        $this->fakePrinter();
        ToolRun::factory()->count(3)->create(['tool' => 'numerology']);
        ToolRun::factory()->create(['tool' => 'kundli', 'channel' => 'app', 'inputs' => ['name' => 'Sita Devi', 'birth_date' => '1992-08-20', 'birth_time' => '14:10', 'place_id' => 1, 'birth_place' => 'Patna, Bihar, India']]);
        $admin = $this->makeStaff(['administrator']);
        $page = $this->asStaff($admin)->get(route('admin.analytics.tools'))->assertOk()->assertSee('Sita Devi')->assertSee('Patna, Bihar, India');
        $this->assertSame(4, $page->viewData('sum')['runs']);
        $this->assertSame(1, $page->viewData('sum')['kundli']);
        $this->asStaff($admin)->get(route('admin.analytics.tools', ['tool' => 'kundli', 'channel' => 'app']))->assertOk()->assertSee('Sita Devi');
        $csv = $this->asStaff($admin)->get(route('admin.analytics.tools.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Sita Devi', $csv);

        $run = ToolRun::where('tool', 'numerology')->first();
        $this->asStaff($admin)->get(route('admin.analytics.tools.show', $run))->assertOk()->assertSee($run->inputs['name']);
        $this->assertTrue(DB::table('audit_logs')->where('action', 'analytics.tool_run_viewed')->exists());

        // a publisher sees the numbers but not the names
        $publisher = $this->makeStaff(['content_publisher']);
        $this->asStaff($publisher)->get(route('admin.analytics.tools'))->assertOk()->assertDontSee('Sita Devi');
        $this->asStaff($publisher)->get(route('admin.analytics.tools.show', $run))->assertForbidden();
    }

    public function test_app_run_returns_a_pdf_link_and_kundli_pdf_has_charts(): void
    {
        if (! app(Engine::class)->available()) {
            $this->markTestSkipped('engine not built');
        }
        $this->syncRbac();
        $this->fakePrinter();
        DB::table('places')->insert(['id' => 1260086, 'name' => 'Patna', 'ascii_name' => 'Patna', 'admin1' => 'Bihar', 'country_code' => 'IN', 'country' => 'India', 'lat' => 25.59408, 'lng' => 85.13563, 'tz_name' => 'Asia/Kolkata', 'population' => 1]);
        $res = $this->withHeaders(['User-Agent' => 'Dart/3.9 (dart:io)', 'X-App-Install' => 'abcdef12-3456-7890-abcd-ef1234567890'])
            ->postJson('/api/v1/tools/kundli', ['name' => 'Ravi', 'birth_date' => '1990-05-14', 'birth_time' => '06:30', 'place_id' => 1260086])->assertOk();
        $run = ToolRun::firstOrFail();
        $this->assertSame('app', $run->channel);
        $this->assertSame('Patna, Bihar, India', $run->inputs['birth_place']);
        $this->assertStringContainsString('Lagna', $run->summary);
        $body = $this->get($res->json('data.pdf_url'))->assertOk()->getContent();
        $this->assertStringContainsString('Rashi chart (D1)', $body);
        $this->assertStringContainsString('Vimshottari dasha', $body);
        $this->assertSame(1, DB::table('analytics_events')->where('type', 'pdf_download')->where('channel', 'app')->count());

        $owner = $this->makeStaff(['owner']);
        $this->asStaff($owner)->get(route('admin.pdf-templates.preview', 'kundli'))->assertOk()->assertSee('Sample Person');
        $this->asStaff($owner)->get(route('admin.pdf-templates.edit', 'kundli'))->assertOk()->assertSee('Planet positions');
    }
}
