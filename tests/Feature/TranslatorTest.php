<?php

namespace Riomigal\Languages\Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Riomigal\Languages\Livewire\Translators;
use Riomigal\Languages\Models\Language;
use Riomigal\Languages\Models\Translator;
use Riomigal\Languages\Tests\BaseTestCase;

class TranslatorTest extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * @var Collection
     */
    protected Collection $languages;

    /**
     * @var Translator
     */
    protected Translator $admin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->languages = factory(Language::class)->times(30)->create();
        $this->admin = Translator::first();
    }

    /**
     * @test
     */
    public function trans_admin_can_access_translators_and_see_admin_options(): void
    {
        /**
         * Given a user is an admin, when he accesses the translator page
         */
        Livewire::actingAs($this->admin)
            ->test(Translators::class)
            /**
             * Then he can see the form when toggling and the admin options
             */
            ->assertSee(__('languages::translators.button_toggle_create_form'))
            ->assertSee(__('languages::table.delete'))
            ->assertSee(__('languages::table.edit'))
            ->assertDontSee('<form id="createOrUpdateForm" class="pb-4">')
            ->set('showForm', true)
            ->assertSeeHtml('<form id="createOrUpdateForm" class="pb-4">');

    }

    /**
     * @test
     */
    public function trans_guest_cannot_access_translators_page(): void
    {
        Livewire::test(Translators::class)
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function trans_no_admin_cannot_access_translators_page(): void
    {
        Livewire::actingAs(factory(Translator::class)->create())
            ->test(Translators::class)
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function trans_admin_can_delete_user(): void
    {
        /**
         * Background
         */
        $translator = factory(Translator::class)->create();

        $this->assertEquals(2, Translator::count());

        /**
         * Given there are two translators
         */
        Livewire::actingAs($this->admin)
            ->test(Translators::class)
            ->assertSee($translator->email)
            ->assertSee($this->admin->email)
            /**
             * When the admin deletes the second translator
             */
            ->call('delete', $translator->id)
            /**
             * Then it should only show the admin email
             */
            ->assertDontSee($translator->email)
            ->assertSee($this->admin->email);

        $this->assertEquals(1, Translator::count());

    }

    /**
     * @test
     */
    public function trans_admin_cannot_delete_first_user_super_admin(): void
    {

        /**
         * Given there is a admin account and its the first generated admin user
         */
        Livewire::actingAs($this->admin)
            ->test(Translators::class)
            /**
             * When the admin tries to delete his own account
             */
            ->call('delete', $this->admin->id)
            /**
             * Then it should not delete the first account since the first account serves as entry account for the app (super admin)
             */
            ->assertSee($this->admin->email);

        $this->assertEquals(1, Translator::count());

    }

    /**
     * @test
     */
    public function trans_admin_can_create_a_user_success(): void
    {
        /**
         * Background
         */

        $this->assertEquals(1, Translator::count());

        /**
         * Given a admin wants to create a new account
         */
        Livewire::actingAs($this->admin)
            ->test(Translators::class)
            /**
             * When the admin sends the form data
             */
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john@example.test')
            ->set('phone', '234234234')
            ->set('password', 'aaaaaaaa')
            ->set('password_confirmation', 'aaaaaaaa')
            ->set('languages', $this->languages->pluck('id')->all())
            ->set('admin', false)
            ->call('create')
            ->assertHasNoErrors()
            /**
             * Then it should create a new account
             */
            ->assertSee(Translator::find(2)->email);


        $this->assertEquals(2, Translator::count());
    }

    /**
     * @test
     */
    public function trans_admin_cannot_create_a_user_validation_fails(): void
    {
        /**
         * Background
         */

        $this->assertEquals(1, Translator::count());

        /**
         * Given a admin wants to create a new account
         */
        Livewire::actingAs($this->admin)
            ->test(Translators::class)
            /**
             * When the admin sends the form data
             */
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('email', 'john')
            ->set('phone', '')
            ->set('password', 'aaaaaaaa')
            ->set('password_confirmation', 'aaaaaaa')
            ->set('languages', [])
            ->set('admin', false)
            ->call('create')
            ->assertHasErrors(['password_confirmation', 'email', 'languages']);
        /**
         * Then it should NOT create a new account
         */

        $this->assertEquals(1, Translator::count());
    }

    /**
     * @test
     */
    public function trans_admin_can_update_users_password(): void
    {
        /**
         * Background
         */
        $this->assertEquals(1, Translator::count());
        $translator = factory(Translator::class)->create();

        $this->assertFalse(Auth::attempt(['email' => $translator->email, 'password' => 'newvalue']));

        /**
         * Given a admin wants to update the password of a translator
         */
        Livewire::actingAs($this->admin)
            ->test(Translators::class)
            /**
             * When the admin updates the password
             */
            ->set('new_password', 'newvalue')
            ->set('new_password_confirmation', 'newvalue')
            ->set('translator', $translator)
            ->call('updateNewPassword')
            ->assertHasNoErrors();

        /**
         * Then the translator can login with the new password
         */
        $this->assertTrue(Auth::attempt(['email' => $translator->email, 'password' => 'newvalue']));
    }

    /**
     * @test
     */
    public function trans_livewire_admin_can_search_translator(): void
    {
        /**
         * Background
         */
        $translators = factory(Translator::class)->times(2)->create();

        /**
         * Given there are three languages and a user wants to search for "English"
         */
        $newTranslator = $translators->get(0);
        $newTranslator_2 = $translators->get(1);
        $this->assertEquals(3, Translator::count());

        Livewire::actingAs($this->admin)
            ->test(Translators::class)
            ->assertSee($newTranslator->email)
            ->assertSee($newTranslator_2->email)
            /**
             * When the user searches for a translator
             */
            ->set('search', $this->admin->email)
            /**
             * Then only the searched translator email is shown
             */
            ->assertSee($this->admin->email)
            ->assertDontSee($newTranslator->email)
            ->assertDontSee($newTranslator_2->email);

    }


}
