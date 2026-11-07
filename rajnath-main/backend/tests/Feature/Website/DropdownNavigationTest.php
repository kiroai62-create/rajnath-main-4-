<?php

namespace Tests\Feature\Website;

use App\Models\User;
use App\Support\AccountMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\StaffHelpers;
use Tests\TestCase;

/** Header language and account dropdowns, account section menus and the admin user menu. */
class DropdownNavigationTest extends TestCase
{
    use RefreshDatabase, StaffHelpers;

    private function customer(): User
    {
        $c = new User(['name' => 'Asha Verma', 'email' => 'asha@example.com']);
        $c->save();

        return $c;
    }

    public function test_language_dropdown_lists_enabled_languages_and_marks_the_current_one(): void
    {
        $html = $this->get('/faq?lang=hi')->assertOk()->getContent();
        $this->assertStringContainsString('aria-controls="lang-menu"', $html);
        $this->assertStringContainsString('aria-label="भाषा: हिन्दी"', $html);
        $this->assertMatchesRegularExpression('/lang="hi" hreflang="hi" class="menu-item"\s+aria-current="true"/', $html);
        $this->assertDoesNotMatchRegularExpression('/lang="en" hreflang="en" class="menu-item"\s+aria-current/', $html);
        $this->assertStringContainsString('lang="en" hreflang="en" class="menu-item"', $html);
        $this->assertStringContainsString('?lang=en', $html);
    }

    public function test_no_language_dropdown_on_an_english_only_site(): void
    {
        config(['locales.enabled' => ['en']]);
        $this->get('/faq')->assertOk()->assertDontSee('aria-controls="lang-menu"', false);
    }

    public function test_guest_sees_sign_in_and_no_account_menu(): void
    {
        $this->get('/faq')->assertOk()
            ->assertSee(route('account.sign-in'), false)
            ->assertDontSee('aria-controls="account-menu"', false);
    }

    public function test_signed_in_customer_gets_account_dropdown_with_sections_and_sign_out(): void
    {
        $html = $this->actingAs($this->customer())->get('/faq')->assertOk()->getContent();
        $this->assertStringContainsString('aria-controls="account-menu"', $html);
        $this->assertStringContainsString('asha@example.com', $html);
        $this->assertStringContainsString('>AV<', $html);
        foreach (['account.bookings', 'account.orders', 'account.wallet', 'account.reports.index', 'account.profile'] as $r) {
            $this->assertStringContainsString('href="'.route($r).'"', $html, $r);
        }
        $this->assertStringContainsString('action="'.route('account.sign-out').'"', $html);
    }

    public function test_staff_on_the_website_get_control_centre_and_staff_sign_out(): void
    {
        $this->syncRbac();
        $html = $this->asStaff($this->makeStaff(['owner'], ['name' => 'Ravi Jha']))->get('/faq')->assertOk()->getContent();
        $this->assertStringContainsString('aria-controls="account-menu"', $html);
        $this->assertStringContainsString('Control centre', $html);
        $this->assertStringContainsString('action="'.route('staff.logout').'"', $html);
        $this->assertStringNotContainsString('href="'.route('account.wallet').'"', $html);
    }

    public function test_account_pages_have_section_dropdowns_and_mark_the_current_section(): void
    {
        $html = $this->actingAs($this->customer())->get('/account/privacy')->assertOk()->getContent();
        $this->assertStringContainsString('aria-controls="account-sections"', $html);
        $this->assertStringContainsString('aria-controls="account-more"', $html);
        // Privacy is under "More", so the More button shows it as the current section
        $this->assertStringContainsString('aria-label="More: Privacy"', $html);
        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('account.privacy'), '#').'" class="menu-item"\s+aria-current="page"#', $html);
        // every section is still reachable
        foreach (AccountMenu::ITEMS as $it) {
            $this->assertStringContainsString('href="'.route($it['route']).'"', $html, $it['route']);
        }
    }

    public function test_admin_user_menu_and_collapsible_sidebar_groups(): void
    {
        $this->syncRbac();
        $html = $this->asStaff($this->makeStaff(['owner'], ['name' => 'Ravi Jha', 'email' => 'ravi@staff.test']))->get('/admin/faqs')->assertOk()->getContent();
        $this->assertStringContainsString('aria-controls="admin-user-menu"', $html);
        $this->assertStringContainsString('ravi@staff.test', $html);
        $this->assertStringContainsString('action="'.route('staff.logout').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.team.index').'"', $html);
        // the group with the current page is open, an unrelated one starts closed
        $this->assertMatchesRegularExpression('/<details[^>]*data-group="Website" data-active="1"\s+open\s*>/', $html);
        $this->assertMatchesRegularExpression('/<details[^>]*data-group="Money" data-active="0"\s*>/', $html);
        $this->assertStringContainsString(':inert="drawerInert"', $html);
    }

    public function test_admin_user_menu_hides_links_the_role_cannot_use(): void
    {
        $this->syncRbac();
        $html = $this->asStaff($this->makeStaff(['content_author']))->get('/admin')->assertOk()->getContent();
        $this->assertStringContainsString('aria-controls="admin-user-menu"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.team.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.health').'"', $html);
    }
}
