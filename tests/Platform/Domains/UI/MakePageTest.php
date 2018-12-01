<?php

namespace Tests\Platform\Domains\UI;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SuperV\Platform\Domains\Database\Schema\SchemaService;
use SuperV\Platform\Domains\Resource\Field\FieldFactory;
use SuperV\Platform\Domains\Resource\Form\Form;
use SuperV\Platform\Domains\Resource\Testing\ResourceTestHelpers;
use SuperV\Platform\Domains\UI\Page\MakeFormPage;
use SuperV\Platform\Domains\UI\Page\MakeTablePage;
use Tests\Platform\Domains\Resource\Fixtures\HelperComponent;
use Tests\Platform\TestCase;

class MakePageTest extends TestCase
{
    use RefreshDatabase;
    use ResourceTestHelpers;

    protected $flags = [];

    function test__make_table_page()
    {
        $this->withoutExceptionHandling();
        $fields = [
            FieldFactory::createFromArray(['type' => 'text', 'name' => 'table']),
            FieldFactory::createFromArray(['type' => 'text', 'name' => 'singular']),
        ];

        $rows = app(SchemaService::class)->getDatabaseTables();

        MakeTablePage::forUrl('sv/table/abc')
                     ->setFields($fields)
                     ->setRows($rows)
                     ->register();

        $response = $this->getJsonUser('sv/table/abc/data')->assertOk();

        $this->assertEquals($rows->count(), count($response->decodeResponseJson('data.rows')));
    }

    function test__make_form_page()
    {
        $this->withoutExceptionHandling();
        $fields = [
            FieldFactory::createFromArray(['type' => 'text', 'name' => 'title']),
            FieldFactory::createFromArray(['type' => 'text', 'name' => 'email', 'flags' => ['nullable']]),
        ];

        MakeFormPage::forUrl('sv/pages/abc')
                    ->setFields($fields)
                    ->onSuccess(function (Form $form) {
                        $this->assertEquals('Users', $form->getFieldValue('title'));

                        return ['status' => 'ok'];
                    })
                    ->register();

        $page = $this->getPageFromUrl('sv/pages/abc');
        $this->assertNotNull($page);
        $form = HelperComponent::from($page->getProp('blocks.0'));
        $this->assertEquals(count($fields), count($form->getProp('fields')));

        $response = $this->postJsonUser($form->getProp('url'), ['title' => 'Users', 'email' => 'a'])->assertOk();
        $this->assertEquals('ok', $response->decodeResponseJson('status'));
    }
}
